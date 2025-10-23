<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'reset']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    // Route::put('/user/profile', [UserController::class, 'updateProfile']);
    // Route::put('/user/password', [UserController::class, 'updatePassword']);

    // Projets
    Route::apiResource('projects', ProjectController::class);
    
    // Tâches (API minimale pour MVP)
    Route::apiResource('tasks', TaskController::class)->only(['index', 'show', 'store', 'update', 'destroy']);


    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Rapports
    Route::prefix('reports')->group(function () {
        Route::get('productivity', [ReportController::class, 'productivity']);
        Route::get('time-usage', [ReportController::class, 'timeUsage']);
        Route::get('project-progress', [ReportController::class, 'projectProgress']);
        Route::get('user-performance', [ReportController::class, 'userPerformance']);
        Route::get('export', [ReportController::class, 'export']);
    });

    // Recherche
    //Route::get('search', [\App\Http\Controllers\Api\SearchController::class, 'index']);
});

// Webhooks
Route::post('/webhooks/email-opened', [NotificationController::class, 'handleEmailOpened'])
    ->name('api.webhooks.email-opened')
    ->withoutMiddleware(['auth:sanctum']);
