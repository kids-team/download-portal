<?php

namespace App\Models;

use App\Actions\UpdateDocumentEmbeddings;
use App\Actions\UpdateDocumentSearchIndex;
use App\DocumentStatus;
use App\Jobs\DeleteDocumentEmbeddings;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

#[Fillable([
    'parent_id',
    'category_id',
    'audience_id',
    'cover_media_id',
    'slug',
    'position',
    'title',
    'content',
    'summary',
    'label',
    'external_url',
    'icon',
    'copyright_notice',
    'status',
    'is_locked',
    'published_at',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $attributes = [
        'status' => DocumentStatus::Draft->value,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class);
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class)->orderBy('position');
    }

    public function bibleReferences(): HasMany
    {
        return $this->hasMany(BibleReference::class)->orderBy('position');
    }

    public function searchIndex(): HasOne
    {
        return $this->hasOne(DocumentSearchIndex::class);
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(DocumentEmbedding::class);
    }

    /**
     * Move the document without allowing a cyclic parent-child relationship.
     */
    public function moveTo(?self $parent): void
    {
        if (! $this->exists) {
            throw new LogicException('Only persisted documents can be moved.');
        }

        DB::transaction(function () use ($parent): void {
            if ($parent === null) {
                $this->parent()->dissociate();
            } else {
                $this->parent()->associate($parent);
            }

            $this->save();
        });
    }

    #[Scope]
    protected function publiclyAvailable(Builder $query): void
    {
        $query
            ->where('status', DocumentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->status === DocumentStatus::Published
            && $this->published_at !== null
            && ! $this->published_at->isFuture();
    }

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            if (! $document->exists || ! $document->isDirty('parent_id')) {
                return;
            }

            $parent = $document->parent_id === null
                ? null
                : self::query()->find($document->parent_id);

            $document->ensureCanMoveTo($parent);
        });
        static::saved(function (self $document): void {
            app(UpdateDocumentSearchIndex::class)->handle($document);
            app(UpdateDocumentEmbeddings::class)->handle($document);
        });
        static::deleting(function (self $document): void {
            $ids = $document->embeddings()->pluck('id')->all();
            if ($ids !== []) {
                DeleteDocumentEmbeddings::dispatch($ids)->onConnection('database');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => DocumentStatus::class,
            'is_locked' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    private function ensureCanMoveTo(?self $parent): void
    {
        $ancestor = $parent;
        $visitedDocumentIds = [];

        while ($ancestor !== null) {
            if (in_array($ancestor->getKey(), $visitedDocumentIds, true)) {
                throw new LogicException('The target hierarchy already contains a cycle.');
            }

            if ($ancestor->is($this)) {
                throw new LogicException('A document cannot be moved beneath itself or one of its descendants.');
            }

            $visitedDocumentIds[] = $ancestor->getKey();
            $ancestor = $ancestor->parent;
        }
    }
}
