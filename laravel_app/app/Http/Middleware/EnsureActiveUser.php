<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->user()?->is_blocked, 403, 'Tài khoản đã bị khóa. Vui lòng liên hệ hỗ trợ.');

        return $next($request);
    }
}
