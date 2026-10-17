<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaAssetRequest;
use App\Http\Requests\UpdateMediaAssetRequest;
use App\Http\Resources\Api\V1\Admin\MediaAssetResource;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaAssetController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('viewAny', MediaAsset::class), 403);

        return MediaAssetResource::collection(MediaAsset::query()->latest()->paginate(24));
    }

    public function store(StoreMediaAssetRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $visibility = $request->validated('visibility', 'public');
        $disk = $this->diskFor($visibility);
        $path = $file->store('media', ['disk' => $disk, 'visibility' => $visibility]);

        if ($path === false) {
            throw new RuntimeException('Die Mediendatei konnte nicht gespeichert werden.');
        }

        try {
            $mediaAsset = MediaAsset::query()->create($this->attributesFor($file, $disk, $path, $visibility));
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        return (new MediaAssetResource($mediaAsset))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, MediaAsset $mediaAsset): MediaAssetResource
    {
        abort_unless($request->user()?->can('view', $mediaAsset), 403);

        return new MediaAssetResource($mediaAsset);
    }

    public function update(UpdateMediaAssetRequest $request, MediaAsset $mediaAsset): MediaAssetResource
    {
        $visibility = $request->validated('visibility');

        if ($visibility !== $mediaAsset->visibility) {
            $this->moveToVisibility($mediaAsset, $visibility);
        }

        return new MediaAssetResource($mediaAsset->refresh());
    }

    public function destroy(Request $request, MediaAsset $mediaAsset): JsonResponse
    {
        abort_unless($request->user()?->can('delete', $mediaAsset), 403);

        $disk = $mediaAsset->disk;
        $path = $mediaAsset->path;
        $mediaAsset->delete();
        Storage::disk($disk)->delete($path);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function attributesFor(UploadedFile $file, string $disk, string $path, string $visibility): array
    {
        $dimensions = $file->dimensions();

        return [
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'byte_size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
            'visibility' => $visibility,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ];
    }

    private function moveToVisibility(MediaAsset $mediaAsset, string $visibility): void
    {
        $targetDisk = $this->diskFor($visibility);

        if ($targetDisk === $mediaAsset->disk) {
            $mediaAsset->update(['visibility' => $visibility]);

            return;
        }

        $sourceDisk = Storage::disk($mediaAsset->disk);
        $stream = $sourceDisk->readStream($mediaAsset->path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Die Mediendatei konnte nicht gelesen werden.');
        }

        try {
            $wasWritten = Storage::disk($targetDisk)->writeStream($mediaAsset->path, $stream, ['visibility' => $visibility]);
        } finally {
            fclose($stream);
        }

        if (! $wasWritten) {
            throw new RuntimeException('Die Mediendatei konnte nicht verschoben werden.');
        }

        $sourceDiskName = $mediaAsset->disk;
        $mediaAsset->update(['disk' => $targetDisk, 'visibility' => $visibility]);
        Storage::disk($sourceDiskName)->delete($mediaAsset->path);
    }

    private function diskFor(string $visibility): string
    {
        return $visibility === 'public' ? 'public' : 'local';
    }
}
