<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDeliveryController extends Controller
{
    public function show(Download $download): StreamedResponse
    {
        $download->loadMissing('document');

        abort_unless(
            $download->is_published
            && $download->visibility === 'public'
            && $download->document->isPubliclyAvailable(),
            404,
        );

        $disk = Storage::disk($download->disk);

        abort_unless($disk->exists($download->path), 404);

        $download->recordDownload();

        return $disk->download($download->path, $download->original_filename, [
            'Content-Type' => $download->mime_type,
        ]);
    }
}
