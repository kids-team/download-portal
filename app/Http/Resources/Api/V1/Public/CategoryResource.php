<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryResource extends SchemaOrgResource
{
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'DefinedTerm',
            '@id' => route('v1.categories.show', $category),
            'identifier' => 'category:'.$category->getKey(),
            'name' => $category->name,
            'termCode' => $category->slug,
            'alternateName' => $this->whenNotNull($category->synonyms),
        ];
    }
}
