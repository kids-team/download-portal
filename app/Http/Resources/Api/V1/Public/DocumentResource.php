<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Models\Document;
use App\Models\Download;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends SchemaOrgResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Document $document */
        $document = $this->resource;

        return [
            '@context' => 'https://schema.org',
            '@type' => ['DigitalDocument', 'LearningResource'],
            '@id' => route('v1.documents.show', $document),
            'identifier' => 'document:'.$document->getKey(),
            'name' => $document->title,
            'description' => $this->whenNotNull($document->summary),
            'datePublished' => $this->whenNotNull($document->published_at?->toAtomString()),
            'dateModified' => $document->updated_at->toAtomString(),
            'isAccessibleForFree' => true,
            'copyrightNotice' => $this->whenNotNull($document->copyright_notice),
            'isPartOf' => $this->parentReference($document),
            'about' => $this->category($document),
            'keywords' => $this->tags($document),
            'audience' => $this->audience($document),
            'image' => $this->coverImage($document),
            'encoding' => $this->downloads($document),
            'citation' => $this->bibleReferences($document),
        ];
    }

    /**
     * @return array<string, string>|MissingValue
     */
    private function parentReference(Document $document): array|MissingValue
    {
        if ($document->parent_id === null) {
            return new MissingValue;
        }

        return [
            '@id' => route('v1.documents.show', $document->parent_id),
        ];
    }

    /**
     * @return array<string, string>|MissingValue
     */
    private function category(Document $document): array|MissingValue
    {
        if (! $document->relationLoaded('category') || $document->category === null) {
            return new MissingValue;
        }

        return [
            '@type' => 'DefinedTerm',
            '@id' => route('v1.categories.show', $document->category),
            'name' => $document->category->name,
        ];
    }

    /**
     * @return array<int, array<string, string>>|MissingValue
     */
    private function tags(Document $document): array|MissingValue
    {
        if (! $document->relationLoaded('tags')) {
            return new MissingValue;
        }

        return $document->tags
            ->map(fn ($tag): array => [
                '@type' => 'DefinedTerm',
                '@id' => route('v1.tags.show', $tag),
                'name' => $tag->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>|MissingValue
     */
    private function audience(Document $document): array|MissingValue
    {
        if (! $document->relationLoaded('audience') || $document->audience === null) {
            return new MissingValue;
        }

        return [
            '@type' => 'Audience',
            '@id' => route('v1.audiences.show', $document->audience),
            'audienceType' => $document->audience->name,
        ];
    }

    /**
     * @return array<string, int|string>|MissingValue
     */
    private function coverImage(Document $document): array|MissingValue
    {
        if (! $document->relationLoaded('coverMedia') || $document->coverMedia === null || $document->coverMedia->visibility !== 'public') {
            return new MissingValue;
        }

        return $this->image($document->coverMedia);
    }

    /**
     * @return array<string, int|string>
     */
    private function image(MediaAsset $mediaAsset): array
    {
        return array_filter([
            '@type' => 'ImageObject',
            'contentUrl' => $this->storageUrl($mediaAsset->disk, $mediaAsset->path),
            'encodingFormat' => $mediaAsset->mime_type,
            'width' => $mediaAsset->width,
            'height' => $mediaAsset->height,
        ], static fn (int|string|null $value): bool => $value !== null);
    }

    /**
     * @return array<int, array<string, int|string>>|MissingValue
     */
    private function downloads(Document $document): array|MissingValue
    {
        if (! $document->relationLoaded('downloads')) {
            return new MissingValue;
        }

        return $document->downloads
            ->filter(fn (Download $download): bool => $download->is_published && $download->visibility === 'public')
            ->map(fn (Download $download): array => [
                '@type' => 'MediaObject',
                'name' => $download->title,
                'description' => $download->description,
                'contentUrl' => route('v1.downloads.delivery', $download),
                'encodingFormat' => $download->mime_type,
                'contentSize' => $download->byte_size.' B',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>|MissingValue
     */
    private function bibleReferences(Document $document): array|MissingValue
    {
        if (! $document->relationLoaded('bibleReferences')) {
            return new MissingValue;
        }

        return $document->bibleReferences
            ->map(fn ($reference): string => $reference->translation.': '.$reference->apiPath())
            ->values()
            ->all();
    }

    private function storageUrl(string $disk, string $path): string
    {
        return url(Storage::disk($disk)->url($path));
    }
}
