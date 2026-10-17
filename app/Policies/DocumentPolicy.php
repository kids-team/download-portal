<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageDocuments($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        return $this->canManageDocuments($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageDocuments($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): Response
    {
        if ($user->role === UserRole::Admin) {
            return Response::allow();
        }

        if ($user->role !== UserRole::Editor) {
            return Response::deny();
        }

        return $document->is_locked
            ? Response::deny('Dieses Dokument ist gesperrt.')
            : Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): Response
    {
        if ($user->role !== UserRole::Admin) {
            return Response::deny('Nur Administratoren dürfen Dokumente löschen.');
        }

        return $document->children()->exists()
            ? Response::deny('Dokumente mit Unterdokumenten können nicht gelöscht werden.')
            : Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }

    private function canManageDocuments(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Editor], true);
    }
}
