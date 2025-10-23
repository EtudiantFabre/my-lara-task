<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir leurs projets
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // L'utilisateur propriétaire peut voir le projet
        return $user->id === $project->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Tout utilisateur authentifié peut créer ses projets (MVP)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        // Seul l'admin peut restaurer un projet supprimé
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        // Seul l'admin peut supprimer définitivement un projet
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can assign employees to the project.
     */
    public function assignEmployee(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->isAdmin();
    }
}
