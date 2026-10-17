<?php

namespace App\Jobs;

use App\Actions\SyncSemanticIndex;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ReloadSemanticIndex implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [5, 30];

    public function __construct(public ?string $lockOwner = null) {}

    /**
     * Execute the job.
     */
    public function handle(SyncSemanticIndex $syncSemanticIndex): void
    {
        $syncSemanticIndex->reload();
        SyncSemanticIndex::releaseReloadLock($this->lockOwner);
    }

    public function failed(?Throwable $exception): void
    {
        SyncSemanticIndex::releaseReloadLock($this->lockOwner);
    }
}
