<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsrfWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_submission_retry_and_logout_with_csrf_enforced(): void
    {
        $user = User::factory()->create();
        Http::preventStrayRequests();
        Queue::fake();
        // Keep the isolated database configuration, but enable real forgery checks.
        $this->app['env'] = 'local';
        $this->get('/login')->assertOk();
        $login = ['email' => $user->email, 'password' => 'password'];
        $this->post('/login', $login)->assertStatus(419);
        $token = $this->app['session']->token();
        $this->post('/login', [...$login, '_token' => $token])->assertRedirect('/studio');
        $this->assertAuthenticatedAs($user);

        $task = ['kind' => 'text_to_image', 'prompt' => 'CSRF workflow test', 'idempotency_key' => fake()->uuid()];
        $this->post('/tasks', [...$task, '_token' => $token])->assertStatus(419);
        $token = $this->app['session']->token();
        $this->post('/tasks', [...$task, '_token' => $token])->assertRedirect('/studio');
        $this->post('/tasks', [...$task, '_token' => $token])->assertRedirect('/studio');
        $this->assertDatabaseCount('generation_tasks', 1);
        $this->assertDatabaseHas('generation_tasks', ['user_id' => $user->id, 'status' => 'pending']);
        Http::assertNothingSent();
        Queue::assertNothingPushed();

        Storage::fake('local');
        $page = $this->get('/studio')->assertOk();
        $xsrf = $page->getCookie('XSRF-TOKEN', false)->getValue();
        $asset = $this->withHeader('X-XSRF-TOKEN', $xsrf)->postJson('/assets', ['file' => UploadedFile::fake()->image('reference.jpg')])->assertCreated()->json('id');
        $this->get('/assets/'.$asset)->assertOk();
        $this->withHeader('X-XSRF-TOKEN', '');

        $this->post('/logout')->assertStatus(419);
        $this->post('/logout', ['_token' => $token])->assertRedirect('/');
        $this->assertGuest();
    }
}
