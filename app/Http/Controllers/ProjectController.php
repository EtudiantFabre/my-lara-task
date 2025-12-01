<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProjectController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $projects = $this->projectService->getUserProjects($user);

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
            'status' => 'sometimes|in:not_started,in_progress,on_hold,completed,cancelled',
            'estimated_time' => 'sometimes|numeric|min:0',
        ]);

        try {
            $project = $this->projectService->createProject($validated, $request->user());

            return redirect()->route('projects.index')
                ->with('success', 'Projet créé avec succès');
        } catch (\Exception $e) {
            Log::error('Error creating project: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la création du projet']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $project = $this->projectService->getProjectWithTasks($project);
        
        return Inertia::render('Projects/Show', [
            'project' => $project,
            'canEdit' => true,
            'statusOptions' => $this->projectService->getStatusOptions()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'deadline' => 'required|date|after:start_date',
            'status' => 'required|in:not_started,in_progress,on_hold,completed,cancelled',
            'progress' => 'required|integer|min:0|max:100'
        ]);

        try {
            $this->projectService->updateProject($project, $validated);

            return back()->with('success', 'Projet mis à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Error updating project: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du projet']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        try {
            $this->projectService->deleteProject($project);

            return redirect()->route('projects.index')
                ->with('success', 'Projet supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('Error deleting project: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la suppression du projet');
        }
    }
    
    /**
     * Assign an employee to the project.
     */
    public function assignEmployee(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
        ]);
        
        try {
            $project = $this->projectService->assignEmployee($project, $validated['employee_id']);
            
            return response()->json([
                'message' => 'Employee assigned to project successfully',
                'data' => new ProjectResource($project)
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning employee to project: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getMessage() === 'This employee is already assigned to the project.' ? 422 : 500);
        }
    }
}
