<?php

use App\Http\Controllers\Api\DefaultsController;
use App\Http\Controllers\Api\ExperimentController;
use App\Http\Controllers\Api\GenerationController;
use App\Http\Controllers\Api\ModelController;
use App\Http\Controllers\Api\PromptController;
use App\Http\Controllers\Api\Admin\ActionLogController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('generations', GenerationController::class)->only(['index', 'show', 'destroy']);
    Route::post('generate', [GenerationController::class, 'generate']);
    Route::get('generations/{generation}/status', [GenerationController::class, 'status']);
    Route::post('generations/{generation}/share', [GenerationController::class, 'toggleShare']);
    Route::delete('generations/{generation}/image', [GenerationController::class, 'deleteImage']);
    Route::get('queue/stats', [GenerationController::class, 'queueStats']);

    Route::apiResource('models', ModelController::class);
    Route::post('models/{model}/toggle-active', [ModelController::class, 'toggleActive']);
    Route::apiResource('prompts', PromptController::class);
    Route::post('prompts/{prompt}/toggle-active', [PromptController::class, 'toggleActive']);

    Route::get('defaults', [DefaultsController::class, 'index']);

    Route::get('experiments', [ExperimentController::class, 'index']);
    Route::post('experiments', [ExperimentController::class, 'generate']);
    Route::delete('experiments/{experiment}', [ExperimentController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('logs', [ActionLogController::class, 'index']);
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
});
