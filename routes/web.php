<?php

use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\Auth\GoogleCalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubTaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeTrackingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Page d'accueil publique
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Tableau de bord
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/upcoming-tasks', [DashboardController::class, 'upcomingTasks']);
    Route::get('/dashboard/recent-activities', [DashboardController::class, 'recentActivities']);

    // Profil utilisateur
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projets
    Route::resource('projects', ProjectController::class);
    Route::get('projects/{project}/report', [ProjectController::class, 'report'])->name('projects.report');
    
    // Tâches
    Route::resource('projects.tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('tasks/{task}/complete', [TaskController::class, 'toggleComplete'])->name('tasks.complete');
    Route::post('tasks/{task}/start-timer', [TaskController::class, 'startTimer'])->name('tasks.timer.start');
    Route::post('tasks/{task}/stop-timer', [TaskController::class, 'stopTimer'])->name('tasks.timer.stop');
    
    // Sous-tâches
    Route::prefix('tasks/{task}')->group(function () {
        Route::post('/subtasks', [SubTaskController::class, 'store'])->name('subtasks.store');
        Route::patch('/subtasks/{subTask}', [SubTaskController::class, 'update'])->name('subtasks.update');
        Route::delete('/subtasks/{subTask}', [SubTaskController::class, 'destroy'])->name('subtasks.destroy');
        Route::patch('/subtasks/{subTask}/toggle', [SubTaskController::class, 'toggle'])->name('subtasks.toggle');
    });

    // Gestion des positions (drag & drop)
    Route::patch('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::patch('/subtasks/{subTask}/move', [SubTaskController::class, 'move'])->name('subtasks.move');

    // Suivi du temps
    Route::resource('time-entries', TimeEntryController::class);
    Route::get('time-entries/by-date/{date}', [TimeEntryController::class, 'byDate']);
    Route::get('time-entries/by-project/{project}', [TimeEntryController::class, 'byProject']);
    Route::get('time-entries/by-task/{task}', [TimeEntryController::class, 'byTask']);
    Route::post('time-entries/start', [TimeEntryController::class, 'start']);
    Route::post('time-entries/{timeEntry}/stop', [TimeEntryController::class, 'stop']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Rapports
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/productivity', [ReportController::class, 'productivity'])->name('reports.productivity');
        Route::get('/time-entries', [ReportController::class, 'timeEntries'])->name('reports.time-entries');
        Route::get('/export', [ReportController::class, 'export'])->name('reports.export');
    });

    // Intégration Google Calendar
    Route::prefix('google-calendar')->group(function () {
        Route::get('/connect', [GoogleCalendarController::class, 'connect'])->name('google-calendar.connect');
        Route::get('/callback', [GoogleCalendarController::class, 'callback'])->name('google-calendar.callback');
        Route::delete('/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google-calendar.disconnect');
        Route::post('/sync/{project}', [GoogleCalendarController::class, 'syncProject'])->name('google-calendar.sync');
    });
});

// Webhooks pour les notifications
Route::post('/webhooks/email-opened', [NotificationController::class, 'handleEmailOpened'])
    ->name('webhooks.email-opened')
    ->withoutMiddleware(['web', 'csrf']);

// Route pour les erreurs 404 (doit être la dernière route)
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

require __DIR__.'/auth.php';
