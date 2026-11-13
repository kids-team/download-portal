<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\BibleApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BibleCatalogController extends Controller
{
    public function index(Request $request, BibleApiClient $bible): JsonResponse
    {
        abort_unless($request->user()?->can('viewAny', Document::class), 403);

        $validated = $request->validate([
            'translation' => ['required', 'string', 'max:32'],
        ]);

        return response()->json([
            'data' => $bible->books($validated['translation']),
        ]);
    }

    public function schema(Request $request, BibleApiClient $bible): JsonResponse
    {
        abort_unless($request->user()?->can('viewAny', Document::class), 403);

        $validated = $request->validate([
            'translation' => ['required', 'string', 'max:32'],
        ]);

        return response()->json([
            'data' => $bible->schema($validated['translation']),
        ]);
    }
}
