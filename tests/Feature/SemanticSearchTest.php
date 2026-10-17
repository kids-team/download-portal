<?php

namespace Tests\Feature;

use App\Actions\ChunkDocumentForEmbeddings;
use App\Actions\SyncSemanticIndex;
use App\DocumentStatus;
use App\Jobs\DeleteDocumentEmbeddings;
use App\Jobs\GenerateDocumentEmbeddings;
use App\Models\Document;
use App\Models\DocumentEmbedding;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SemanticSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_chunks_lexical_content_on_word_boundaries_and_keeps_mentions(): void
    {
        $document = Document::factory()->make(['content' => ['root' => ['children' => [
            ['type' => 'text', 'text' => implode(' ', array_fill(0, 190, 'Gnade'))],
            ['type' => 'mention', 'label' => 'Paulus'],
        ]]]]);

        $chunks = app(ChunkDocumentForEmbeddings::class)->handle($document);

        $this->assertCount(2, $chunks);
        $this->assertStringContainsString('Paulus', $chunks[1]);
        $this->assertSame(180, count(explode(' ', $chunks[0])));
    }

    public function test_queues_embedding_generation_when_a_document_changes(): void
    {
        Queue::fake([GenerateDocumentEmbeddings::class]);

        $document = Document::factory()->create();
        $document->update(['title' => 'Aktualisierter Titel']);

        Queue::assertPushed(GenerateDocumentEmbeddings::class, 2);
        Queue::assertPushed(GenerateDocumentEmbeddings::class, fn (GenerateDocumentEmbeddings $job): bool => $job->documentId === $document->id);
    }

    public function test_generates_persists_and_upserts_embeddings(): void
    {
        Queue::fake();
        $document = Document::factory()->create(['content' => ['root' => ['children' => [['type' => 'text', 'text' => 'Gnade und Frieden']]]]]);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:8081/internal/embed/passages' => Http::response([['id' => '0', 'vector' => [0.1, 0.2, 0.3]]]),
            'http://127.0.0.1:8081/internal/index/upsert' => Http::response(['size' => 1]),
        ]);

        (new GenerateDocumentEmbeddings($document->id))->handle(app(ChunkDocumentForEmbeddings::class), app(SyncSemanticIndex::class));

        $this->assertDatabaseHas('document_embeddings', ['document_id' => $document->id, 'chunk_index' => 0, 'dimension' => 3]);
        Http::assertSentCount(2);
    }

    public function test_queues_ram_index_deletion_when_document_is_deleted(): void
    {
        Queue::fake([DeleteDocumentEmbeddings::class]);
        $document = Document::factory()->create();
        $embedding = DocumentEmbedding::query()->create([
            'document_id' => $document->id,
            'chunk_index' => 0,
            'model' => 'intfloat/multilingual-e5-small',
            'content_hash' => hash('sha256', 'text'),
            'dimension' => 1,
            'embedding' => pack('g', 1.0),
        ]);

        $document->delete();

        Queue::assertPushed(DeleteDocumentEmbeddings::class, fn (DeleteDocumentEmbeddings $job): bool => $job->embeddingIds === [$embedding->id]);
    }

    public function test_rrf_prefers_semantic_match_but_never_returns_non_public_documents(): void
    {
        Queue::fake();
        $classical = Document::factory()->create(['title' => 'Gnade', 'content' => ['root' => ['children' => []]]]);
        $semantic = Document::factory()->create(['title' => 'Anderer Titel', 'content' => ['root' => ['children' => [['type' => 'text', 'text' => 'Gnade']]]]]);
        $draft = Document::factory()->create(['title' => 'Geheim', 'status' => DocumentStatus::Draft, 'content' => ['root' => ['children' => [['type' => 'text', 'text' => 'Gnade']]]]]);
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/search' => Http::response(['results' => [
            ['document_id' => $draft->id, 'chunk_index' => 0],
            ['document_id' => $semantic->id, 'chunk_index' => 0],
        ]])]);

        $response = $this->getJson(route('v1.search', ['q' => 'Gnade']))->assertOk();

        $response->assertJsonPath('data.0.id', $semantic->id)
            ->assertJsonMissing(['id' => $draft->id])
            ->assertJsonCount(2, 'data');
    }

    public function test_uses_classical_results_when_semantic_service_fails(): void
    {
        Queue::fake();
        $document = Document::factory()->create(['title' => 'Gnade für alle']);
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/search' => Http::failedConnection()]);

        $this->getJson(route('v1.search', ['q' => 'Gnade']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $document->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_reloads_persisted_embeddings_into_the_python_index(): void
    {
        Queue::fake();
        $document = Document::factory()->create();
        DocumentEmbedding::query()->create([
            'document_id' => $document->id,
            'chunk_index' => 0,
            'model' => 'intfloat/multilingual-e5-small',
            'content_hash' => hash('sha256', 'text'),
            'dimension' => 2,
            'embedding' => pack('g*', 1.0, 2.0),
        ]);
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/index/reload' => Http::response(['size' => 1])]);

        app(SyncSemanticIndex::class)->reload();

        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:8081/internal/index/reload'
            && $request['0']['document_id'] === $document->id);
    }
}
