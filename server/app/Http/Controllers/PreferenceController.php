<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function update(Request $request)
    {
        $admin = $request->input('scope') === 'admin';
        $data = $request->validate(['scope' => 'sometimes|in:admin,user', 'locale' => ['required_without:theme', 'string', Rule::in(array_keys(SetInterface::locales($admin)))], 'theme' => 'required_without:locale|in:light,dark']);
        unset($data['scope']);
        $response = back();
        foreach ($data as $name => $value) {
            $name = $admin && $name === 'locale' ? 'admin_locale' : $name;
            $response->withCookie(cookie($name, $value, 60 * 24 * 365, '/', null, $request->isSecure(), false, false, 'lax'));
        }

        return $response;
    }
}
