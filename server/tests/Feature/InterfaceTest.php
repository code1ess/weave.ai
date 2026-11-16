<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_is_default_and_matches_the_reference_locale_list(): void
    {
        config(['app.debug' => false]);
        $this->withHeader('Accept-Language', 'ja')->get('/')->assertInertia(fn (Assert $page) => $page->where('ui.locale', 'en')->has('ui.locales', 17)->missing('ui.locales.zh')->where('ui.messages.我的创作', 'My creations'));
    }

    public function test_every_reference_locale_has_a_catalog_and_theme_is_rendered_in_html(): void
    {
        config(['app.debug' => false]);
        $expected = ['en', 'de', 'ja', 'ko', 'es', 'ar', 'fr', 'pt', 'id', 'hi', 'vi', 'nl', 'pl', 'tr', 'it', 'no', 'fil'];
        $this->assertSame($expected, array_keys(config('interface.locales')));
        foreach ($expected as $locale) {
            $catalog = json_decode(file_get_contents(lang_path($locale.'.json')), true, 512, JSON_THROW_ON_ERROR);
            $this->withCookie('locale', $locale)->get('/')->assertInertia(fn (Assert $page) => $page->where('ui.locale', $locale)->where('ui.messages.发现', $catalog['发现']));
        }
        $this->withCookie('theme', 'light')->get('/')->assertSee('data-theme="light"', false);
        $this->withCookie('theme', 'dark')->get('/')->assertSee('data-theme="dark"', false);
    }

    public function test_chinese_is_available_only_when_debug_is_enabled(): void
    {
        config(['app.debug' => false]);
        $this->withCookie('locale', 'zh')->get('/')->assertInertia(fn (Assert $page) => $page->where('ui.locale', 'en')->missing('ui.locales.zh'));
        $this->post('/preferences', ['locale' => 'zh'])->assertSessionHasErrors('locale');
        config(['app.debug' => true]);
        $this->withCookie('locale', 'zh')->get('/')->assertInertia(fn (Assert $page) => $page->where('ui.locale', 'zh')->where('ui.messages.我的创作', '我的创作')->has('ui.locales', 18));
    }

    public function test_admin_defaults_to_chinese_independently_of_user_locale_and_debug(): void
    {
        config(['app.debug' => false]);

        $this->withCookie('locale', 'en')->get('/admin/login')->assertOk()
            ->assertSee('lang="zh"', false)
            ->assertInertia(fn (Assert $page) => $page->where('ui.locale', 'zh')->where('ui.scope', 'admin')->where('ui.locales.zh', '简体中文')->where('ui.messages.管理员登录', '管理员登录'));
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('ui.locale', 'en'));
        $this->post('/preferences', ['scope' => 'admin', 'locale' => 'en'])->assertCookie('admin_locale', 'en')->assertCookieMissing('locale');
        $this->withCookie('admin_locale', 'en')->get('/admin/login')->assertInertia(fn (Assert $page) => $page->where('ui.locale', 'en'));
        $this->post('/preferences', ['scope' => 'admin', 'locale' => 'zh'])->assertCookie('admin_locale', 'zh');
    }

    public function test_locale_theme_and_rtl_are_applied_and_invalid_values_rejected(): void
    {
        config(['app.debug' => false]);
        $this->withCookie('locale', 'ar')->withCookie('theme', 'light')->get('/')->assertInertia(fn (Assert $page) => $page->where('ui.locale', 'ar')->where('ui.direction', 'rtl')->where('ui.theme', 'light')->where('ui.messages.我的创作', 'إبداعاتي'));
        $this->post('/preferences', ['theme' => 'light', 'locale' => 'ja'])->assertRedirect()->assertCookie('theme', 'light')->assertCookie('locale', 'ja');
        $this->post('/preferences', ['theme' => 'invalid', 'locale' => 'invalid'])->assertSessionHasErrors(['theme', 'locale']);
    }
}
