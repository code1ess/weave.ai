<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CreativeTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): AdminUser
    {
        return AdminUser::create(['name' => 'Ops', 'email' => 'ops@example.test', 'password' => 'secure-long-password', 'role' => 'operations', 'active' => true]);
    }

    private function createTemplate(): string
    {
        $this->actingAs($this->operator(), 'admin')->post('/admin/templates', ['title' => 'Mountain', 'description' => 'Landscape', 'kind' => 'text_to_image', 'prompt' => 'Misty mountain', 'category' => '风景', 'source_license' => 'Original editorial content', 'reason' => 'Initial template'])->assertRedirect();

        return CreativeTemplate::value('id');
    }

    private function transition(string $id, string $status): void
    {
        $this->post('/admin/templates/'.$id.'/status', ['status' => $status, 'version' => 1, 'reason' => 'Editorial review'])->assertRedirect();
    }

    public function test_public_catalog_only_exposes_published_templates(): void
    {
        $id = $this->createTemplate();
        $this->get('/templates')->assertInertia(fn (Assert $page) => $page->component('Templates', false)->has('templates.data', 0));
        $this->get('/templates/'.$id)->assertNotFound();
        $this->post('/admin/templates/'.$id.'/status', ['status' => 'published', 'version' => 1, 'reason' => 'Skip review'])->assertSessionHasErrors('status');
    }

    public function test_publication_is_versioned_audited_and_archived_templates_reject_new_tasks(): void
    {
        $id = $this->createTemplate();
        $this->transition($id, 'pending_review');
        $this->transition($id, 'published');
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'template.published', 'subject' => $id]);
        $this->get('/templates/'.$id)->assertOk();
        $this->actingAs(User::factory()->create(), 'web');
        $payload = ['kind' => 'text_to_image', 'prompt' => 'Misty mountain', 'template_id' => $id, 'template_version' => 1, 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'];
        $this->post('/tasks', $payload)->assertRedirect('/studio');
        $this->assertDatabaseHas('generation_tasks', ['template_id' => $id, 'template_version' => 1]);
        $this->transition($id, 'archived');
        $this->post('/tasks', $payload)->assertRedirect('/studio');
        $this->post('/tasks', [...$payload, 'idempotency_key' => 'c810f941-27a8-4a66-a477-4b70e1368096'])->assertSessionHasErrors('template_id');
        $this->assertDatabaseCount('generation_tasks', 1);
    }

    public function test_edits_preserve_old_versions_and_stale_submissions_are_rejected(): void
    {
        $id = $this->createTemplate();
        $this->transition($id, 'pending_review');
        $this->transition($id, 'published');
        $data = ['title' => 'New title', 'description' => 'New description', 'kind' => 'text_to_image', 'prompt' => 'Updated prompt', 'category' => '风景', 'source_license' => 'Original', 'reason' => 'Improve prompt', 'version' => 1];
        $this->put('/admin/templates/'.$id, $data)->assertRedirect('/admin/templates');
        $this->assertDatabaseHas('templates', ['id' => $id, 'status' => 'draft', 'current_version' => 2]);
        $this->assertDatabaseHas('template_versions', ['template_id' => $id, 'version' => 1, 'prompt' => 'Misty mountain']);
        $this->assertDatabaseHas('template_versions', ['template_id' => $id, 'version' => 2, 'prompt' => 'Updated prompt']);
        $this->put('/admin/templates/'.$id, $data)->assertSessionHasErrors('version');
        $this->assertDatabaseCount('template_versions', 2);
        $this->get('/templates/'.$id)->assertNotFound();
        $this->post('/admin/templates/'.$id.'/status', ['status' => 'pending_review', 'version' => 2, 'reason' => 'Review'])->assertRedirect();
        $this->post('/admin/templates/'.$id.'/status', ['status' => 'published', 'version' => 2, 'reason' => 'Publish'])->assertRedirect();
        $this->actingAs(User::factory()->create(), 'web')->post('/tasks', ['kind' => 'text_to_image', 'prompt' => 'Old prompt', 'template_id' => $id, 'template_version' => 1, 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'])->assertSessionHasErrors('template_id');
        $this->assertDatabaseCount('generation_tasks', 0);
    }

    public function test_support_cannot_manage_templates(): void
    {
        $admin = $this->operator();
        $admin->update(['role' => 'support']);
        $this->actingAs($admin, 'admin')->post('/admin/templates', [])->assertForbidden();
    }
}
