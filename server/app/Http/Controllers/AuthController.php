<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth');
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => ['required', 'confirmed', Password::min(12)]]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('studio');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::guard('web')->attempt([...$data, 'active' => true])) {
            throw ValidationException::withMessages(['email' => __('邮箱或密码不正确。')]);
        }
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended)) {
            $path = parse_url($intended, PHP_URL_PATH) ?: '/';
            $sameOrigin = str_starts_with($intended, $request->getSchemeAndHttpHost().'/');
            $relative = str_starts_with($intended, '/') && ! str_starts_with($intended, '//');
            if (($sameOrigin || $relative) && $path !== '/admin' && ! str_starts_with($path, '/admin/')) {
                return redirect($intended);
            }
        }

        return redirect('/studio');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
