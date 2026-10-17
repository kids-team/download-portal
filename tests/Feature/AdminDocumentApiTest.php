<?php

namespace Tests\Feature;

use App\DocumentStatus;
use App\Models\Audience;
use App\Models\Category;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Models\Tag;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDocumentApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_for_an_unauthenticated_document_request(): void
    {
        $this->getJson(route('v1.admin.documents.index'))
            ->assertUnauthorized();
    }

    public function test_editor_can_list_and_view_documents(): void
    {
        $tag = Tag::factory()->create();
        $document = Document::factory()->create(['title' => 'Erstes Dokument']);
        $document->tags()->attach($tag);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        Sanctum::actingAs($editor);

        $this->getJson(route('v1.admin.documents.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $document->id)
            ->assertJsonPath('data.0.tag_ids.0', $tag->id);

        $this->getJson(route('v1.admin.documents.show', $document))
            ->assertOk()
            ->assertJsonPath('data.id', $document->id)
            ->assertJsonPath('data.title', 'Erstes Dokument');
    }

    public function test_editor_can_create_a_document_with_taxonomy_and_tags(): void
    {
        $category = Category::factory()->create();
        $audience = Audience::factory()->create();
        $coverMedia = MediaAsset::factory()->create();
        $firstTag = Tag::factory()->create();
        $secondTag = Tag::factory()->create();
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        Sanctum::actingAs($editor);

        $response = $this->postJson(route('v1.admin.documents.store'), [
            'slug' => 'arbeitsblatt-schoepfung',
            'title' => 'Arbeitsblatt Schöpfung',
            'category_id' => $category->id,
            'audience_id' => $audience->id,
            'cover_media_id' => $coverMedia->id,
            'summary' => 'Material für den Unterricht.',
            'tag_ids' => [$firstTag->id, $secondTag->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'arbeitsblatt-schoepfung')
            ->assertJsonPath('data.status', DocumentStatus::Draft->value)
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.tag_ids.0', $firstTag->id)
            ->assertJsonPath('data.tag_ids.1', $secondTag->id);

        $documentId = $response->json('data.id');

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'title' => 'Arbeitsblatt Schöpfung',
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('document_tag', ['document_id' => $documentId, 'tag_id' => $firstTag->id]);
        $this->assertDatabaseHas('document_tag', ['document_id' => $documentId, 'tag_id' => $secondTag->id]);
    }

    public function test_returns_422_for_an_invalid_document_payload(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->postJson(route('v1.admin.documents.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'title']);
    }

    public function test_editor_cannot_publish_a_document(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->postJson(route('v1.admin.documents.store'), [
            'slug' => 'fertiges-arbeitsblatt',
            'title' => 'Fertiges Arbeitsblatt',
            'status' => DocumentStatus::Published->value,
            'published_at' => now()->toAtomString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_admin_can_publish_a_document(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $response = $this->postJson(route('v1.admin.documents.store'), [
            'slug' => 'freigegebenes-arbeitsblatt',
            'title' => 'Freigegebenes Arbeitsblatt',
            'status' => DocumentStatus::Published->value,
            'published_at' => '2026-10-17T06:00:00+00:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', DocumentStatus::Published->value);

        $this->assertDatabaseHas('documents', [
            'id' => $response->json('data.id'),
            'status' => DocumentStatus::Published->value,
        ]);
    }

    public function test_returns_422_when_a_slug_is_already_used_below_the_same_parent(): void
    {
        $parent = Document::factory()->create();
        Document::factory()->for($parent, 'parent')->create(['slug' => 'arbeitsblatt']);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->postJson(route('v1.admin.documents.store'), [
            'parent_id' => $parent->id,
            'slug' => 'arbeitsblatt',
            'title' => 'Noch ein Arbeitsblatt',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_editor_can_update_an_unlocked_document_but_cannot_lock_it(): void
    {
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $document = Document::factory()->create(['title' => 'Entwurf', 'is_locked' => false]);
        $document->tags()->attach($oldTag);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->patchJson(route('v1.admin.documents.update', $document), [
            'title' => 'Überarbeiteter Entwurf',
            'tag_ids' => [$newTag->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Überarbeiteter Entwurf')
            ->assertJsonPath('data.tag_ids.0', $newTag->id);

        $this->assertDatabaseHas('documents', ['id' => $document->id, 'title' => 'Überarbeiteter Entwurf']);
        $this->assertDatabaseMissing('document_tag', ['document_id' => $document->id, 'tag_id' => $oldTag->id]);

        $this->patchJson(route('v1.admin.documents.update', $document), ['is_locked' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_locked']);
    }

    public function test_returns_403_when_editor_updates_a_locked_document(): void
    {
        $document = Document::factory()->create(['is_locked' => true]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->patchJson(route('v1.admin.documents.update', $document), ['title' => 'Änderung'])
            ->assertForbidden();
    }

    public function test_admin_can_update_a_locked_document_and_manage_its_lock(): void
    {
        $document = Document::factory()->create(['is_locked' => true]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->patchJson(route('v1.admin.documents.update', $document), [
            'title' => 'Durch Administrator geändert',
            'is_locked' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Durch Administrator geändert')
            ->assertJsonPath('data.is_locked', false);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => 'Durch Administrator geändert',
            'is_locked' => false,
        ]);
    }

    public function test_returns_422_when_a_document_is_moved_below_its_descendant(): void
    {
        $root = Document::factory()->create();
        $child = Document::factory()->for($root, 'parent')->create();
        $grandchild = Document::factory()->for($child, 'parent')->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->patchJson(route('v1.admin.documents.update', $root), ['parent_id' => $grandchild->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_only_admin_can_delete_leaf_documents(): void
    {
        $document = Document::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->deleteJson(route('v1.admin.documents.destroy', $document))
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->deleteJson(route('v1.admin.documents.destroy', $document))
            ->assertNoContent();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_admin_cannot_delete_a_document_with_children(): void
    {
        $document = Document::factory()->create();
        Document::factory()->for($document, 'parent')->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->deleteJson(route('v1.admin.documents.destroy', $document))
            ->assertForbidden();

        $this->assertModelExists($document);
    }
}
