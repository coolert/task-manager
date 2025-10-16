<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskLabelController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->withoutMiddleware('jwt.auth');
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->middleware('project.member')
        ->name('projects.show');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])
        ->middleware('project.member')
        ->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
        ->middleware('project.member')
        ->name('projects.destroy');

    // Project Actions
    Route::post('/projects/{project}/owner', [ProjectController::class, 'transferOwnership'])
        ->middleware('project.member')
        ->name('projects.transfer');

    Route::middleware('project.member')->group(function () {
        // Project Members
        Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index'])->name('projects.members.index');
        Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
        Route::patch('/projects/{project}/members/{project_member}', [ProjectMemberController::class, 'update'])->name('projects.members.update');
        Route::delete('/projects/{project}/members/{project_member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');

        // Project Labels
        Route::get('/projects/{project}/labels', [LabelController::class, 'index'])->name('projects.labels.index');
        Route::post('/projects/{project}/labels', [LabelController::class, 'store'])->name('projects.labels.store');
        Route::patch('/projects/{project}/labels/{label}', [LabelController::class, 'update'])->name('projects.labels.update');
        Route::delete('/projects/{project}/labels/{label}', [LabelController::class, 'destroy'])->name('projects.labels.destroy');

        // Task
        Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->name('projects.tasks.index');
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        // Task Actions
        Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
        Route::post('/tasks/{task}/claim', [TaskController::class, 'claim'])->name('tasks.claim');

        // Task Comments
        Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');

        // Task Labels
        Route::post('/tasks/{task}/labels/{label}', [TaskLabelController::class, 'attach'])->name('tasks.labels.attach');
        Route::delete('/tasks/{task}/labels/{label}', [TaskLabelController::class, 'detach'])->name('tasks.labels.detach');
    });
});
