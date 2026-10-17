<?php

namespace App\Jobs;

use App\Actions\ChunkDocumentForEmbeddings;
use App\Actions\SyncSemanticIndex;
use App\Models\Document;
use App\Models\DocumentEmbedding;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GenerateDocumentEmbeddings implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [5, 30];

    public function __construct(public int $documentId) {}

    /**
     * Execute the job.
     */
    public function handle(ChunkDocumentForEmbeddings $chunkDocumentForEmbeddings, SyncSemanticIndex $syncSemanticIndex): void
    {
        $document = Document::query()->find($this->documentId);
        if ($document === null) {
            return;
        }

        $originalChunks = $chunkDocumentForEmbeddings->handle($document);
        [$chunks, $safeVectors] = $this->embed($originalChunks);
        if ($chunks !== $originalChunks) {
            $this->replaceWithTokenSafeChunks($document, $chunks, $safeVectors, $syncSemanticIndex);

            return;
        }
        $hashes = collect($chunks)->map(fn (string $chunk): string => hash('sha256', $chunk));
        $existing = $document->embeddings()->where('model', 'intfloat/multilingual-e5-small')->get()->keyBy('chunk_index');
        $unchanged = $existing->filter(fn (DocumentEmbedding $embedding, int $chunkIndex): bool => ($hashes[$chunkIndex] ?? null) === $embedding->content_hash);
        $missing = collect($chunks)->filter(fn (string $_, int $chunkIndex): bool => ! $unchanged->has($chunkIndex));

        $vectors = $missing->keys()->mapWithKeys(fn (int $chunkIndex): array => [$chunkIndex => $safeVectors[$chunkIndex]])->all();
        $removedIds = $existing->reject(fn (DocumentEmbedding $_, int $chunkIndex): bool => isset($chunks[$chunkIndex]) && $unchanged->has($chunkIndex))->pluck('id')->all();
        $created = DB::transaction(function () use ($document, $existing, $missing, $vectors, $hashes): Collection {
            $staleIds = $existing->except($missing->keys()->all())->pluck('id')->all();
            if ($staleIds !== []) {
                DocumentEmbedding::query()->whereKey($staleIds)->delete();
            }
            $created = new Collection;
            foreach ($missing as $chunkIndex => $_) {
                $vector = $vectors[$chunkIndex];
                $embedding = DocumentEmbedding::query()->updateOrCreate(
                    ['document_id' => $document->id, 'chunk_index' => $chunkIndex, 'model' => 'intfloat/multilingual-e5-small'],
                    ['content_hash' => $hashes[$chunkIndex], 'dimension' => count($vector), 'embedding' => pack('g*', ...$vector)],
                );
                $created->push($embedding);
            }

            return $created;
        });
        $syncSemanticIndex->delete($removedIds);
        $syncSemanticIndex->upsert($created);
    }

    /**
     * @param  array<int, string>  $chunks
     * @return array{0: array<int, string>, 1: array<int, array<int, float>>}
     */
    private function embed(array $chunks): array
    {
        $safeChunks = [];
        $vectors = [];
        foreach ($chunks as $chunk) {
            foreach ($this->embedChunk($chunk) as [$safeChunk, $vector]) {
                $safeChunks[] = $safeChunk;
                $vectors[] = $vector;
            }
        }

        return [$safeChunks, $vectors];
    }

    /** @return array<int, array{0: string, 1: array<int, float>}> */
    private function embedChunk(string $chunk): array
    {
        $response = Http::baseUrl((string) config('services.semantic_search.url'))
            ->withHeader('X-Internal-Token', (string) config('services.semantic_search.token'))
            ->connectTimeout(2)->timeout(30)
            ->post('/internal/embed/passages', [['id' => '0', 'text' => $chunk]]);
        if ($response->status() === 422) {
            $words = preg_split('/\s+/u', $chunk, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($words) < 2) {
                throw new \RuntimeException('A single lexical token exceeds the semantic model token limit.');
            }
            $middle = intdiv(count($words), 2);

            return [...$this->embedChunk(implode(' ', array_slice($words, 0, $middle))), ...$this->embedChunk(implode(' ', array_slice($words, $middle)))];
        }

        $item = $response->throw()->json()[0];

        return [[$chunk, $item['vector']]];
    }

    /** @param array<int, string> $chunks @param array<int, array<int, float>> $vectors */
    private function replaceWithTokenSafeChunks(Document $document, array $chunks, array $vectors, SyncSemanticIndex $syncSemanticIndex): void
    {
        $existingIds = $document->embeddings()->where('model', 'intfloat/multilingual-e5-small')->pluck('id')->all();
        $embeddings = DB::transaction(function () use ($document, $chunks, $vectors): Collection {
            $document->embeddings()->where('model', 'intfloat/multilingual-e5-small')->delete();
            $embeddings = new Collection;
            foreach ($chunks as $index => $chunk) {
                $embeddings->push(DocumentEmbedding::query()->create([
                    'document_id' => $document->id, 'chunk_index' => $index, 'model' => 'intfloat/multilingual-e5-small',
                    'content_hash' => hash('sha256', $chunk), 'dimension' => count($vectors[$index]), 'embedding' => pack('g*', ...$vectors[$index]),
                ]));
            }

            return $embeddings;
        });
        $syncSemanticIndex->delete($existingIds);
        $syncSemanticIndex->upsert($embeddings);
    }
}
