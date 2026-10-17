<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Public\TagCollection;
use App\Http\Resources\Api\V1\Public\TagResource;
use App\Models\Tag;

class TagController extends Controller
{
    public function index(): TagCollection
    {
        return new TagCollection(Tag::query()->orderBy('name')->get());
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }
}
