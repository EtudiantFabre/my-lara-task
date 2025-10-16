<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use Inertia\Inertia;


class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $stats = [
            'total_projects' => $user->projects()->count(),
            'total_tasks' => $user->assignedTasks()->count(),
            'completed_tasks' => $user->assignedTasks()->where('status', 'completed')->count(),
        ];

        $recentProjects = $user->projects()
            ->withCount('tasks')
            ->latest()
            ->take(5)
            ->get();

        $upcomingTasks = $user->assignedTasks()
            ->with(['project' => function($query) {
                $query->select('id', 'name');
            }])
            ->where('status', 'in_progress')
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->take(10)
            ->get()
            ->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $task->due_date,
                'completed_at' => $task->completed_at,
                'project' => $task->project ? [
                    'id' => $task->project->id,
                    'name' => $task->project->name
                ] : null
            ];
        });

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentProjects' => $recentProjects,
            'upcomingTasks' => $upcomingTasks,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
