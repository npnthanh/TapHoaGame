<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectAuthenticationContext
{
    public function handle(Request $request, Closure $next)
    {
        $customerGuard = Auth::guard('web');

        // Migrate sessions created before customer/admin authentication was separated.
        // Clear only the invalid customer identity; preserve a valid admin login.
        if ($customerGuard->check() && $customerGuard->user()->role !== 'customer') {
            $customerGuard->logoutCurrentDevice();
            $request->session()->forget(['url.intended', 'password_hash_web']);
            $request->session()->regenerate(true);
        }

        Auth::shouldUse($request->is('admin', 'admin/*') ? 'admin' : 'web');

        return $next($request);
    }
}
