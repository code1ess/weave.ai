<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerationTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_is_persisted_once_without_model_calls(): void
    {
        Http::preventStrayRequests();
        Queue::fake();
        $user = User::factory()->create();
        $payload = ['kind' => 'text_to_image', 'prompt' => 'A mountain at sunrise', 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'];

        $this->actingAs($user)->post('/tasks', $payload)->assertRedirect('/studio');
        $this->post('/tasks', $payload)->assertRedirect('/studio');

        $this->assertDatabaseCount('generation_tasks', 1);
        $this->assertDatabaseHas('generation_tasks', ['user_id' => $user->id, 'status' => 'pending', 'prompt' => $payload['prompt']]);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_reusing_key_with_changed_input_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = ['kind' => 'text_to_image', 'prompt' => 'A mountain', 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'];
        $this->post('/tasks', $payload)->assertRedirect();
        $this->post('/tasks', [...$payload, 'prompt' => 'Different'])->assertSessionHasErrors('idempotency_key');
        $this->assertDatabaseCount('generation_tasks', 1);
    }

    public function test_task_details_are_private_and_guests_cannot_submit(): void
    {
        $this->post('/tasks', [])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post('/tasks', ['kind' => 'text_to_image', 'prompt' => 'Private', 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096']);
        $id = DB::table('generation_tasks')->value('id');
        $this->actingAs(User::factory()->create())->get('/tasks/'.$id)->assertNotFound();
    }
}
