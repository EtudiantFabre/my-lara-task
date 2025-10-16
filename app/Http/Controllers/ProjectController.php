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
        return Inertia::render('Projects/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Début de la création du projet', ['data' => $request->all()]);
        //printf($request);
        try {
            $user = $request->user();
            \Log::info('Utilisateur récupéré', ['user_id' => $user ? $user->id : null]);
            
            if (!$user) {
                \Log::error('Erreur: Utilisateur non authentifié');
                return redirect()->back()
                    ->with('error', 'Utilisateur non authentifié');
            }

            \Log::info('Validation des données...');
            $validated = $request->validate([
                'title' => 'required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'start_date' => 'required|date',
                'deadline' => 'required|date|after:start_date'
            ]);
            \Log::info('Données validées avec succès', $validated);

            \Log::info('Création du projet...');
            $projectData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'deadline' => $validated['deadline'],
                'status' => 'not_started',
                'progress' => 0,
                'user_id' => $user->id
            ];
            \Log::info('Données du projet préparées', $projectData);

            $project = Project::create($projectData);
            \Log::info('Projet créé avec succès', ['project_id' => $project->id]);

            return redirect()->route('projects.index')
                ->with('success', 'Projet créé avec succès !');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Erreur de validation', ['errors' => $e->errors()]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du projet', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Erreur lors de la création du projet: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): JsonResponse
    {
        //$this->authorize('view', $project);
        
        // Vérifier que l'utilisateur est bien le propriétaire du projet
        if ($project->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        return response()->json(new ProjectResource($project->load('tasks')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        //$this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'deadline' => 'sometimes|date|after:start_date',
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'on_hold', 'completed', 'cancelled'])],
            'progress' => 'sometimes|numeric|min:0|max:100',
            'employee_id' => [
                'sometimes',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($project) {
                    if ($value === $project->manager_id) {
                        $fail('The employee cannot be the same as the project manager.');
                    }
                },
            ],
        ]);

        try {
            $project->update($validated);

            // Enregistrer l'activité
            activity()
                ->causedBy(Auth::user())
                ->performedOn($project)
                ->withProperties(['changes' => $validated])
                ->log('updated');

            return response()->json([
                'message' => 'Project updated successfully',
                'data' => new ProjectResource($project->load(['employee', 'manager']))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating project: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): JsonResponse
    {
        //$this->authorize('delete', $project);

        try {
            // Enregistrer l'activité avant la suppression
            activity()
                ->causedBy(Auth::user())
                ->performedOn($project)
                ->log('deleted');

            $project->delete();

            return response()->json([
                'message' => 'Project deleted successfully'
            ], 204);
            
        } catch (\Exception $e) {
            Log::error('Error deleting project: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error deleting project',
                'error' => $e->getMessage()
            ], 500);
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
