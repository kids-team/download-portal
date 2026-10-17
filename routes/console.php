<?php

use App\Actions\SyncSemanticIndex;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(SyncSemanticIndex::class)->queueReloadIfNeeded();
})
    ->name('semantic-index-health')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer();
