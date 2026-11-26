<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vui lòng đăng nhập quản trị.'], 401);
            }
            if ($request->isMethod('GET')) {
                $request->session()->put('admin.intended', $request->getRequestUri());
            }
            return redirect()->route('admin.login');
        }

        Auth::shouldUse('admin');
        return $next($request);
    }
}
