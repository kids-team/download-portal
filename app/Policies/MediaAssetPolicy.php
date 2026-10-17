<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Access\Response;

class MediaAssetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageMedia($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $this->canManageMedia($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageMedia($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MediaAsset $mediaAsset): Response
    {
        return $user->role === UserRole::Admin
            ? Response::allow()
            : Response::deny('Nur Administratoren dürfen die Sichtbarkeit von Medien ändern.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaAsset $mediaAsset): Response
    {
        return $user->role === UserRole::Admin
            ? Response::allow()
            : Response::deny('Nur Administratoren dürfen Medien löschen.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MediaAsset $mediaAsset): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MediaAsset $mediaAsset): bool
    {
        return false;
    }

    private function canManageMedia(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Editor], true);
    }
}
