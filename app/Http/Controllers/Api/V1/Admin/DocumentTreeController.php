<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MoveDocumentRequest;
use App\Http\Resources\Api\V1\Admin\DocumentResource;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentTreeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('viewAny', Document::class), 403);

        $documents = Document::query()
            ->select(['id', 'parent_id', 'position', 'slug', 'title', 'icon', 'status', 'is_locked'])
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $this->buildTree($documents)]);
    }

    public function move(MoveDocumentRequest $request, Document $document): DocumentResource
    {
        $movedDocument = DB::transaction(function () use ($request, $document): Document {
            $document = Document::query()->lockForUpdate()->findOrFail($document->getKey());
            $previousParentId = $document->parent_id;
            $parentId = $request->validated('parent_id');
            $parentId = $parentId === null ? null : (int) $parentId;
            $parent = $parentId === null ? null : Document::query()->findOrFail($parentId);

            $document->moveTo($parent);

            $this->resequenceSiblings($previousParentId);
            $this->resequenceSiblings($parentId, $document->getKey(), $request->integer('position'));

            return $document->refresh();
        }, 5);

        return new DocumentResource($movedDocument);
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(Collection $documents): array
    {
        $nodes = [];

        foreach ($documents as $document) {
            $nodes[$document->getKey()] = [
                'id' => $document->id,
                'parent_id' => $document->parent_id,
                'position' => $document->position,
                'slug' => $document->slug,
                'title' => $document->title,
                'icon' => $document->icon,
                'status' => $document->status->value,
                'is_locked' => $document->is_locked,
                'children' => [],
            ];
        }

        $tree = [];

        foreach ($documents as $document) {
            $documentId = $document->getKey();

            if ($document->parent_id !== null && isset($nodes[$document->parent_id])) {
                $nodes[$document->parent_id]['children'][] = &$nodes[$documentId];

                continue;
            }

            $tree[] = &$nodes[$documentId];
        }

        return $tree;
    }

    private function resequenceSiblings(?int $parentId, ?int $movedDocumentId = null, ?int $position = null): void
    {
        $siblings = $this->siblingsQuery($parentId)
            ->when($movedDocumentId !== null, fn (Builder $query): Builder => $query->whereKeyNot($movedDocumentId))
            ->lockForUpdate()
            ->get();

        $documentIds = $siblings->modelKeys();

        if ($movedDocumentId !== null && $position !== null) {
            array_splice($documentIds, min($position, count($documentIds)), 0, [$movedDocumentId]);
        }

        foreach ($documentIds as $index => $documentId) {
            Document::query()->whereKey($documentId)->update(['position' => $index]);
        }
    }

    private function siblingsQuery(?int $parentId): Builder
    {
        return Document::query()
            ->when(
                $parentId === null,
                fn (Builder $query): Builder => $query->whereNull('parent_id'),
                fn (Builder $query): Builder => $query->where('parent_id', $parentId),
            )
            ->orderBy('position')
            ->orderBy('id');
    }
}
