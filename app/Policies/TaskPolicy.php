<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir leurs tâches
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // L'employé assigné, le manager du projet ou un admin peut voir la tâche
        return $user->id === $task->assigned_to || 
               $user->id === $task->project->manager_id ||
               $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Seuls les managers et les admins peuvent créer des tâches
        return $user->isManager() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        // L'employé assigné, le manager du projet ou un admin peut mettre à jour la tâche
        return $user->id === $task->assigned_to || 
               $user->id === $task->project->manager_id ||
               $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        // Seul le manager du projet ou un admin peut supprimer une tâche
        return $user->id === $task->project->manager_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        // Seul l'admin peut restaurer une tâche supprimée
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        // Seul l'admin peut supprimer définitivement une tâche
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can assign the task to an employee.
     */
    public function assignEmployee(User $user, Task $task): bool
    {
        // Seul le manager du projet ou un admin peut assigner des tâches
        return $user->id === $task->project->manager_id || $user->isAdmin();
    }
}
