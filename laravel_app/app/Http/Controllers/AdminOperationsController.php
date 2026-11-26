<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, Category, Coupon, Product, ProductKey, User};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\Rule;

class AdminOperationsController extends Controller
{
    private function dates(Request $request): array
    {
        $data = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();
        abort_if($from->gt($to) || $from->diffInDays($to) > 366, 422, 'Khoảng báo cáo tối đa 366 ngày.');
        return [$from, $to];
    }
    public function reports(Request $request)
    {
        Gate::authorize('manage-finance');
        [$from, $to] = $this->dates($request);
        $orders = DB::table('orders')->whereBetween('created_at', [$from, $to]);
        return view('admin.reports', [
            'from' => $from, 'to' => $to,
            'revenue' => (clone $orders)->where('status', 'completed')->where('payment_status', 'paid')->sum('total'),
            'counts' => (clone $orders)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'topProducts' => DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->whereBetween('orders.created_at', [$from, $to])->where('orders.status', 'completed')->groupBy('order_items.product_id', 'order_items.product_name')->selectRaw('order_items.product_id, order_items.product_name, SUM(quantity) as quantity, SUM(order_items.total) as amount')->orderByDesc('quantity')->limit(20)->get(),
            'deposits' => DB::table('deposits')->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('amount'),
            'stock' => DB::table('product_keys')->selectRaw('status,COUNT(*) as total')->groupBy('status')->pluck('total','status'),
        ]);
    }
    public function exportOrders(Request $request)
    {
        Gate::authorize('manage-finance');
        [$from, $to] = $this->dates($request);
        AuditLog::record('report.orders_exported', null, ['from' => $from->toDateString(), 'to' => $to->toDateString()]);
        return $this->csv('orders.csv', ['Mã đơn', 'Khách hàng', 'Ngày tạo', 'Trạng thái', 'Tạm tính', 'Giảm giá', 'Tổng tiền'], function ($write) use ($from, $to) {
            DB::table('orders')->join('users', 'users.id', '=', 'orders.user_id')->whereBetween('orders.created_at', [$from, $to])->select('orders.*','users.email')->orderBy('orders.id')->chunk(500, function ($rows) use ($write) { foreach ($rows as $row) $write([$row->number,$row->email,$row->created_at,$row->status,$row->subtotal,$row->discount,$row->total]); });
        });
    }
    public function exportStock()
    {
        Gate::authorize('manage-finance');
        AuditLog::record('report.stock_exported');
        return $this->csv('inventory.csv', ['SKU','Sản phẩm','Chưa bán','Đã bán','Lỗi'], function ($write) {
            Product::withCount(['availableKeys', 'keys as sold_count' => fn ($q) => $q->where('status','sold'), 'keys as invalid_count' => fn ($q) => $q->where('status','invalid')])->orderBy('id')->chunk(500, function ($rows) use ($write) { foreach ($rows as $p) $write([$p->sku,$p->name,$p->available_keys_count,$p->sold_count,$p->invalid_count]); });
        });
    }
    private function csv(string $filename, array $headings, callable $rows)
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $file = fopen('php://output','w'); fwrite($file,"\xEF\xBB\xBF");
            $write = function (array $row) use ($file) {
                $row = array_map(fn ($cell) => is_string($cell) && preg_match('/^[=+@\-\t\r]/u',$cell) ? "'".$cell : $cell, $row);
                fputcsv($file,$row,',','"','');
            };
            $write($headings); $rows($write); fclose($file);
        }, $filename, ['Content-Type'=>'text/csv; charset=UTF-8', 'Cache-Control'=>'private, no-store']);
    }
    public function key(Request $request, ProductKey $key)
    {
        $data = $request->validate(['status'=>'required|in:available,invalid', 'reason'=>'required|string|min:5|max:400']);
        DB::transaction(function () use ($key,$data) {
            Product::whereKey($key->product_id)->lockForUpdate()->firstOrFail();
            $locked = ProductKey::whereKey($key->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->order_item_id !== null || $locked->status === 'sold',409,'Không đổi trạng thái key đã giao.');
            $locked->update(['status'=>$data['status']]);
            AuditLog::record('key.status_changed',$locked,['status'=>$data['status'],'reason'=>$data['reason']]);
        });
        return back()->with('success','Đã cập nhật trạng thái key.');
    }
    public function coupon(Coupon $coupon)
    {
        return view('admin.coupon-edit',['coupon'=>$coupon,'products'=>Product::orderBy('name')->get(),'categories'=>Category::orderBy('name')->get(),'usages'=>DB::table('coupon_usages')->join('users','users.id','=','coupon_usages.user_id')->where('coupon_id',$coupon->id)->select('coupon_usages.*','users.email')->latest('coupon_usages.created_at')->paginate(20)]);
    }
    public function updateCoupon(Request $request,Coupon $coupon)
    {
        $data=$request->validate([
            'type'=>'required|in:fixed,percent','value'=>'required|integer|min:1|max:1000000000',
            'minimum_amount'=>'required|integer|min:0|max:1000000000','maximum_discount'=>'nullable|integer|min:1|max:1000000000',
            'usage_limit'=>'nullable|integer|min:1|max:1000000','per_user_limit'=>'required|integer|min:1|max:1000000',
            'product_id'=>'nullable|exists:products,id','category_id'=>'nullable|exists:categories,id',
            'starts_at'=>'nullable|date','expires_at'=>'nullable|date|after_or_equal:starts_at','is_active'=>'nullable|boolean',
        ]);
        if($data['type']==='percent' && $data['value']>100) return back()->withErrors(['value'=>'Mức giảm không vượt 100%.']);
        $data['is_active']=$request->boolean('is_active');
        DB::transaction(function()use($data,$coupon){ Coupon::whereKey($coupon->id)->lockForUpdate()->firstOrFail()->update($data); AuditLog::record('coupon.updated',$coupon); });
        return back()->with('success','Đã lưu mã giảm giá. Thay đổi chỉ áp dụng cho giao dịch tiếp theo.');
    }
    public function user(User $user)
    {
        Gate::authorize('manage-finance');
        return view('admin.user-detail',['user'=>$user->load('wallet'),'orders'=>$user->orders()->latest()->paginate(10,['*'],'orders'),'deposits'=>$user->deposits()->latest()->paginate(10,['*'],'deposits'),'logins'=>AuditLog::where('user_id',$user->id)->whereIn('action',['auth.login','auth.admin_login'])->latest()->limit(20)->get()]);
    }
    public function updateUser(Request $request,User $user)
    {
        Gate::authorize('manage-finance');
        abort_if($user->id===$request->user()->id,403,'Không tự thay đổi quyền tài khoản đang dùng.');
        $data=$request->validate(['role'=>['required',Rule::in(['customer','staff','admin'])],'internal_note'=>'nullable|string|max:5000']);
        DB::transaction(function()use($data,$user){$user->forceFill($data)->save();AuditLog::record('user.permissions_updated',$user,['role'=>$data['role']]);});
        return back()->with('success','Đã lưu quyền và ghi chú nội bộ.');
    }
}
