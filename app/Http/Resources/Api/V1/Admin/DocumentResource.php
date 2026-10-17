<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'id' => $document->id,
            'parent_id' => $document->parent_id,
            'category_id' => $document->category_id,
            'audience_id' => $document->audience_id,
            'cover_media_id' => $document->cover_media_id,
            'slug' => $document->slug,
            'position' => $document->position,
            'title' => $document->title,
            'content' => $document->content,
            'summary' => $document->summary,
            'label' => $document->label,
            'external_url' => $document->external_url,
            'icon' => $document->icon,
            'copyright_notice' => $document->copyright_notice,
            'status' => $document->status->value,
            'is_locked' => $document->is_locked,
            'published_at' => $document->published_at?->toAtomString(),
            'tag_ids' => $this->when($document->relationLoaded('tags'), fn (): array => $document->tags->modelKeys()),
            'created_at' => $document->created_at->toAtomString(),
            'updated_at' => $document->updated_at->toAtomString(),
        ];
    }
}
