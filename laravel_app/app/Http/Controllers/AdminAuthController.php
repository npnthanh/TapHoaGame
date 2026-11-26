<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function loginForm() { return view('admin.auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|boolean']);
        $guard = Auth::guard('admin');
        if (! $guard->attempt([
            'email' => $data['email'], 'password' => $data['password'],
            'role' => ['admin', 'staff'], 'is_blocked' => false,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập quản trị không đúng hoặc tài khoản không có quyền truy cập.']);
        }

        $request->session()->regenerate(true);
        $request->session()->forget('password_hash_admin');
        Auth::shouldUse('admin');
        $guard->user()->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('auth.admin_login', $guard->user());

        $target = $request->session()->pull('admin.intended', '/admin');
        if (! is_string($target) || ($target !== '/admin' && ! str_starts_with($target, '/admin/'))) $target = '/admin';
        return redirect($target);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->forget(['admin.intended', 'password_hash_admin']);
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
