<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\LogController;
use App\Models\User;
use App\Models\Task;

Route::post('/login', [UserController::class, 'login']);

// Routes tanpa middleware checkUserStatus untuk memungkinkan admin mengedit user (termasuk status)
Route::middleware(['auth:sanctum', 'logRequest'])->group(function () {
    // User Routes
    Route::get('/users', [UserController::class, 'index'])->middleware('can:viewAny,App\Models\User');
    Route::post('/users', [UserController::class, 'store'])->middleware('can:create,App\Models\User');

    // Binding spesifik untuk route user
    Route::bind('user', function ($value) {
        return User::findOrFail($value);
    });

    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('can:update,user');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('can:delete,user');

    // Task Routes
    Route::bind('task', function ($value) {
        return Task::findOrFail($value);
    });

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    Route::get('/logs', [LogController::class, 'index'])->middleware('can:viewAny,App\Models\ActivityLog');
});