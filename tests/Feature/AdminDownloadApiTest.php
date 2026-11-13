<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Download;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDownloadApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_for_an_unauthenticated_download_upload(): void
    {
        $document = Document::factory()->create();

        $this->post(route('v1.admin.documents.downloads.store', $document), [
            'file' => UploadedFile::fake()->create('arbeitsblatt.pdf', 10, 'application/pdf'),
        ])->assertUnauthorized();
    }

    public function test_editor_can_upload_and_list_a_download_for_an_unlocked_document(): void
    {
        Storage::fake('local');
        $document = Document::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $response = $this->post(route('v1.admin.documents.downloads.store', $document), [
            'file' => UploadedFile::fake()->create('arbeitsblatt.pdf', 10, 'application/pdf'),
            'title' => 'Arbeitsblatt',
            'is_published' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.document_id', $document->id)
            ->assertJsonPath('data.original_filename', 'arbeitsblatt.pdf')
            ->assertJsonPath('data.title', 'Arbeitsblatt')
            ->assertJsonPath('data.disk', 'local')
            ->assertJsonPath('data.is_published', true);

        $download = Download::query()->findOrFail($response->json('data.id'));
        Storage::disk('local')->assertExists($download->path);

        $this->getJson(route('v1.admin.documents.downloads.index', $document))
            ->assertOk()
            ->assertJsonPath('data.0.id', $download->id);
    }

    public function test_returns_403_when_an_editor_uploads_to_a_locked_document(): void
    {
        $document = Document::factory()->create(['is_locked' => true]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->post(route('v1.admin.documents.downloads.store', $document), [
            'file' => UploadedFile::fake()->create('arbeitsblatt.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_admin_can_update_and_delete_a_download_with_its_file(): void
    {
        Storage::fake('local');
        $document = Document::factory()->create();
        $download = Download::factory()->for($document)->create(['disk' => 'local', 'path' => 'downloads/arbeitsblatt.pdf']);
        Storage::disk('local')->put($download->path, 'file content');
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->patchJson(route('v1.admin.documents.downloads.update', [$document, $download]), [
            'title' => 'Überarbeitetes Arbeitsblatt',
            'visibility' => 'private',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Überarbeitetes Arbeitsblatt')
            ->assertJsonPath('data.visibility', 'private');

        $this->deleteJson(route('v1.admin.documents.downloads.destroy', [$document, $download]))
            ->assertNoContent();

        $this->assertModelMissing($download);
        Storage::disk('local')->assertMissing('downloads/arbeitsblatt.pdf');
    }
}
