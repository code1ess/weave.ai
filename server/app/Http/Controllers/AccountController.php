<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        $request->user()->update($data);

        return redirect('/settings');
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|current_password:web', 'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)]]);
        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        $request->session()->put('password_hash_web', $user->password);
        $request->session()->regenerate();

        return redirect('/settings');
    }
}
