<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\QuinielaController;
use App\Http\Controllers\Admin\MatchEventController;
use App\Http\Controllers\Admin\QuinielaAdminController;
use App\Http\Controllers\Admin\RosterController;
use App\Http\Controllers\Admin\ScoringRuleController;
use Illuminate\Support\Facades\Route;

// --- Auth (token based) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Participant facing ---
    Route::get('/quinielas', [QuinielaController::class, 'index']);
    Route::get('/quinielas/{quiniela}', [QuinielaController::class, 'show']);
    Route::get('/quinielas/{quiniela}/leaderboard', [QuinielaController::class, 'leaderboard']);
    Route::get('/quinielas/{quiniela}/predictions', [QuinielaController::class, 'predictions']);
    Route::get('/quinielas/{quiniela}/live', [QuinielaController::class, 'live']);
    Route::put('/quinielas/{quiniela}/prediction', [PredictionController::class, 'store']);

    // --- Admin only ---
    Route::middleware('can:admin')->prefix('admin')->group(function () {
        Route::post('/quinielas', [QuinielaAdminController::class, 'store']);
        Route::put('/quinielas/{quiniela}', [QuinielaAdminController::class, 'update']);
        Route::delete('/quinielas/{quiniela}', [QuinielaAdminController::class, 'destroy']);
        Route::put('/quinielas/{quiniela}/status', [QuinielaAdminController::class, 'updateStatus']);
        Route::put('/quinielas/{quiniela}/result', [QuinielaAdminController::class, 'updateResult']);
        Route::post('/quinielas/{quiniela}/sync', [QuinielaAdminController::class, 'sync']);
        Route::post('/quinielas/{quiniela}/reset', [QuinielaAdminController::class, 'reset']);
        Route::post('/quinielas/{quiniela}/simulate', [QuinielaAdminController::class, 'simulate']);

        Route::put('/quinielas/{quiniela}/rules', [ScoringRuleController::class, 'update']);

        Route::post('/quinielas/{quiniela}/players', [RosterController::class, 'store']);
        Route::delete('/players/{player}', [RosterController::class, 'destroy']);

        Route::post('/quinielas/{quiniela}/events', [MatchEventController::class, 'store']);
        Route::delete('/events/{event}', [MatchEventController::class, 'destroy']);
    });
});
