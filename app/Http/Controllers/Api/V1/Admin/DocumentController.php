<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\UpdateDocumentSearchIndex;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\Api\V1\Admin\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('viewAny', Document::class), 403);

        return DocumentResource::collection(
            Document::query()
                ->with('tags')
                ->orderBy('parent_id')
                ->orderBy('position')
                ->paginate(24),
        );
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = DB::transaction(function () use ($request): Document {
            $document = Document::query()->create($request->safe()->except('tag_ids'));
            $document->tags()->sync($request->validated('tag_ids', []));

            return $document->load('tags');
        });
        app(UpdateDocumentSearchIndex::class)->handle($document);

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Document $document): DocumentResource
    {
        abort_unless($request->user()?->can('view', $document), 403);

        return new DocumentResource($document->load('tags'));
    }

    public function update(UpdateDocumentRequest $request, Document $document): DocumentResource
    {
        $document = DB::transaction(function () use ($request, $document): Document {
            $document->update($request->safe()->except('tag_ids'));

            if ($request->has('tag_ids')) {
                $document->tags()->sync($request->validated('tag_ids'));
            }

            return $document->load('tags');
        });
        app(UpdateDocumentSearchIndex::class)->handle($document);

        return new DocumentResource($document);
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        abort_unless($request->user()?->can('delete', $document), 403);

        $document->delete();

        return response()->json(status: 204);
    }
}
