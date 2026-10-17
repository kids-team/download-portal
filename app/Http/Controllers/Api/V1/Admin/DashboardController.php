<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Download;
use App\Models\MediaAsset;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Document::class);

        return response()->json(['data' => [
            'documents_by_status' => Document::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'media_assets' => MediaAsset::query()->count(),
            'downloads' => ['files' => Download::query()->count(), 'total' => Download::query()->sum('download_count')],
            'capabilities' => ['can_manage_taxonomy' => $request->user()?->role === UserRole::Admin],
        ]]);
    }
}
