<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private function reviewer(): AdminUser
    {
        return AdminUser::create(['name' => 'Reviewer', 'email' => 'review@example.test', 'password' => 'secure-long-password', 'role' => 'reviewer', 'active' => true]);
    }

    public function test_rejection_is_audited_idempotent_and_identical_resubmission_is_blocked(): void
    {
        $user = User::factory()->create();
        $payload = ['kind' => 'text_to_image', 'prompt' => 'Sensitive request', 'idempotency_key' => fake()->uuid()];
        $this->actingAs($user)->post('/tasks', $payload)->assertRedirect();
        $id = DB::table('generation_tasks')->value('id');
        $review = ['decision' => 'rejected', 'reason' => 'Unsupported input', 'idempotency_key' => fake()->uuid()];
        $this->actingAs($this->reviewer(), 'admin')->post('/admin/tasks/'.$id.'/review', $review)->assertRedirect();
        $this->post('/admin/tasks/'.$id.'/review', $review)->assertRedirect();
        $this->assertDatabaseCount('moderation_reviews', 1);
        $this->assertDatabaseHas('generation_tasks', ['id' => $id, 'status' => 'rejected', 'moderation_status' => 'rejected']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'task.reviewed', 'subject' => $id]);
        $this->actingAs($user, 'web')->post('/tasks', [...$payload, 'idempotency_key' => fake()->uuid()])->assertSessionHasErrors('prompt');
        $this->post('/tasks', $payload)->assertRedirect();
        $this->assertDatabaseCount('generation_tasks', 1);
        $this->assertDatabaseCount('wallet_ledger', 0);
    }

    public function test_approval_does_not_dispatch_and_conflicting_review_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post('/tasks', ['kind' => 'text_to_image', 'prompt' => 'Mountain', 'idempotency_key' => fake()->uuid()]);
        $id = DB::table('generation_tasks')->value('id');
        $this->actingAs($this->reviewer(), 'admin')->post('/admin/tasks/'.$id.'/review', ['decision' => 'approved', 'reason' => 'Input reviewed', 'idempotency_key' => fake()->uuid()])->assertRedirect();
        $this->assertDatabaseHas('generation_tasks', ['id' => $id, 'status' => 'pending', 'moderation_status' => 'approved']);
        $this->post('/admin/tasks/'.$id.'/review', ['decision' => 'rejected', 'reason' => 'Changed', 'idempotency_key' => fake()->uuid()])->assertSessionHasErrors('decision');
    }

    public function test_task_metadata_permission_does_not_allow_review_or_private_input_access(): void
    {
        $this->actingAs(User::factory()->create())->post('/tasks', ['kind' => 'text_to_image', 'prompt' => 'Private prompt', 'idempotency_key' => fake()->uuid()]);
        $id = DB::table('generation_tasks')->value('id');
        $staff = $this->reviewer();
        $staff->update(['role' => 'operations']);
        $this->actingAs($staff, 'admin')->get('/admin/tasks/'.$id)->assertOk()->assertDontSee('Private prompt');
        $this->get('/admin/tasks/'.$id.'/input')->assertForbidden();
        $this->post('/admin/tasks/'.$id.'/review', [])->assertForbidden();
    }
}
