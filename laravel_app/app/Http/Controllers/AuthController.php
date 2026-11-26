<?php

namespace App\Http\Controllers;

use App\Models\{User, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Password};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm() { return view('auth.login'); }
    public function registerForm() { return view('auth.register'); }
    public function forgotForm() { return view('auth.forgot'); }
    public function resetForm(Request $request, string $token) { return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]); }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users,email', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            $user->wallet()->create();
            return $user;
        });
        Auth::guard('web')->login($user);
        $request->session()->forget('password_hash_web');
        $request->session()->regenerate();
        return redirect()->route('account')->with('success', 'Tạo tài khoản thành công.');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|boolean']);
        if (! Auth::guard('web')->attempt(['email' => $data['email'], 'password' => $data['password'], 'role' => 'customer', 'is_blocked' => false], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không đúng hoặc tài khoản đã bị khóa.']);
        }
        $request->session()->regenerate();
        Auth::shouldUse('web');
        $request->session()->forget('password_hash_web');
        $request->user()->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('auth.login', $request->user());
        // Old sessions may still contain an admin destination. Only return to
        // customer pages, and always use a local path rather than an external URL.
        $intended = $request->session()->pull('url.intended');
        $parts = is_string($intended) ? parse_url($intended) : false;
        $path = is_array($parts) ? ($parts['path'] ?? '') : '';
        $allowed = in_array($path, ['/tai-khoan', '/don-hang', '/vi', '/ho-tro', '/gio-hang'], true)
            || str_starts_with($path, '/don-hang/');

        return $allowed
            ? redirect($path.(isset($parts['query']) ? '?'.$parts['query'] : ''))
            : redirect()->route('account');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->forget(['cart', 'checkout_token', 'url.intended', 'password_hash_web']);
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        return back()->with('success', 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi đến bạn.');
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new \Illuminate\Auth\Events\PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) return back()->withErrors(['email' => 'Liên kết không hợp lệ hoặc đã hết hạn.']);
        return redirect()->route('login')->with('success', 'Đã đặt lại mật khẩu. Bạn có thể đăng nhập.');
    }
}
