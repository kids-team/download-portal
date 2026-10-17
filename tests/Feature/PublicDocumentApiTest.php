<?php

namespace Tests\Feature;

use App\DocumentStatus;
use App\Models\Audience;
use App\Models\BibleReference;
use App\Models\Category;
use App\Models\Document;
use App\Models\Download;
use App\Models\MediaAsset;
use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicDocumentApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_published_documents_as_a_schema_org_item_list(): void
    {
        $document = $this->createPublishedDocument();
        Document::factory()->create(['status' => DocumentStatus::Archived]);
        Document::factory()->create(['status' => DocumentStatus::Draft]);
        Document::factory()->create(['published_at' => now()->addMinute()]);

        $response = $this->getJson(route('v1.documents.index'));

        $response->assertOk()
            ->assertHeaderContains('Content-Type', 'application/ld+json')
            ->assertJsonPath('@context', 'https://schema.org')
            ->assertJsonPath('@type', 'ItemList')
            ->assertJsonPath('numberOfItems', 1)
            ->assertJsonPath('itemListElement.0.@type', 'ListItem')
            ->assertJsonPath('itemListElement.0.position', 1)
            ->assertJsonPath('itemListElement.0.item.@id', route('v1.documents.show', $document))
            ->assertJsonPath('itemListElement.0.item.name', $document->title);
    }

    public function test_returns_a_published_document_with_schema_org_relationships(): void
    {
        $document = $this->createPublishedDocument();

        $response = $this->getJson(route('v1.documents.show', $document));

        $response->assertOk()
            ->assertHeaderContains('Content-Type', 'application/ld+json')
            ->assertJsonPath('@type.0', 'DigitalDocument')
            ->assertJsonPath('@type.1', 'LearningResource')
            ->assertJsonPath('identifier', 'document:'.$document->id)
            ->assertJsonPath('about.@type', 'DefinedTerm')
            ->assertJsonPath('keywords.0.@type', 'DefinedTerm')
            ->assertJsonPath('audience.@type', 'Audience')
            ->assertJsonPath('image.@type', 'ImageObject')
            ->assertJsonPath('encoding.0.@type', 'MediaObject')
            ->assertJsonPath('encoding.0.contentUrl', route('v1.downloads.delivery', $document->downloads()->first()))
            ->assertJsonPath('citation.0', 'ELB: john/3:16');
    }

    public function test_returns_404_for_a_document_that_is_not_published(): void
    {
        $document = Document::factory()->create([
            'status' => DocumentStatus::Review,
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->getJson(route('v1.documents.show', $document));

        $response->assertNotFound();
    }

    public function test_delivers_a_published_download_and_records_it(): void
    {
        Storage::fake('public');
        $document = Document::factory()->create();
        $download = Download::factory()->for($document)->create([
            'path' => 'downloads/arbeitsblatt.pdf',
            'original_filename' => 'arbeitsblatt.pdf',
        ]);
        Storage::disk('public')->put($download->path, 'PDF content');

        $response = $this->get(route('v1.downloads.delivery', $download));

        $response->assertDownload('arbeitsblatt.pdf');
        $this->assertSame(1, $download->fresh()->download_count);
    }

    private function createPublishedDocument(): Document
    {
        $category = Category::factory()->create(['name' => 'Bibel']);
        $audience = Audience::factory()->create(['name' => 'Kinder']);
        $coverMedia = MediaAsset::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Schöpfung']);
        $document = Document::factory()->create([
            'category_id' => $category->id,
            'audience_id' => $audience->id,
            'cover_media_id' => $coverMedia->id,
            'title' => 'Arbeitsblatt: Schöpfung',
        ]);
        $document->tags()->attach($tag);
        Download::factory()->for($document)->create();
        BibleReference::factory()->for($document)->create([
            'translation' => 'ELB',
            'book_slug' => 'john',
            'start_chapter' => 3,
            'start_verse' => 16,
        ]);

        return $document;
    }
}
