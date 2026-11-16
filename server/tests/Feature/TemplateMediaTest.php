<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_covers_tags_and_sort_order_are_published_with_template_versions(): void
    {
        Storage::fake('local');
        $admin = AdminUser::create(['name' => 'Ops', 'email' => 'ops@example.test', 'password' => 'secure-long-password', 'role' => 'operations', 'active' => true]);
        $media = $this->actingAs($admin, 'admin')->postJson('/admin/template-media', ['file' => UploadedFile::fake()->image('cover.jpg')])->assertCreated()->json('id');
        $data = ['title' => 'Landscape', 'description' => 'Mountain scene', 'kind' => 'text_to_image', 'prompt' => 'Mountain', 'category' => 'Scenes', 'source_license' => 'Original content', 'reason' => 'Initial template', 'cover_media_id' => $media, 'tags' => ['自然', '摄影'], 'sort_order' => 10];
        $this->post('/admin/templates', $data)->assertRedirect();
        $id = DB::table('templates')->value('id');
        $this->get('/templates/'.$id.'/cover')->assertNotFound();
        foreach (['pending_review', 'published'] as $status) {
            $this->post('/admin/templates/'.$id.'/status', ['status' => $status, 'version' => 1, 'reason' => 'Review'])->assertRedirect();
        }
        $this->get('/templates/'.$id.'/cover')->assertOk();
        $this->get('/templates?tag='.urlencode('自然'))->assertInertia(fn (Assert $page) => $page->component('Templates', false)->has('templates.data', 1)->where('templates.data.0.tags', ['自然', '摄影'])->where('templates.data.0.cover_url', '/templates/'.$id.'/cover'));
        $this->get('/templates?tag=unknown')->assertInertia(fn (Assert $page) => $page->has('templates.data', 0));
        $this->post('/admin/templates', [...$data, 'title' => 'First', 'sort_order' => 0])->assertRedirect();
        $second = DB::table('templates')->where('id', '!=', $id)->value('id');
        foreach (['pending_review', 'published'] as $status) {
            $this->post('/admin/templates/'.$second.'/status', ['status' => $status, 'version' => 1, 'reason' => 'Review']);
        }
        $this->get('/templates')->assertInertia(fn (Assert $page) => $page->where('templates.data.0.title', 'First'));
        $admin->update(['role' => 'support']);
        $this->postJson('/admin/template-media', ['file' => UploadedFile::fake()->image('blocked.jpg')])->assertForbidden();
        $this->get('/admin/template-media/'.$media)->assertForbidden();
    }
}
