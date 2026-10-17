<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $index = $this->resource['index'];

        return ['id' => $index->document_id, 'name' => $index->document->title, 'description' => $index->document->summary, 'url' => route('v1.documents.show', $index->document), 'score' => $this->resource['score']];
    }
}
