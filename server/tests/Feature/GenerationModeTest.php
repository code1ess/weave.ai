<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GenerationModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_parameters_and_configuration_version_are_persisted_and_replays_are_checked(): void
    {
        $this->actingAs(User::factory()->create());
        $data = ['kind' => 'text_to_image', 'prompt' => 'Mountain', 'parameters' => ['aspect_ratio' => '16:9'], 'idempotency_key' => fake()->uuid()];
        $this->post('/tasks', $data)->assertRedirect();
        $task = DB::table('generation_tasks')->first();
        $this->assertSame('16:9', json_decode($task->parameters, true)['aspect_ratio']);
        $this->assertSame(1, $task->mode_version);
        $this->get('/works/'.$task->id)->assertInertia(fn (Assert $page) => $page->component('WorkDetail', false)->where('task.parameters.aspect_ratio', '16:9')->where('task.mode_version', 1));
        DB::table('generation_tasks')->where('id', $task->id)->update(['parameters' => json_encode(['duration_seconds' => null, 'aspect_ratio' => '16:9'])]);
        $this->post('/tasks', $data)->assertRedirect();
        $this->post('/tasks', [...$data, 'parameters' => ['aspect_ratio' => '1:1']])->assertSessionHasErrors('idempotency_key');
        $this->assertDatabaseCount('generation_tasks', 1);
    }

    public function test_pausing_a_mode_is_audited_blocks_new_requests_and_preserves_retries(): void
    {
        $user = User::factory()->create();
        $data = ['kind' => 'text_to_image', 'prompt' => 'Mountain', 'idempotency_key' => fake()->uuid()];
        $this->actingAs($user)->post('/tasks', $data)->assertRedirect();
        $admin = AdminUser::create(['name' => 'Ops', 'email' => 'ops@example.test', 'password' => 'secure-long-password', 'role' => 'operations', 'active' => true]);
        $this->actingAs($admin, 'admin')->put('/admin/generation-modes/text_to_image', ['active' => false, 'aspect_ratios' => ['1:1'], 'pause_message' => 'Temporarily paused', 'version' => 1, 'reason' => 'Maintenance'])->assertRedirect();
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'generation.mode_updated', 'subject' => 'text_to_image']);
        $this->actingAs($user, 'web')->post('/tasks', $data)->assertRedirect();
        $this->post('/tasks', [...$data, 'idempotency_key' => fake()->uuid()])->assertSessionHasErrors('kind');
        $this->assertDatabaseCount('generation_tasks', 1);
    }

    public function test_unsupported_parameters_stale_modes_and_pending_limits_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $data = ['kind' => 'text_to_image', 'prompt' => 'Mountain', 'idempotency_key' => fake()->uuid()];
        $this->post('/tasks', [...$data, 'parameters' => ['aspect_ratio' => '100:1']])->assertSessionHasErrors('parameters.aspect_ratio');
        $this->post('/tasks', [...$data, 'mode_version' => 99])->assertSessionHasErrors('mode_version');
        config(['generation.pending_limit' => 1]);
        $this->post('/tasks', $data)->assertRedirect();
        $this->post('/tasks', [...$data, 'idempotency_key' => fake()->uuid()])->assertSessionHasErrors('task');
        $this->post('/tasks', $data)->assertRedirect();
        $this->assertDatabaseCount('generation_tasks', 1);
    }
}
