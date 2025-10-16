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
        // L'employé ou le manager peut voir le projet
        return $user->id === $project->employee_id || 
               $user->id === $project->manager_id ||
               $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Seuls les managers et les admins peuvent créer des projets
        return $user->isManager() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Le manager responsable ou un admin peut mettre à jour le projet
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // Seul l'admin peut supprimer un projet
        return $user->id === $project->user_id;
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
        // Seul le manager responsable ou un admin peut assigner des employés
        return $user->id === $project->manager_id || $user->isAdmin();
    }
}
