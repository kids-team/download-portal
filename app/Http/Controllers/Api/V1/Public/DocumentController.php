<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Public\DocumentCollection;
use App\Http\Resources\Api\V1\Public\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->publiclyAvailable()
            ->with($this->publicRelations())
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(24)
            ->withQueryString();

        $response = response()->json((new DocumentCollection($documents))->resolve($request));
        $response->header('Content-Type', 'application/ld+json');

        $links = array_filter([
            $documents->previousPageUrl() === null ? null : '<'.$documents->previousPageUrl().'>; rel="prev"',
            $documents->nextPageUrl() === null ? null : '<'.$documents->nextPageUrl().'>; rel="next"',
        ]);

        if ($links !== []) {
            $response->header('Link', implode(', ', $links));
        }

        return $response;
    }

    public function show(Document $document): DocumentResource
    {
        abort_unless(
            $document->isPubliclyAvailable(),
            404,
        );

        $document->loadMissing($this->publicRelations());

        return new DocumentResource($document);
    }

    /**
     * @return array<int, string>
     */
    private function publicRelations(): array
    {
        return [
            'category',
            'audience',
            'tags',
            'coverMedia',
            'downloads',
            'bibleReferences',
        ];
    }
}
