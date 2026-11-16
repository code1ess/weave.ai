<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_images_are_private_and_can_be_used_in_tasks(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $asset = $this->postJson('/assets', ['file' => UploadedFile::fake()->image('portrait.jpg')])->assertCreated()->json('id');
        $this->get('/assets/'.$asset)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/tasks', ['kind' => 'image_to_video', 'prompt' => 'Gentle motion', 'asset_id' => $asset, 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'])->assertRedirect('/studio');
        $this->assertDatabaseHas('generation_tasks', ['kind' => 'image_to_video', 'asset_id' => $asset, 'status' => 'pending']);
        $this->actingAs(User::factory()->create());
        $this->get('/assets/'.$asset)->assertNotFound();
        $this->post('/tasks', ['kind' => 'image_to_image', 'prompt' => 'Edit', 'asset_id' => $asset, 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'])->assertSessionHasErrors('asset_id');
    }

    public function test_r2_uploads_use_uuid_names_and_public_urls_after_metadata_authorization(): void
    {
        config(['assets.disk' => 'r2', 'filesystems.disks.r2.url' => 'https://media.example.test']);
        Storage::fake('r2');
        $user = User::factory()->create();
        $id = $this->actingAs($user)->postJson('/assets', ['file' => UploadedFile::fake()->image('personal-original.jpg')])->assertCreated()->json('id');
        $asset = Asset::findOrFail($id);
        $this->assertSame('r2', $asset->disk);
        $this->assertMatchesRegularExpression('/^assets\/[0-9a-f-]{36}\.jpg$/', $asset->path);
        $this->assertStringNotContainsString('personal-original', $asset->path);
        Storage::disk('r2')->assertExists($asset->path);
        $this->get('/assets/'.$id)->assertRedirect(Storage::disk('r2')->url($asset->path));
        $this->actingAs(User::factory()->create())->get('/assets/'.$id)->assertNotFound();
    }

    public function test_non_images_and_missing_required_assets_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->postJson('/assets', ['file' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml')])->assertUnprocessable();
        $this->post('/tasks', ['kind' => 'image_to_video', 'prompt' => 'Motion', 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'])->assertSessionHasErrors('asset_id');
        $this->assertDatabaseCount('generation_tasks', 0);
    }
}
