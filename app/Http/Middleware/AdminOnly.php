<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Chỉ cho tài khoản có role = admin đi qua */
class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Chỉ quản trị viên mới được truy cập.');

        return $next($request);
    }
}
