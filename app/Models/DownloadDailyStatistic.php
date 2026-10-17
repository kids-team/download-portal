<?php

namespace App\Models;

use Database\Factories\DownloadDailyStatisticFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['download_id', 'downloaded_on', 'download_count'])]
class DownloadDailyStatistic extends Model
{
    /** @use HasFactory<DownloadDailyStatisticFactory> */
    use HasFactory;

    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'downloaded_on' => 'date',
            'download_count' => 'integer',
        ];
    }
}
