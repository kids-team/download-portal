<?php

namespace App\Actions;

use App\Jobs\ReloadSemanticIndex;
use App\Models\DocumentEmbedding;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSemanticIndex
{
    private const ReloadLock = 'semantic-search:reload';

    public function reload(): void
    {
        $this->request('/internal/index/reload', DocumentEmbedding::query()
            ->where('model', 'intfloat/multilingual-e5-small')
            ->get());
    }

    public function queueReloadIfNeeded(): void
    {
        try {
            $initialized = $this->client()->get('/internal/health')->throw()->json('initialized') === true;
        } catch (Throwable $exception) {
            Log::warning('Semantic index health check failed.', ['exception' => $exception]);

            return;
        }

        if ($initialized) {
            return;
        }

        $lock = Cache::lock(self::ReloadLock, 300);
        if (! $lock->get()) {
            return;
        }

        try {
            dispatch(new ReloadSemanticIndex($lock->owner()));
        } catch (Throwable $exception) {
            $lock->release();
            Log::warning('Semantic index reload could not be queued.', ['exception' => $exception]);
        }
    }

    public static function releaseReloadLock(?string $owner): void
    {
        if ($owner !== null) {
            Cache::restoreLock(self::ReloadLock, $owner)->release();
        }
    }

    /** @param Collection<int, DocumentEmbedding> $embeddings */
    public function upsert(Collection $embeddings): void
    {
        if ($embeddings->isNotEmpty()) {
            $this->request('/internal/index/upsert', $embeddings);
        }
    }

    /** @param array<int, int> $ids */
    public function delete(array $ids): void
    {
        if ($ids !== []) {
            $this->client()->post('/internal/index/delete', array_map(strval(...), $ids))->throw();
        }
    }

    /** @param Collection<int, DocumentEmbedding> $embeddings */
    private function request(string $path, Collection $embeddings): void
    {
        $this->client()->post($path, $embeddings->map(fn (DocumentEmbedding $embedding): array => [
            'id' => (string) $embedding->id,
            'document_id' => $embedding->document_id,
            'chunk_index' => $embedding->chunk_index,
            'vector' => array_values(unpack('g*', $embedding->embedding)),
        ])->all())->throw();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.semantic_search.url'))
            ->withHeader('X-Internal-Token', (string) config('services.semantic_search.token'))
            ->connectTimeout(2)
            ->timeout(5);
    }
}
