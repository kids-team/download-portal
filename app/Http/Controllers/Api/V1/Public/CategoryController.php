<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Public\CategoryCollection;
use App\Http\Resources\Api\V1\Public\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index(): CategoryCollection
    {
        return new CategoryCollection(Category::query()->orderBy('name')->get());
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }
}
