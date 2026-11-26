<?php

namespace App\Http\Controllers;

use App\Models\{Order, Deposit};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        return view('account.index', ['user' => $request->user()->load('wallet'), 'orderCount' => $request->user()->orders()->count()]);
    }
    public function profile(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'phone' => 'nullable|string|max:30']);
        $request->user()->update($data);
        return back()->with('success', 'Đã cập nhật thông tin.');
    }
    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()]]);
        $request->user()->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        $request->session()->regenerate();
        return back()->with('success', 'Đã đổi mật khẩu.');
    }
    public function orders(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:40']);
        return view('account.orders', ['orders' => $request->user()->orders()->when($request->filled('q'), fn ($q) => $q->where('number', 'like', '%'.$request->input('q').'%'))->latest()->paginate(15)->withQueryString()]);
    }
    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items.keys');
        return response()->view('account.order', compact('order'))->header('Cache-Control', 'private, no-store, max-age=0')->header('Pragma', 'no-cache');
    }
    public function wallet(Request $request)
    {
        $wallet = $request->user()->wallet;
        return view('account.wallet', [
            'wallet' => $wallet,
            'transactions' => $wallet ? $wallet->transactions()->latest()->paginate(15, ['*'], 'ledger') : collect(),
            'deposits' => $request->user()->deposits()->latest()->paginate(10, ['*'], 'deposits'),
        ]);
    }
    public function deposit(Request $request)
    {
        abort_unless(config('shop.payments.enabled') && strlen((string) config('shop.payments.secret')) >= 32 && config('shop.payments.account'), 503, 'Nạp tiền tự động chưa được cấu hình.');
        $data = $request->validate(['amount' => 'required|integer|min:10000|max:50000000']);
        Deposit::create(['user_id' => $request->user()->id, 'reference' => 'NAP'.Str::upper(Str::random(14)), 'amount' => $data['amount'], 'provider' => config('shop.payments.provider'), 'expires_at' => now()->addMinutes(30)]);
        return back()->with('success', 'Đã tạo yêu cầu. Chuyển đúng số tiền và nội dung được hiển thị bên dưới.');
    }
    public function support(Request $request)
    {
        return view('account.support', ['tickets' => DB::table('support_tickets')->where('user_id', $request->user()->id)->latest()->paginate(10)]);
    }
    public function ticket(Request $request)
    {
        $data = $request->validate(['subject' => 'required|string|max:200', 'message' => 'required|string|min:10|max:5000', 'order_number' => ['nullable', 'string', Rule::exists('orders', 'number')->where('user_id', $request->user()->id)]]);
        DB::table('support_tickets')->insert(['user_id' => $request->user()->id, 'order_id' => empty($data['order_number']) ? null : Order::where('number', $data['order_number'])->value('id'), 'subject' => $data['subject'], 'message' => $data['message'], 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Đã gửi yêu cầu hỗ trợ.');
    }
}
