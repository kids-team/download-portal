<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Public\AudienceCollection;
use App\Http\Resources\Api\V1\Public\AudienceResource;
use App\Models\Audience;

class AudienceController extends Controller
{
    public function index(): AudienceCollection
    {
        return new AudienceCollection(Audience::query()->orderBy('name')->get());
    }

    public function show(Audience $audience): AudienceResource
    {
        return new AudienceResource($audience);
    }
}
