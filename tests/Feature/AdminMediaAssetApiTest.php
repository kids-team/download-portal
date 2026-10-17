<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminMediaAssetApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_for_an_unauthenticated_media_upload(): void
    {
        $this->post(route('v1.admin.media-assets.store'), [
            'file' => UploadedFile::fake()->image('cover.png'),
        ])
            ->assertUnauthorized();
    }

    public function test_editor_can_upload_and_view_an_image(): void
    {
        Storage::fake('public');
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        Sanctum::actingAs($editor);

        $response = $this->post(route('v1.admin.media-assets.store'), [
            'file' => UploadedFile::fake()->image('cover.png', 800, 600),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.original_filename', 'cover.png')
            ->assertJsonPath('data.mime_type', 'image/png')
            ->assertJsonPath('data.disk', 'public')
            ->assertJsonPath('data.visibility', 'public')
            ->assertJsonPath('data.width', 800)
            ->assertJsonPath('data.height', 600);

        $mediaAsset = MediaAsset::query()->findOrFail($response->json('data.id'));

        Storage::disk('public')->assertExists($mediaAsset->path);

        $this->getJson(route('v1.admin.media-assets.show', $mediaAsset))
            ->assertOk()
            ->assertJsonPath('data.id', $mediaAsset->id);
    }

    public function test_returns_422_for_a_non_image_upload(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->post(route('v1.admin.media-assets.store'), [
            'file' => UploadedFile::fake()->create('notiz.txt', 10, 'text/plain'),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_only_admin_can_change_the_visibility_of_a_media_asset(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $mediaAsset = MediaAsset::factory()->create(['path' => 'media/cover.png']);
        Storage::disk('public')->put($mediaAsset->path, 'image content');
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->patchJson(route('v1.admin.media-assets.update', $mediaAsset), ['visibility' => 'private'])
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->patchJson(route('v1.admin.media-assets.update', $mediaAsset), ['visibility' => 'private'])
            ->assertOk()
            ->assertJsonPath('data.disk', 'local')
            ->assertJsonPath('data.visibility', 'private');

        Storage::disk('public')->assertMissing($mediaAsset->path);
        Storage::disk('local')->assertExists($mediaAsset->path);
        $this->assertDatabaseHas('media_assets', [
            'id' => $mediaAsset->id,
            'disk' => 'local',
            'visibility' => 'private',
        ]);
    }

    public function test_only_admin_can_delete_a_media_asset_and_its_file(): void
    {
        Storage::fake('public');
        $mediaAsset = MediaAsset::factory()->create(['path' => 'media/cover.png']);
        Storage::disk('public')->put($mediaAsset->path, 'image content');
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->deleteJson(route('v1.admin.media-assets.destroy', $mediaAsset))
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->deleteJson(route('v1.admin.media-assets.destroy', $mediaAsset))
            ->assertNoContent();

        $this->assertModelMissing($mediaAsset);
        Storage::disk('public')->assertMissing('media/cover.png');
    }

    public function test_media_asset_policy_allows_editors_to_upload_but_only_admins_to_manage_visibility_and_deletion(): void
    {
        $mediaAsset = MediaAsset::factory()->create();
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue($editor->can('viewAny', MediaAsset::class));
        $this->assertTrue($editor->can('create', MediaAsset::class));
        $this->assertFalse($editor->can('update', $mediaAsset));
        $this->assertFalse($editor->can('delete', $mediaAsset));
        $this->assertTrue($admin->can('update', $mediaAsset));
        $this->assertTrue($admin->can('delete', $mediaAsset));

        $this->assertTrue($admin->can('create', MediaAsset::class));
    }
}
