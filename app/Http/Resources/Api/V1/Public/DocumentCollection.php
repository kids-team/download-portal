<?php

namespace App\Http\Resources\Api\V1\Public;

class DocumentCollection extends SchemaOrgItemListCollection
{
    public $collects = DocumentResource::class;
}
