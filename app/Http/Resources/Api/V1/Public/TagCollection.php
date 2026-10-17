<?php

namespace App\Http\Resources\Api\V1\Public;

class TagCollection extends SchemaOrgItemListCollection
{
    public $collects = TagResource::class;
}
