<?php

namespace App\Http\Resources\Api\V1\Public;

class CategoryCollection extends SchemaOrgItemListCollection
{
    public $collects = CategoryResource::class;
}
