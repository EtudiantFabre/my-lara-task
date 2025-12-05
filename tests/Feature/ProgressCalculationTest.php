<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\SubTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(): User
    {
        return User::factory()->create();
    }

    protected function createProject(User $owner): Project
    {
        return Project::create([
            'title' => 'Demo Project',
            'description' => null,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addDays(7)->toDateString(),
            'status' => 'not_started',
            'progress' => 0,
            'user_id' => $owner->id,
        ]);
    }

    protected function createTask(Project $project, User $user, int $subtaskCount = 0): Task
    {
        $task = Task::create([
            'title' => 'Task',
            'description' => null,
            'estimated_time' => 0,
            'time_spent' => 0,
            'progress' => 0,
            'status' => 'not_started',
            'due_date' => now()->addDays(3)->toDateString(),
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
        ]);

        for ($i = 0; $i < $subtaskCount; $i++) {
            SubTask::create([
                'title' => 'ST '.$i,
                'description' => null,
                'estimated_time' => 1,
                'time_spent' => 0,
                'status' => 'not_started',
                'due_date' => now()->addDays(2)->toDateString(),
                'task_id' => $task->id,
                'assigned_to' => $user->id,
            ]);
        }

        return $task->fresh();
    }

    public function test_task_progress_updates_with_subtasks()
    {
        $user = $this->createUser();
        $project = $this->createProject($user);
        $task = $this->createTask($project, $user, 5);

        $this->assertEquals(0.0, (float) $task->progress);

        // complete 2 subtasks => 40%
        $subs = $task->subTasks()->get();
        $subs[0]->update(['status' => 'completed']);
        $subs[1]->update(['status' => 'completed']);
        $task->refresh();
        $this->assertEquals(40.0, round((float) $task->progress, 1));

        // complete all => 100%
        foreach ($subs as $idx => $st) {
            $st->refresh();
            $st->update(['status' => 'completed']);
        }
        $task->refresh();
        $this->assertEquals(100.0, (float) $task->progress);
    }

    public function test_project_reaches_90_when_all_tasks_complete_and_100_after_review()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $project = $this->createProject($user);

        // two tasks with 3 subtasks each
        $t1 = $this->createTask($project, $user, 3);
        $t2 = $this->createTask($project, $user, 3);

        // complete all subtasks
        foreach ([$t1, $t2] as $t) {
            foreach ($t->subTasks as $st) {
                $st->update(['status' => 'completed']);
            }
        }

        $project->refresh();
        $this->assertEquals(90.0, (float) $project->progress, 'Project should be at 90% after all tasks complete');
        $this->assertFalse((bool) $project->reviewed);

        // call review completion endpoint
        $response = $this->post(route('projects.review.complete', $project));
        $response->assertRedirect(route('projects.show', $project));

        $project->refresh();
        $this->assertTrue((bool) $project->reviewed);
        $this->assertEquals(100.0, (float) $project->progress);
        $this->assertEquals('completed', $project->status);
    }
}
