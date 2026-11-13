<?php

namespace Tests\Feature;

use App\Models\BibleReference;
use App\Models\Document;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminBibleReferenceApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_editor_can_manage_document_bible_references(): void
    {
        $document = Document::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $created = $this->postJson(route('v1.admin.documents.bible-references.store', $document), [
            'translation' => 'de',
            'book_slug' => 'john',
            'start_chapter' => 3,
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.translation', 'de')
            ->assertJsonPath('data.book_slug', 'john')
            ->assertJsonPath('data.api_path', 'john/3');

        $referenceId = $created->json('data.id');

        $this->getJson(route('v1.admin.documents.bible-references.index', $document))
            ->assertOk()
            ->assertJsonPath('data.0.id', $referenceId);

        $this->deleteJson(route('v1.admin.documents.bible-references.destroy', [$document, $referenceId]))
            ->assertNoContent();

        $this->assertDatabaseMissing('bible_references', ['id' => $referenceId]);
    }

    public function test_editor_cannot_delete_a_reference_from_another_document(): void
    {
        $document = Document::factory()->create();
        $reference = BibleReference::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->deleteJson(route('v1.admin.documents.bible-references.destroy', [$document, $reference]))
            ->assertNotFound();
    }

    public function test_editor_can_load_the_bible_catalog_through_laravel(): void
    {
        config()->set('services.bible_api.url', 'http://bible-api:8080');
        Http::fake([
            'http://bible-api:8080/books?translation=de' => Http::response([
                'translation' => 'de',
                'books' => [['id' => 430, 'short_name' => 'john', 'long_name' => 'Johannes', 'chapters' => 21]],
            ]),
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->getJson(route('v1.admin.bible.books', ['translation' => 'de']))
            ->assertOk()
            ->assertJsonPath('data.books.0.long_name', 'Johannes');
    }

    public function test_editor_can_load_bible_verses_through_laravel(): void
    {
        config()->set('services.bible_api.url', 'http://bible-api:8080');
        Http::preventStrayRequests();
        Http::fake([
            'http://bible-api:8080/verses/john/3:16?translation=de' => Http::response([
                'translation' => 'de',
                'verses' => [['text' => 'Denn so sehr hat Gott die Welt geliebt.']],
            ]),
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->getJson(route('v1.admin.bible.verses', [
            'reference' => 'john/3:16',
            'translation' => 'de',
        ]))
            ->assertOk()
            ->assertJsonPath('data.verses.0.text', 'Denn so sehr hat Gott die Welt geliebt.');
    }

    public function test_editor_can_load_verse_counts_for_each_chapter(): void
    {
        config()->set('services.bible_api.url', 'http://bible-api:8080');
        Http::fake([
            'http://bible-api:8080/schema?translation=de' => Http::response([
                'translation' => 'de',
                'schema' => [['id' => 'genesis', 'verses' => ['1' => 31, '2' => 25]]],
            ]),
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->getJson(route('v1.admin.bible.schema', ['translation' => 'de']))
            ->assertOk()
            ->assertJsonPath('data.schema.0.id', 'genesis')
            ->assertJsonPath('data.schema.0.verses.1', 31);
    }

    public function test_bible_verse_endpoint_rejects_an_invalid_reference(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->getJson(route('v1.admin.bible.verses', [
            'reference' => '../private',
            'translation' => 'de',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reference');
    }
}
