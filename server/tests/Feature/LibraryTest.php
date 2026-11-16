<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\GenerationTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_filters_and_detail_are_scoped_to_current_user(): void
    {
        $user = User::factory()->create();
        $task = GenerationTask::create(['user_id' => $user->id, 'kind' => 'text_to_image', 'prompt' => 'My task', 'status' => 'pending', 'idempotency_key' => fake()->uuid()]);
        GenerationTask::create(['user_id' => User::factory()->create()->id, 'kind' => 'text_to_image', 'prompt' => 'Other private task', 'status' => 'pending', 'idempotency_key' => fake()->uuid()]);
        $this->actingAs($user)->get('/works')->assertInertia(fn (Assert $page) => $page->component('Works', false)->has('tasks.data', 1)->where('tasks.data.0.id', $task->id));
        $this->get('/works?status=cancelled')->assertInertia(fn (Assert $page) => $page->has('tasks.data', 0));
        $this->get('/works/'.$task->id)->assertOk();
        $this->actingAs(User::factory()->create())->get('/works/'.$task->id)->assertNotFound();
    }

    public function test_unused_assets_are_deleted_idempotently_and_cannot_be_submitted(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $id = $this->actingAs($user)->postJson('/assets', ['file' => UploadedFile::fake()->image('photo.jpg')])->json('id');
        $asset = Asset::findOrFail($id);
        $this->get('/assets')->assertInertia(fn (Assert $page) => $page->component('Assets', false)->has('assets.data', 1));
        $this->delete('/assets/'.$id)->assertRedirect('/assets');
        $this->delete('/assets/'.$id)->assertRedirect('/assets');
        Storage::disk('local')->assertMissing($asset->path);
        $this->get('/assets/'.$id)->assertNotFound();
        $this->post('/tasks', ['kind' => 'image_to_image', 'prompt' => 'Edit', 'asset_id' => $id, 'idempotency_key' => fake()->uuid()])->assertSessionHasErrors('asset_id');
    }

    public function test_failed_storage_deletion_can_be_retried_and_blocks_new_tasks(): void
    {
        Storage::fake('local');
        $id = $this->actingAs(User::factory()->create())->postJson('/assets', ['file' => UploadedFile::fake()->image('photo.jpg')])->json('id');
        $asset = Asset::findOrFail($id);
        $disk = Storage::disk('local');
        $storage = \Mockery::mock($disk);
        $attempt = 0;
        $storage->shouldReceive('delete')->with($asset->path)->twice()->andReturnUsing(function ($path) use (&$attempt, $disk) {
            return ++$attempt === 1 ? false : $disk->delete($path);
        });
        Storage::shouldReceive('disk')->with('local')->andReturn($storage);
        $this->delete('/assets/'.$id)->assertSessionHasErrors('asset');
        $this->assertDatabaseHas('assets', ['id' => $id, 'status' => 'deleting']);
        $this->post('/tasks', ['kind' => 'image_to_image', 'prompt' => 'Edit', 'asset_id' => $id, 'idempotency_key' => fake()->uuid()])->assertSessionHasErrors('asset_id');
        $this->delete('/assets/'.$id)->assertRedirect('/assets');
        $this->assertDatabaseHas('assets', ['id' => $id, 'status' => 'deleted']);
        $disk->assertMissing($asset->path);
    }

    public function test_referenced_assets_and_other_users_assets_cannot_be_deleted(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $id = $this->actingAs($user)->postJson('/assets', ['file' => UploadedFile::fake()->image('photo.jpg')])->json('id');
        $this->post('/tasks', ['kind' => 'image_to_image', 'prompt' => 'Edit', 'asset_id' => $id, 'idempotency_key' => fake()->uuid()])->assertRedirect();
        $this->delete('/assets/'.$id)->assertSessionHasErrors('asset');
        $this->actingAs(User::factory()->create())->delete('/assets/'.$id)->assertNotFound();
        Storage::disk('local')->assertExists(Asset::findOrFail($id)->path);
    }
}
