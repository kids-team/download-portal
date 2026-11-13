<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBibleReferenceRequest;
use App\Http\Resources\Api\V1\Admin\BibleReferenceResource;
use App\Models\BibleReference;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BibleReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Document $document): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('view', $document), 403);

        return BibleReferenceResource::collection($document->bibleReferences()->paginate(100));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBibleReferenceRequest $request, Document $document): JsonResponse
    {
        $reference = $document->bibleReferences()->create([
            ...$request->validated(),
            'position' => ((int) $document->bibleReferences()->max('position')) + 1,
        ]);

        return (new BibleReferenceResource($reference))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function destroy(Request $request, Document $document, BibleReference $bibleReference): JsonResponse
    {
        abort_unless($request->user()?->can('update', $document), 403);
        abort_unless($bibleReference->document_id === $document->id, 404);

        $bibleReference->delete();

        return response()->json(status: 204);
    }
}
