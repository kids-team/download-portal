<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class SchemaOrgItemListCollection extends ResourceCollection
{
    public static $wrap = null;

    /**
     * @return array<string, array<int, array<string, array<string, mixed>|int>>|int|string>
     */
    public function toArray(Request $request): array
    {
        $firstPosition = $this->resource instanceof LengthAwarePaginator
            ? $this->resource->firstItem() ?? 1
            : 1;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'numberOfItems' => $this->resource instanceof LengthAwarePaginator
                ? $this->resource->total()
                : $this->collection->count(),
            'itemListElement' => $this->collection
                ->values()
                ->map(fn (JsonResource $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $firstPosition + $index,
                    'item' => $item->resolve($request),
                ])
                ->all(),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Content-Type', 'application/ld+json');
    }
}
