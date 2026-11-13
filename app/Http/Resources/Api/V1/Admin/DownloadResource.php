<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownloadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Download $download */
        $download = $this->resource;

        return [
            'id' => $download->id,
            'document_id' => $download->document_id,
            'disk' => $download->disk,
            'path' => $download->path,
            'original_filename' => $download->original_filename,
            'mime_type' => $download->mime_type,
            'byte_size' => $download->byte_size,
            'checksum' => $download->checksum,
            'visibility' => $download->visibility,
            'title' => $download->title,
            'description' => $download->description,
            'position' => $download->position,
            'is_published' => $download->is_published,
            'download_count' => $download->download_count,
            'last_downloaded_at' => $download->last_downloaded_at?->toAtomString(),
            'created_at' => $download->created_at->toAtomString(),
            'updated_at' => $download->updated_at->toAtomString(),
        ];
    }
}
