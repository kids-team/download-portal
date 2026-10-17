<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAudienceRequest;
use App\Http\Requests\UpdateAudienceRequest;
use App\Models\Audience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Audience::class);

        return response()->json(['data' => Audience::query()->orderBy('name')->paginate(100)]);
    }

    public function store(StoreAudienceRequest $request): JsonResponse
    {
        $audience = Audience::query()->create($request->validated());

        return response()->json(['data' => $audience], 201);
    }

    public function show(Audience $audience): JsonResponse
    {
        $this->authorize('view', $audience);

        return response()->json(['data' => $audience]);
    }

    public function update(UpdateAudienceRequest $request, Audience $audience): JsonResponse
    {
        $audience->update($request->validated());

        return response()->json(['data' => $audience]);
    }

    public function destroy(Audience $audience): JsonResponse
    {
        $this->authorize('delete', $audience);
        $audience->delete();

        return response()->json(status: 204);
    }
}
