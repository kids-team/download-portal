<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDownloadRequest;
use App\Http\Requests\UpdateDownloadRequest;
use App\Http\Resources\Api\V1\Admin\DownloadResource;
use App\Models\Document;
use App\Models\Download;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DownloadController extends Controller
{
    public function index(Request $request, Document $document): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('view', $document), 403);

        return DownloadResource::collection($document->downloads()->paginate(100));
    }

    public function store(StoreDownloadRequest $request, Document $document): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store('downloads', 'local');

        if ($path === false) {
            throw new RuntimeException('Die Datei konnte nicht gespeichert werden.');
        }

        try {
            $download = $document->downloads()->create($this->attributesFor($request, $file, $path));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return (new DownloadResource($download))->response()->setStatusCode(201);
    }

    public function show(Request $request, Document $document, Download $download): DownloadResource
    {
        abort_unless($request->user()?->can('view', $document), 403);

        return new DownloadResource($download);
    }

    public function update(UpdateDownloadRequest $request, Document $document, Download $download): DownloadResource
    {
        $download->update($request->validated());

        return new DownloadResource($download->refresh());
    }

    public function destroy(Request $request, Document $document, Download $download): JsonResponse
    {
        abort_unless($request->user()?->can('delete', $download), 403);

        $disk = $download->disk;
        $path = $download->path;
        $download->delete();
        Storage::disk($disk)->delete($path);

        return response()->json(status: 204);
    }

    /** @return array<string, bool|int|string|null> */
    private function attributesFor(StoreDownloadRequest $request, UploadedFile $file, string $path): array
    {
        $originalFilename = $file->getClientOriginalName();

        return [
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $originalFilename,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'byte_size' => $file->getSize() ?: 0,
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
            'visibility' => $request->validated('visibility', 'public'),
            'title' => $request->validated('title') ?: pathinfo($originalFilename, PATHINFO_FILENAME),
            'description' => $request->validated('description'),
            'position' => ((int) $request->route('document')->downloads()->max('position')) + 1,
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
