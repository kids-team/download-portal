<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaAssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MediaAsset $mediaAsset */
        $mediaAsset = $this->resource;

        return [
            'id' => $mediaAsset->id,
            'disk' => $mediaAsset->disk,
            'path' => $mediaAsset->path,
            'original_filename' => $mediaAsset->original_filename,
            'mime_type' => $mediaAsset->mime_type,
            'byte_size' => $mediaAsset->byte_size,
            'checksum' => $mediaAsset->checksum,
            'visibility' => $mediaAsset->visibility,
            'width' => $mediaAsset->width,
            'height' => $mediaAsset->height,
            'url' => $this->when(
                $mediaAsset->visibility === 'public',
                fn (): string => url(Storage::disk($mediaAsset->disk)->url($mediaAsset->path)),
            ),
            'created_at' => $mediaAsset->created_at->toAtomString(),
            'updated_at' => $mediaAsset->updated_at->toAtomString(),
        ];
    }
}
