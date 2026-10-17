<?php

namespace Tests\Feature;

use App\Actions\SyncSemanticIndex;
use App\Jobs\ReloadSemanticIndex;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SemanticIndexRecoveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_queues_a_reload_after_a_python_index_restart(): void
    {
        $this->resetReloadLock();
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/health' => Http::response(['initialized' => false, 'size' => 0])]);

        app(SyncSemanticIndex::class)->queueReloadIfNeeded();

        Queue::assertPushed(ReloadSemanticIndex::class, 1);
    }

    public function test_does_not_reload_a_legitimately_empty_initialized_index(): void
    {
        $this->resetReloadLock();
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/health' => Http::response(['initialized' => true, 'size' => 0])]);

        app(SyncSemanticIndex::class)->queueReloadIfNeeded();

        Queue::assertNothingPushed();
    }

    public function test_prevents_parallel_reload_checks_from_queuing_more_than_one_job(): void
    {
        $this->resetReloadLock();
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/health' => Http::response(['initialized' => false, 'size' => 0])]);

        app(SyncSemanticIndex::class)->queueReloadIfNeeded();
        app(SyncSemanticIndex::class)->queueReloadIfNeeded();

        Queue::assertPushed(ReloadSemanticIndex::class, 1);
    }

    public function test_logs_python_outage_without_queuing_a_reload(): void
    {
        $this->resetReloadLock();
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/health' => Http::failedConnection()]);
        Log::shouldReceive('warning')->once();

        app(SyncSemanticIndex::class)->queueReloadIfNeeded();

        Queue::assertNothingPushed();
    }

    public function test_reload_job_releases_its_lock_after_restoring_the_index(): void
    {
        $this->resetReloadLock();
        $lock = Cache::lock('semantic-search:reload', 300);
        $this->assertTrue($lock->get());
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/index/reload' => Http::response(['initialized' => true, 'size' => 0])]);

        (new ReloadSemanticIndex($lock->owner()))->handle(app(SyncSemanticIndex::class));

        $this->assertTrue(Cache::lock('semantic-search:reload', 300)->get());
    }

    public function test_reload_job_keeps_its_lock_for_retries_and_releases_it_when_it_fails_permanently(): void
    {
        $this->resetReloadLock();
        $lock = Cache::lock('semantic-search:reload', 300);
        $this->assertTrue($lock->get());
        Http::preventStrayRequests();
        Http::fake(['http://127.0.0.1:8081/internal/index/reload' => Http::failedConnection()]);

        try {
            (new ReloadSemanticIndex($lock->owner()))->handle(app(SyncSemanticIndex::class));
        } catch (\Throwable) {
        }

        $this->assertFalse(Cache::lock('semantic-search:reload', 300)->get());
        (new ReloadSemanticIndex($lock->owner()))->failed(new \RuntimeException('Python unavailable'));

        $this->assertTrue(Cache::lock('semantic-search:reload', 300)->get());
    }

    private function resetReloadLock(): void
    {
        Cache::flush();
        Cache::lock('semantic-search:reload')->forceRelease();
    }
}
