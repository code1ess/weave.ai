<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function rootView(Request $request): string
    {
        return $request->is('admin', 'admin/*') ? 'admin' : 'app';
    }

    public function share(Request $request): array
    {
        return [...parent::share($request), 'ui' => $request->attributes->get('ui'), 'admin' => $request->is('admin', 'admin/*') && $request->user('admin') ? [...$request->user('admin')->only('name', 'role'), 'permissions' => config('admin.roles.'.$request->user('admin')->role, [])] : null, 'auth' => ['user' => $request->user()?->only('id', 'name', 'email')]];
    }
}
