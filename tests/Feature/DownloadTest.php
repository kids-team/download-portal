<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Download;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_belongs_directly_to_a_document_and_is_ordered_by_position(): void
    {
        $document = Document::factory()->create();
        $laterDownload = Download::factory()->for($document)->create(['position' => 20]);
        $earlierDownload = Download::factory()->for($document)->create(['position' => 10]);

        $downloads = $document->downloads()->get();

        $this->assertSame([$earlierDownload->id, $laterDownload->id], $downloads->pluck('id')->all());
        $this->assertSame($document->id, $earlierDownload->document->id);
    }

    public function test_records_total_and_daily_download_statistics(): void
    {
        $download = Download::factory()->create();
        $firstDownload = CarbonImmutable::parse('2026-10-16 09:00:00');
        $secondDownload = CarbonImmutable::parse('2026-10-16 12:00:00');
        $nextDayDownload = CarbonImmutable::parse('2026-10-17 09:00:00');

        $download->recordDownload($firstDownload);
        $download->recordDownload($secondDownload);
        $download->recordDownload($nextDayDownload);

        $this->assertSame(3, $download->download_count);
        $this->assertTrue($download->last_downloaded_at->equalTo($nextDayDownload));
        $this->assertDatabaseHas('download_daily_statistics', [
            'download_id' => $download->id,
            'downloaded_on' => '2026-10-16',
            'download_count' => 2,
        ]);
        $this->assertDatabaseHas('download_daily_statistics', [
            'download_id' => $download->id,
            'downloaded_on' => '2026-10-17',
            'download_count' => 1,
        ]);
    }

    public function test_deletes_daily_statistics_when_the_download_is_deleted(): void
    {
        $download = Download::factory()->create();
        $download->recordDownload(CarbonImmutable::parse('2026-10-16 09:00:00'));

        $download->delete();

        $this->assertDatabaseMissing('download_daily_statistics', [
            'download_id' => $download->id,
        ]);
    }
}
