<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetInterface
{
    public static function locales(bool $admin = false): array
    {
        return [...config('interface.locales'), ...($admin ? ['zh' => '简体中文'] : (config('app.debug') ? ['zh' => '简体中文 · Debug'] : []))];
    }

    public function handle(Request $request, Closure $next)
    {
        $admin = $request->is('admin', 'admin/*');
        $locales = self::locales($admin);
        $defaultLocale = $admin ? 'zh' : 'en';
        $locale = $request->cookie($admin ? 'admin_locale' : 'locale', $defaultLocale);
        $locale = is_string($locale) && isset($locales[$locale]) ? $locale : $defaultLocale;
        $theme = $request->cookie('theme', 'dark');
        $theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'dark';
        app()->setLocale($locale);
        $english = json_decode(file_get_contents(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
        $path = lang_path($locale.'.json');
        $messages = is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : [];
        $request->attributes->set('ui', ['scope' => $admin ? 'admin' : 'user', 'locale' => $locale, 'locales' => $locales, 'theme' => $theme, 'direction' => $locale === 'ar' ? 'rtl' : 'ltr', 'messages' => [...$english, ...$messages]]);

        return $next($request);
    }
}
