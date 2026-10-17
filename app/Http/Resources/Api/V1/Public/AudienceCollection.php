<?php

namespace App\Http\Resources\Api\V1\Public;

class AudienceCollection extends SchemaOrgItemListCollection
{
    public $collects = AudienceResource::class;
}
