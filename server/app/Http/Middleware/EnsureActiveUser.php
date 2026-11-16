<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('admin', 'admin/*') && Auth::guard('web')->check() && ! Auth::guard('web')->user()->active) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->regenerateToken();

            return $request->expectsJson() ? response()->json(['message' => __('账号已停用。')], 403) : redirect('/login');
        }

        return $next($request);
    }
}
