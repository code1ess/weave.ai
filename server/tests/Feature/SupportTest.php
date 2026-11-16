<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\GenerationTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_task_can_be_cancelled_once_without_financial_changes(): void
    {
        $user = User::factory()->create();
        $task = GenerationTask::create(['user_id' => $user->id, 'kind' => 'text_to_image', 'prompt' => 'Mountain', 'status' => 'pending', 'idempotency_key' => fake()->uuid()]);
        $this->actingAs(User::factory()->create())->post('/tasks/'.$task->id.'/cancel')->assertNotFound();
        $this->actingAs($user)->post('/tasks/'.$task->id.'/cancel')->assertRedirect();
        $this->post('/tasks/'.$task->id.'/cancel')->assertRedirect();
        $this->assertDatabaseHas('generation_tasks', ['id' => $task->id, 'status' => 'cancelled']);
        $this->assertDatabaseCount('task_events', 1);
        $this->assertDatabaseCount('wallet_ledger', 0);
        $task->update(['status' => 'processing']);
        $this->post('/tasks/'.$task->id.'/cancel')->assertSessionHasErrors('task');
    }

    public function test_tickets_and_replies_are_private_idempotent_and_audited(): void
    {
        $user = User::factory()->create();
        $payload = ['subject' => 'Need help', 'body' => 'How can I submit?', 'idempotency_key' => fake()->uuid()];
        $this->actingAs($user)->post('/support', $payload)->assertRedirect();
        $this->post('/support', $payload)->assertRedirect();
        $this->assertDatabaseCount('support_tickets', 1);
        $this->post('/support', [...$payload, 'subject' => 'Changed'])->assertSessionHasErrors('idempotency_key');
        $id = DB::table('support_tickets')->value('id');
        $this->get('/support/'.$id)->assertOk();
        $this->actingAs(User::factory()->create())->get('/support/'.$id)->assertNotFound();
        $this->post('/support/'.$id.'/messages', ['body' => 'Intrusion', 'idempotency_key' => fake()->uuid()])->assertNotFound();
        $admin = AdminUser::create(['name' => 'Support', 'email' => 'support@example.test', 'password' => 'secure-long-password', 'role' => 'support', 'active' => true]);
        $reply = ['body' => 'Use the studio', 'status' => 'resolved', 'idempotency_key' => fake()->uuid()];
        $this->actingAs($admin, 'admin')->get('/admin/support/'.$id)->assertOk();
        $this->post('/admin/support/'.$id.'/messages', $reply)->assertRedirect();
        $this->post('/admin/support/'.$id.'/messages', $reply)->assertRedirect();
        $this->assertDatabaseCount('support_messages', 1);
        $this->assertDatabaseHas('support_tickets', ['id' => $id, 'status' => 'resolved']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'support.replied', 'subject' => $id]);
        $this->actingAs($user, 'web')->post('/support/'.$id.'/messages', ['body' => 'One more question', 'idempotency_key' => fake()->uuid()])->assertRedirect();
        $this->assertDatabaseHas('support_tickets', ['id' => $id, 'status' => 'open']);
        $this->actingAs($admin, 'admin')->post('/admin/support/'.$id.'/messages', $reply)->assertRedirect();
        $this->assertDatabaseHas('support_tickets', ['id' => $id, 'status' => 'open']);
        $this->assertDatabaseCount('support_messages', 2);
    }

    public function test_ticket_cannot_reference_another_users_task_and_operations_has_no_support_access(): void
    {
        $task = GenerationTask::create(['user_id' => User::factory()->create()->id, 'kind' => 'text_to_image', 'prompt' => 'Private', 'status' => 'pending', 'idempotency_key' => fake()->uuid()]);
        $this->actingAs(User::factory()->create())->post('/support', ['subject' => 'Other task', 'body' => 'Help', 'task_id' => $task->id, 'idempotency_key' => fake()->uuid()])->assertSessionHasErrors('task_id');
        $admin = AdminUser::create(['name' => 'Ops', 'email' => 'ops@example.test', 'password' => 'secure-long-password', 'role' => 'operations', 'active' => true]);
        $this->actingAs($admin, 'admin')->get('/admin/support')->assertForbidden();
    }
}
