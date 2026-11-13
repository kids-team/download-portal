<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDashboardLayoutRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardLayoutController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => ['widgets' => $request->user()->dashboard_widget_order]]);
    }

    public function update(UpdateDashboardLayoutRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->dashboard_widget_order = $request->validated('widgets');
        $user->save();

        return response()->json(['data' => ['widgets' => $user->dashboard_widget_order]]);
    }
}
