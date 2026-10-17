<?php

namespace Tests\Feature;

use App\Models\BibleReference;
use App\Models\Document;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BibleReferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_document_has_multiple_ordered_bible_references(): void
    {
        $document = Document::factory()->create();
        $laterReference = BibleReference::factory()->for($document)->create(['position' => 20]);
        $earlierReference = BibleReference::factory()->for($document)->create(['position' => 10]);

        $references = $document->bibleReferences()->get();

        $this->assertSame([$earlierReference->id, $laterReference->id], $references->pluck('id')->all());
        $this->assertSame($document->id, $earlierReference->document->id);
    }

    public function test_builds_paths_supported_by_the_bible_api(): void
    {
        $chapterReference = BibleReference::factory()->create([
            'book_slug' => 'john',
            'start_chapter' => 3,
            'start_verse' => null,
        ]);
        $singleVerseReference = BibleReference::factory()->create([
            'book_slug' => 'john',
            'start_chapter' => 3,
            'start_verse' => 16,
        ]);
        $crossChapterReference = BibleReference::factory()->create([
            'book_slug' => 'john',
            'start_chapter' => 3,
            'start_verse' => 16,
            'end_chapter' => 4,
            'end_verse' => 3,
        ]);

        $this->assertSame('john/3', $chapterReference->apiPath());
        $this->assertSame('john/3:16', $singleVerseReference->apiPath());
        $this->assertSame('john/3:16-4:3', $crossChapterReference->apiPath());
    }

    public function test_deletes_bible_references_when_the_document_is_deleted(): void
    {
        $document = Document::factory()->create();
        $reference = BibleReference::factory()->for($document)->create();

        $document->delete();

        $this->assertDatabaseMissing('bible_references', [
            'id' => $reference->id,
        ]);
    }
}
