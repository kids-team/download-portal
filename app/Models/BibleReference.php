<?php

namespace App\Models;

use App\Actions\UpdateDocumentSearchIndex;
use Database\Factories\BibleReferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'translation',
    'book_slug',
    'start_chapter',
    'start_verse',
    'end_chapter',
    'end_verse',
    'position',
])]
class BibleReference extends Model
{
    /** @use HasFactory<BibleReferenceFactory> */
    use HasFactory;

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function apiPath(): string
    {
        $location = (string) $this->start_chapter;

        if ($this->start_verse !== null) {
            $location .= ':'.$this->start_verse;
        }

        if ($this->end_verse !== null) {
            $location .= '-';
            $location .= $this->end_chapter === null || $this->end_chapter === $this->start_chapter
                ? $this->end_verse
                : $this->end_chapter.':'.$this->end_verse;
        }

        return $this->book_slug.'/'.$location;
    }

    protected static function booted(): void
    {
        static::saved(fn (self $reference): mixed => app(UpdateDocumentSearchIndex::class)->handle($reference->document));
        static::deleted(fn (self $reference): mixed => app(UpdateDocumentSearchIndex::class)->handle($reference->document));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_chapter' => 'integer',
            'start_verse' => 'integer',
            'end_chapter' => 'integer',
            'end_verse' => 'integer',
            'position' => 'integer',
        ];
    }
}
