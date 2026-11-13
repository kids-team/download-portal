<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Admin\ProfileResource;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        /** @var User $user */
        $user = $request->user();

        return new ProfileResource($user);
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        /** @var User $user */
        $user = $request->user();
        $attributes = $request->safe()->only(['name', 'email', 'locale', 'password']);

        if (($attributes['password'] ?? null) === null) {
            unset($attributes['password']);
        }

        $user->update($attributes);

        return new ProfileResource($user->refresh());
    }
}
