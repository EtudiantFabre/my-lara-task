<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // Alerte 50% du temps estimé du projet écoulé
        $schedule->call(function () {
            \App\Models\Project::query()
                ->whereNotNull('start_date')
                ->whereNotNull('deadline')
                ->whereIn('status', ['not_started', 'in_progress'])
                ->chunk(100, function ($projects) {
                    foreach ($projects as $project) {
                        $start = \Carbon\Carbon::parse($project->start_date);
                        $end = \Carbon\Carbon::parse($project->deadline);
                        if ($end->lessThanOrEqualTo($start)) {
                            continue;
                        }
                        $midpoint = $start->copy()->addSeconds($end->diffInSeconds($start) / 2);
                        if (now()->betweenIncluded($midpoint->copy()->subHour(), $midpoint)) {
                            \App\Models\Notification::firstOrCreate([
                                'user_id' => $project->user_id,
                                'type' => 'app',
                                'title' => '50% du temps du projet écoulé',
                                'message' => "Le projet '{$project->title}' a atteint 50% du temps estimé.",
                                'notifiable_id' => $project->id,
                                'notifiable_type' => \App\Models\Project::class,
                            ]);
                        }
                    }
                });
        })->hourly();

        // Alerte J-1 pour projets et tâches
        $schedule->call(function () {
            $tomorrowStart = now()->addDay()->startOfDay();
            $tomorrowEnd = now()->addDay()->endOfDay();

            \App\Models\Project::whereBetween('deadline', [$tomorrowStart, $tomorrowEnd])
                ->chunk(100, function ($projects) {
                    foreach ($projects as $project) {
                        \App\Models\Notification::firstOrCreate([
                            'user_id' => $project->user_id,
                            'type' => 'app',
                            'title' => 'Échéance projet imminente',
                            'message' => "Le projet '{$project->title}' arrive à échéance demain.",
                            'notifiable_id' => $project->id,
                            'notifiable_type' => \App\Models\Project::class,
                        ]);
                    }
                });

            \App\Models\Task::whereDate('due_date', $tomorrowStart->toDateString())
                ->chunk(100, function ($tasks) {
                    foreach ($tasks as $task) {
                        \App\Models\Notification::firstOrCreate([
                            'user_id' => $task->assigned_to,
                            'type' => 'app',
                            'title' => 'Échéance de tâche imminente',
                            'message' => "La tâche '{$task->title}' arrive à échéance demain.",
                            'notifiable_id' => $task->id,
                            'notifiable_type' => \App\Models\Task::class,
                        ]);
                    }
                });
        })->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
