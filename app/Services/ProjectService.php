<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjectService
{
    /**
     * Get all projects for the authenticated user
     */
    public function getUserProjects(User $user)
    {
        return Project::where('user_id', $user->id)
            ->withCount('tasks')
            ->with('tasks')
            ->latest()
            ->get();
    }

    /**
     * Get a single project with its tasks and subtasks
     */
    public function getProjectWithTasks(Project $project)
    {
        $project->load(['tasks' => function($query) {
            $query->with('subTasks')->orderBy('created_at', 'desc');
        }]);
        
        return $project;
    }

    /**
     * Create a new project
     */
    public function createProject(array $data, User $user): Project
    {
        $projectData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'deadline' => $data['deadline'],
            'estimated_time' => $data['estimated_time'] ?? 0,
            'time_spent' => 0,
            'status' => $data['status'] ?? 'not_started',
            'progress' => $data['progress'] ?? 0,
            'reviewed' => false,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'user_id' => $user->id,
        ];

        $project = $user->ownedProjects()->create($projectData);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($project)
            ->withProperties(['attributes' => $projectData])
            ->log('created');

        return $project;
    }

    /**
     * Update an existing project
     */
    public function updateProject(Project $project, array $data): Project
    {
        $project->update($data);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($project)
            ->withProperties(['changes' => $data])
            ->log('updated');

        return $project->fresh();
    }

    /**
     * Delete a project
     */
    public function deleteProject(Project $project): bool
    {
        // Log activity before deletion
        activity()
            ->causedBy(Auth::user())
            ->performedOn($project)
            ->log('deleted');

        return $project->delete();
    }

    /**
     * Assign an employee to a project
     */
    public function assignEmployee(Project $project, int $employeeId): Project
    {
        // Check if employee is already assigned
        if ($project->employee_id === $employeeId) {
            throw new \Exception('This employee is already assigned to the project.');
        }

        $oldEmployeeId = $project->employee_id;
        $project->update(['employee_id' => $employeeId]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($project)
            ->withProperties([
                'old_employee_id' => $oldEmployeeId,
                'new_employee_id' => $employeeId
            ])
            ->log('employee_changed');

        return $project->load('employee');
    }

    /**
     * Get status options
     */
    public function getStatusOptions(): array
    {
        return [
            'not_started' => 'Non commencé',
            'in_progress' => 'En cours',
            'on_hold' => 'En attente',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé'
        ];
    }
}
