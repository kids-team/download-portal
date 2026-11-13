<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Http\Resources\Api\V1\Admin\ManagedUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return ManagedUserResource::collection(User::query()->orderBy('name')->paginate(100));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreManagedUserRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'role', 'password']));

        return (new ManagedUserResource($user))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function update(UpdateManagedUserRequest $request, User $user): ManagedUserResource
    {
        $attributes = $request->safe()->only(['name', 'email', 'role', 'password']);

        if (($attributes['password'] ?? null) === null) {
            unset($attributes['password']);
        }

        $user->update($attributes);

        return new ManagedUserResource($user->refresh());
    }
}
