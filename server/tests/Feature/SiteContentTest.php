<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): AdminUser
    {
        return AdminUser::create(['name' => 'Ops', 'email' => 'ops@example.test', 'password' => 'secure-long-password', 'role' => 'operations', 'active' => true]);
    }

    public function test_drafts_are_hidden_and_publication_is_versioned_and_audited(): void
    {
        $this->actingAs($this->operator(), 'admin');
        $data = ['slug' => 'getting-started', 'kind' => 'help', 'title' => 'Getting started', 'body' => 'Original help text', 'reason' => 'Initial help'];
        $this->post('/admin/content', $data)->assertRedirect();
        $id = DB::table('site_contents')->value('id');
        $this->get('/help/getting-started')->assertNotFound();
        $this->post('/admin/content/'.$id.'/publish', ['version' => 1, 'reason' => 'Publish help'])->assertRedirect();
        $this->get('/help/getting-started')->assertInertia(fn (Assert $page) => $page->component('HelpDetail', false)->where('article.body', 'Original help text'));
        $this->put('/admin/content/'.$id, ['title' => 'Getting started', 'body' => 'Draft revision', 'reason' => 'Revised help', 'version' => 1])->assertRedirect();
        $this->get('/help/getting-started')->assertInertia(fn (Assert $page) => $page->where('article.body', 'Original help text'));
        $this->post('/admin/content/'.$id.'/publish', ['version' => 1, 'reason' => 'Stale publish'])->assertSessionHasErrors('version');
        $this->post('/admin/content/'.$id.'/publish', ['version' => 2, 'reason' => 'Updated help'])->assertRedirect();
        $this->get('/help/getting-started')->assertInertia(fn (Assert $page) => $page->where('article.body', 'Draft revision'));
        $this->post('/admin/content/'.$id.'/withdraw', ['version' => 2, 'reason' => 'Withdraw help'])->assertRedirect();
        $this->get('/help/getting-started')->assertNotFound();
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'content.published', 'subject' => $id]);
        $this->assertDatabaseCount('site_content_versions', 2);
    }

    public function test_only_published_announcements_are_on_home_and_support_cannot_edit_content(): void
    {
        $admin = $this->operator();
        $this->actingAs($admin, 'admin')->post('/admin/content', ['slug' => 'preview', 'kind' => 'announcement', 'title' => 'Preview', 'body' => 'Service preview', 'reason' => 'Announce']);
        $id = DB::table('site_contents')->value('id');
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('announcements', 0));
        $this->post('/admin/content/'.$id.'/publish', ['version' => 1, 'reason' => 'Publish']);
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('announcements', 1)->where('announcements.0.title', 'Preview'));
        $admin->update(['role' => 'support']);
        $this->post('/admin/content', [])->assertForbidden();
    }
}
