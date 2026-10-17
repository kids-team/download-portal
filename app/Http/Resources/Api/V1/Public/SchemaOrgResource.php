<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class SchemaOrgResource extends JsonResource
{
    public static $wrap = null;

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Content-Type', 'application/ld+json');
    }
}
