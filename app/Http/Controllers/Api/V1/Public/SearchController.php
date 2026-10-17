<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Actions\SearchDocuments;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchDocumentsRequest;
use App\Http\Resources\Api\V1\Public\SearchResultResource;

class SearchController extends Controller
{
    public function index(SearchDocumentsRequest $request, SearchDocuments $searchDocuments)
    {
        return SearchResultResource::collection($searchDocuments->handle($request->validated()));
    }
}
