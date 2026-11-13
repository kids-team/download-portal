<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\BibleApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BibleVerseController extends Controller
{
    public function show(Request $request, BibleApiClient $bible): JsonResponse
    {
        abort_unless($request->user()?->can('viewAny', Document::class), 403);

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+\\/[1-9][0-9]*(?::[1-9][0-9]*(?:-(?:[1-9][0-9]*:)?[1-9][0-9]*)?)?$/'],
            'translation' => ['required', 'string', 'max:32'],
        ]);

        return response()->json([
            'data' => $bible->verses($validated['reference'], $validated['translation']),
        ]);
    }
}
