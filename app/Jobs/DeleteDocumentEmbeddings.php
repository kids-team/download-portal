<?php

namespace App\Jobs;

use App\Actions\SyncSemanticIndex;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteDocumentEmbeddings implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [5, 30];

    /** @param array<int, int> $embeddingIds */
    public function __construct(public array $embeddingIds) {}

    /**
     * Execute the job.
     */
    public function handle(SyncSemanticIndex $syncSemanticIndex): void
    {
        $syncSemanticIndex->delete($this->embeddingIds);
    }
}
