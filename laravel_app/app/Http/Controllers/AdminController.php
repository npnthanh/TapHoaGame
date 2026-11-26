<?php

namespace App\Http\Controllers;

use App\Models\{User, Product, Category, ProductKey, Order, Deposit, AuditLog, Coupon};
use App\Services\{KeyImportService, WalletService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $paid = fn () => Order::where('status', 'completed')->where('payment_status', 'paid');
        $revenue = $paid()->sum('total');
        $today = $paid()->whereDate('paid_at', today())->sum('total');
        $week = $paid()->where('paid_at', '>=', now()->startOfWeek())->sum('total');
        $month = $paid()->where('paid_at', '>=', now()->startOfMonth())->sum('total');
        $chart = collect(range(6, 0))->map(function ($days) use ($paid) {
            $date = today()->subDays($days);
            return ['label' => $date->format('d/m'), 'total' => (int) $paid()->whereDate('paid_at', $date)->sum('total')];
        });
        return view('admin.dashboard', compact('revenue', 'today', 'week', 'month', 'chart') + [
            'orders' => Order::with('user')->latest()->limit(8)->get(),
            'counts' => ['Đơn hàng' => Order::count(), 'Khách hàng' => User::where('role', 'customer')->count(), 'Sản phẩm đang bán' => Product::visible()->count(), 'Key còn hàng' => ProductKey::where('status', 'available')->count()],
            'statuses' => Order::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'depositCount' => Deposit::where('status', 'paid')->count(),
        ]);
    }
    public function products(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:150']);
        return view('admin.products', ['products' => Product::with('category')->withCount('availableKeys')->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))->latest()->paginate(20)->withQueryString(), 'categories' => Category::orderBy('sort_order')->get()]);
    }
    public function createProduct() { return view('admin.product-form', ['product' => new Product, 'categories' => Category::orderBy('name')->get()]); }
    public function editProduct(Product $product) { return view('admin.product-form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]); }
    public function saveProduct(Request $request, ?Product $product = null)
    {
        $product ??= new Product;
        $data = $request->validate([
            'name' => 'required|string|max:200', 'sku' => ['required', 'string', 'max:80', Rule::unique('products')->ignore($product)],
            'slug' => ['required', 'alpha_dash:ascii', 'max:200', Rule::unique('products')->ignore($product)],
            'category_id' => 'required|exists:categories,id', 'platform' => 'required|string|max:60', 'region' => 'required|string|max:80',
            'price' => 'required|integer|min:1000|max:1000000000', 'original_price' => 'nullable|integer|gte:price|max:1000000000',
            'image_url' => ['nullable', 'string', 'max:2048', new \App\Rules\PublicAssetUrl], 'description' => 'nullable|string|max:20000', 'instructions' => 'nullable|string|max:20000',
            'is_active' => 'nullable|boolean', 'is_featured' => 'nullable|boolean', 'sort_order' => 'required|integer|min:0|max:100000',
            'seo_title' => 'nullable|string|max:255', 'seo_description' => 'nullable|string|max:500',
            'image_upload' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        unset($data['image_upload']);
        if ($request->hasFile('image_upload')) {
            $directory = public_path('uploads/products');
            \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
            $file = $request->file('image_upload');
            $filename = \Illuminate\Support\Str::uuid().'.'.$file->extension();
            $file->move($directory, $filename);
            $data['image_url'] = '/uploads/products/'.$filename;
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        DB::transaction(function () use ($product, $data) { $product->fill($data)->save(); AuditLog::record('product.saved', $product); });
        return redirect()->route('admin.products')->with('success', 'Đã lưu sản phẩm.');
    }
    public function archiveProduct(Product $product)
    {
        DB::transaction(function () use ($product) { $product->update(['is_active' => false]); AuditLog::record('product.archived', $product); });
        return back()->with('success', 'Đã ngừng bán sản phẩm, giữ nguyên lịch sử đơn hàng.');
    }
    public function category(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'slug' => 'required|alpha_dash:ascii|max:150|unique:categories']);
        DB::transaction(function () use ($data) { $category = Category::create($data); AuditLog::record('category.created', $category); });
        return back()->with('success', 'Đã tạo danh mục.');
    }
    public function keys(Request $request)
    {
        $data = $request->validate(['product_id' => 'nullable|integer|exists:products,id', 'status' => ['nullable', Rule::in(['available', 'sold', 'invalid'])]]);
        return view('admin.keys', ['products' => Product::orderBy('name')->get(), 'keys' => ProductKey::with('product')->when($data['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->latest()->paginate(25)->withQueryString()]);
    }
    public function importKeys(Request $request, KeyImportService $import)
    {
        $data = $request->validate(['product_id' => 'required|exists:products,id', 'keys' => 'nullable|string|max:4000000', 'file' => 'nullable|file|mimes:txt,csv|max:4096']);
        $lines = preg_split('/\r\n|\r|\n/', $data['keys'] ?? '');
        if ($request->hasFile('file')) {
            $handle = fopen($request->file('file')->getRealPath(), 'r');
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($row) !== 1) { fclose($handle); return back()->withErrors(['file' => 'CSV chỉ gồm một cột key, không có tiêu đề.']); }
                $lines[] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0] ?? '');
                if (count($lines) > 2001) { fclose($handle); return back()->withErrors(['file' => 'Tối đa 2.000 dòng key mỗi lượt nhập.']); }
            }
            fclose($handle);
        }
        try { $count = $import->import(Product::findOrFail($data['product_id']), $lines, $request->user()); }
        catch (\Illuminate\Database\UniqueConstraintViolationException) { return back()->withErrors(['keys' => 'Có key trùng vừa được nhập bởi người khác. Chưa nhập key nào.']); }
        return back()->with('success', "Đã nhập {$count} key và mã hóa trong cơ sở dữ liệu.");
    }
    public function orders(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:150', 'status' => ['nullable', Rule::in(['completed', 'pending', 'failed', 'refunded'])]]);
        $orders = Order::with('user')->when($data['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhereHas('user', fn ($q) => $q->where('email', 'like', '%'.$search.'%'))))->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->latest()->paginate(25)->withQueryString();
        return view('admin.orders', compact('orders'));
    }
    public function order(Order $order) { $order->load(['user', 'items.keys']); return view('admin.order', compact('order')); }
    public function refund(Request $request, Order $order, WalletService $wallet)
    {
        Gate::authorize('manage-finance');
        $data = $request->validate(['reason' => 'required|string|min:10|max:400']);
        DB::transaction(function () use ($order, $wallet, $data, $request) {
            $user = User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $current = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($current->status === 'refunded') return;
            abort_unless($current->status === 'completed' && $current->payment_status === 'paid', 409);
            $wallet->change($user, $current->total, 'refund:'.$current->number, 'refund', $data['reason'], ['order_id' => $current->id, 'actor_id' => $request->user()->id]);
            $current->update(['status' => 'refunded', 'payment_status' => 'refunded']);
            AuditLog::record('order.refunded', $current, ['reason' => $data['reason']]);
        }, 3);
        return back()->with('success', 'Đã hoàn tiền vào ví. Key đã giao không quay lại kho bán.');
    }
    public function users(Request $request)
    {
        Gate::authorize('manage-finance');
        $request->validate(['q' => 'nullable|string|max:150']);
        return view('admin.users', ['users' => User::with('wallet')->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('email', 'like', '%'.$request->q.'%')->orWhere('name', 'like', '%'.$request->q.'%')))->latest()->paginate(20)->withQueryString()]);
    }
    public function adjustWallet(Request $request, User $user, WalletService $wallet)
    {
        Gate::authorize('manage-finance');
        $data = $request->validate(['amount' => 'required|integer|not_in:0|min:-50000000|max:50000000', 'reason' => 'required|string|min:10|max:400', 'reference' => 'required|uuid']);
        DB::transaction(function () use ($user, $wallet, $data, $request) {
            $entry = $wallet->change($user, (int) $data['amount'], 'adjust:'.$data['reference'], 'adjustment', $data['reason'], ['actor_id' => $request->user()->id]);
            AuditLog::record('wallet.adjusted', $entry, ['reason' => $data['reason'], 'amount' => (int) $data['amount']]);
        }, 3);
        return back()->with('success', 'Đã ghi nhận điều chỉnh số dư.');
    }
    public function block(Request $request, User $user)
    {
        Gate::authorize('manage-finance');
        abort_if($user->id === $request->user()->id || $user->role === 'admin', 403, 'Không khóa tài khoản quản trị.');
        $request->validate(['blocked' => 'required|boolean']);
        DB::transaction(function () use ($request, $user) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['is_blocked' => $request->boolean('blocked')])->save();
            AuditLog::record('user.status_changed', $locked, ['is_blocked' => $locked->is_blocked]);
        });
        return back()->with('success', 'Đã cập nhật trạng thái tài khoản.');
    }
    public function finance()
    {
        Gate::authorize('manage-finance');
        return view('admin.finance', ['deposits' => Deposit::latest()->paginate(25, ['*'], 'deposits'), 'logs' => AuditLog::latest()->paginate(25, ['*'], 'logs')]);
    }
    public function coupons() { return view('admin.coupons', ['coupons' => Coupon::latest()->paginate(20)]); }
    public function createCoupon(Request $request)
    {
        $request->merge(['code' => mb_strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate(['code' => 'required|alpha_dash:ascii|max:50|unique:coupons', 'type' => 'required|in:fixed,percent', 'value' => 'required|integer|min:1|max:1000000000', 'minimum_amount' => 'required|integer|min:0|max:1000000000', 'usage_limit' => 'required|integer|min:1|max:1000000', 'expires_at' => 'required|date|after:now']);
        if ($data['type'] === 'percent' && $data['value'] > 100) return back()->withErrors(['value' => 'Mức giảm không vượt quá 100%.']);
        DB::transaction(function () use ($data) { $coupon = Coupon::create($data); AuditLog::record('coupon.created', $coupon); });
        return back()->with('success', 'Đã tạo mã giảm giá.');
    }
    public function support() { return view('admin.support', ['tickets' => DB::table('support_tickets')->join('users', 'users.id', '=', 'support_tickets.user_id')->select('support_tickets.*', 'users.email')->orderByDesc('support_tickets.created_at')->paginate(20)]); }
    public function reply(Request $request, int $ticket)
    {
        $data = $request->validate(['admin_reply' => 'required|string|min:5|max:5000', 'status' => 'required|in:open,closed']);
        abort_unless(DB::table('support_tickets')->where('id', $ticket)->exists(), 404);
        DB::transaction(function () use ($data, $ticket) { DB::table('support_tickets')->where('id', $ticket)->update($data + ['updated_at' => now()]); AuditLog::record('support.replied', null, ['ticket_id' => $ticket]); });
        return back()->with('success', 'Đã phản hồi yêu cầu.');
    }
}
