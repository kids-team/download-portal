<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagResource extends SchemaOrgResource
{
    public function toArray(Request $request): array
    {
        /** @var Tag $tag */
        $tag = $this->resource;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'DefinedTerm',
            '@id' => route('v1.tags.show', $tag),
            'identifier' => 'tag:'.$tag->getKey(),
            'name' => $tag->name,
            'termCode' => $tag->slug,
        ];
    }
}
