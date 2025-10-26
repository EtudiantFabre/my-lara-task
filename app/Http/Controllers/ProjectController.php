<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $projects = Project::where('user_id', $user->id)
            ->withCount('tasks')
            ->with('tasks')
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'deadline' => 'required|date|after:start_date',
            'progress' => 'required|numeric|min:0|max:100',
        ]);

        print_r("Données validées : ");
        print_r($validated);

        // Use ownedProjects (one-to-many) to avoid inserting into pivot table
        $project = $request->user()->ownedProjects()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'deadline' => $validated['deadline'],
            'estimated_time' => 0,
            'time_spent' => 0,
            'status' => 'not_started',
            'progress' => $validated['progress'],
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('projects.index')
                ->with('success', 'Projet créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        // Charger les tâches et leurs sous-tâches avec un tri par date
        $project->load(['tasks' => function($query) {
            $query->with('subTasks')->orderBy('created_at', 'desc');
        }]);
        
        return Inertia::render('Projects/Show', [
            'project' => $project,
            'canEdit' => true, // ou une logique de permission plus avancée
            'statusOptions' => [
                'not_started' => 'Non commencé',
                'in_progress' => 'En cours',
                'on_hold' => 'En attente',
                'completed' => 'Terminé',
                'cancelled' => 'Annulé'
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        //$this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'deadline' => 'required|date|after:start_date',
            'status' => 'required|in:not_started,in_progress,on_hold,completed,cancelled',
            'progress' => 'required|integer|min:0|max:100'
        ]);

        $project->update($validated);

        return back()->with('success', 'Projet mis à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        try {
            // Enregistrer l'activité avant la suppression
            activity()
                ->causedBy(auth()->user())
                ->performedOn($project)
                ->log('deleted');

            $project->delete();

            return redirect()->route('projects.index')
                ->with('success', 'Projet supprimé avec succès');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du projet: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la suppression du projet');
        }
    }
    
    /**
     * Assign an employee to the project.
     */
    public function assignEmployee(Request $request, Project $project): JsonResponse
    {
        //$this->authorize('assignEmployee', $project);
        
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
        ]);
        
        // Vérifier que l'employé n'est pas déjà assigné au projet
        if ($project->employee_id === $validated['employee_id']) {
            return response()->json([
                'message' => 'This employee is already assigned to the project.'
            ], 422);
        }
        
        try {
            $oldEmployeeId = $project->employee_id;
            $project->update(['employee_id' => $validated['employee_id']]);
            
            // Enregistrer l'activité
            activity()
                ->causedBy(Auth::user())
                ->performedOn($project)
                ->withProperties([
                    'old_employee_id' => $oldEmployeeId,
                    'new_employee_id' => $validated['employee_id']
                ])
                ->log('employee_changed');
                
            return response()->json([
                'message' => 'Employee assigned to project successfully',
                'data' => new ProjectResource($project->load('employee'))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error assigning employee to project: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error assigning employee to project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
