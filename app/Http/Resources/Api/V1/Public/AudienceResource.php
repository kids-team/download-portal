<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Models\Audience;
use Illuminate\Http\Request;

class AudienceResource extends SchemaOrgResource
{
    public function toArray(Request $request): array
    {
        /** @var Audience $audience */
        $audience = $this->resource;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Audience',
            '@id' => route('v1.audiences.show', $audience),
            'identifier' => 'audience:'.$audience->getKey(),
            'name' => $audience->name,
            'audienceType' => $audience->name,
        ];
    }
}
