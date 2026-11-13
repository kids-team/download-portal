<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTreeApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_for_an_unauthenticated_tree_request(): void
    {
        $this->getJson(route('v1.admin.documents.tree'))
            ->assertUnauthorized();
    }

    public function test_editor_can_retrieve_a_sorted_document_tree(): void
    {
        $firstRoot = Document::factory()->create(['title' => 'Erster Ordner', 'position' => 0]);
        $secondRoot = Document::factory()->create(['title' => 'Zweiter Ordner', 'position' => 1]);
        $child = Document::factory()->for($firstRoot, 'parent')->create(['title' => 'Unterseite', 'position' => 0]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->getJson(route('v1.admin.documents.tree'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $firstRoot->id)
            ->assertJsonPath('data.0.children.0.id', $child->id)
            ->assertJsonPath('data.1.id', $secondRoot->id);
    }

    public function test_editor_can_move_a_document_and_resequence_both_branches(): void
    {
        $oldParent = Document::factory()->create();
        $remainingSibling = Document::factory()->for($oldParent, 'parent')->create(['position' => 1]);
        $document = Document::factory()->for($oldParent, 'parent')->create(['position' => 0]);
        $newParent = Document::factory()->create();
        $existingChild = Document::factory()->for($newParent, 'parent')->create(['position' => 0]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->patchJson(route('v1.admin.documents.tree-position', $document), [
            'parent_id' => $newParent->id,
            'position' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.parent_id', $newParent->id)
            ->assertJsonPath('data.position', 1);

        $this->assertDatabaseHas('documents', [
            'id' => $remainingSibling->id,
            'parent_id' => $oldParent->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('documents', [
            'id' => $existingChild->id,
            'parent_id' => $newParent->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'parent_id' => $newParent->id,
            'position' => 1,
        ]);
    }

    public function test_returns_422_when_the_target_parent_is_a_descendant(): void
    {
        $root = Document::factory()->create();
        $child = Document::factory()->for($root, 'parent')->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->patchJson(route('v1.admin.documents.tree-position', $root), [
            'parent_id' => $child->id,
            'position' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_returns_422_when_a_move_payload_is_incomplete(): void
    {
        $document = Document::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->patchJson(route('v1.admin.documents.tree-position', $document), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id', 'position']);
    }

    public function test_returns_403_when_an_editor_moves_a_locked_document(): void
    {
        $document = Document::factory()->create(['is_locked' => true]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->patchJson(route('v1.admin.documents.tree-position', $document), [
            'parent_id' => null,
            'position' => 0,
        ])
            ->assertForbidden();
    }
}
