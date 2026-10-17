<?php

namespace Tests\Feature;

use App\DocumentStatus;
use App\Models\BibleReference;
use App\Models\Document;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DocumentSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_indexes_lexical_text_mentions_and_bible_references(): void
    {
        $document = Document::factory()->create(['content' => ['root' => ['children' => [['type' => 'text', 'text' => 'Gnade'], ['type' => 'mention', 'label' => 'Paulus']]]]]);
        BibleReference::factory()->for($document)->create(['translation' => 'ELB', 'book_slug' => 'john', 'start_chapter' => 3, 'start_verse' => 16]);

        $this->assertDatabaseHas('document_search_indices', ['document_id' => $document->id, 'content_text' => 'gnade paulus', 'bible_reference_text' => 'elb john/3:16']);
    }

    public function test_returns_only_currently_public_documents(): void
    {
        $document = Document::factory()->create(['title' => 'Gnade für alle', 'status' => DocumentStatus::Published, 'published_at' => now()->subMinute()]);
        Document::factory()->create(['title' => 'Gnade intern', 'status' => DocumentStatus::Review]);
        Document::factory()->create(['title' => 'Gnade später', 'status' => DocumentStatus::Published, 'published_at' => now()->addMinute()]);

        $this->getJson(route('v1.search', ['q' => 'Gnade']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $document->id)
            ->assertJsonCount(1, 'data');
    }
}
