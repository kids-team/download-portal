<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DownloadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LogicException;

#[Fillable([
    'document_id',
    'disk',
    'path',
    'original_filename',
    'mime_type',
    'byte_size',
    'checksum',
    'visibility',
    'title',
    'description',
    'position',
    'is_published',
])]
class Download extends Model
{
    /** @use HasFactory<DownloadFactory> */
    use HasFactory;

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function dailyStatistics(): HasMany
    {
        return $this->hasMany(DownloadDailyStatistic::class)->orderBy('downloaded_on');
    }

    public function recordDownload(?CarbonInterface $downloadedAt = null): void
    {
        if (! $this->exists) {
            throw new LogicException('Only persisted downloads can be recorded.');
        }

        $occurredAt = ($downloadedAt ?? now())->toImmutable();

        DB::transaction(function () use ($occurredAt): void {
            $this->newQuery()->whereKey($this->getKey())->increment('download_count', 1, [
                'last_downloaded_at' => $occurredAt,
            ]);

            DB::table('download_daily_statistics')->upsert([
                [
                    'download_id' => $this->getKey(),
                    'downloaded_on' => $occurredAt->toDateString(),
                    'download_count' => 1,
                    'created_at' => $occurredAt,
                    'updated_at' => $occurredAt,
                ],
            ], ['download_id', 'downloaded_on'], [
                'download_count' => DB::raw('download_count + 1'),
                'updated_at' => $occurredAt,
            ]);

            $this->refresh();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
            'is_published' => 'boolean',
            'download_count' => 'integer',
            'last_downloaded_at' => 'datetime',
        ];
    }
}
