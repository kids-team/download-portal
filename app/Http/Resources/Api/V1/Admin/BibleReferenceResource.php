<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BibleReferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'translation' => $this->translation,
            'book_slug' => $this->book_slug,
            'start_chapter' => $this->start_chapter,
            'start_verse' => $this->start_verse,
            'end_chapter' => $this->end_chapter,
            'end_verse' => $this->end_verse,
            'position' => $this->position,
            'api_path' => $this->apiPath(),
        ];
    }
}
