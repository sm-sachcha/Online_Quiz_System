<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AntiCheatController;
use App\Http\Controllers\Api\LeaderboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public API endpoints
    Route::get('leaderboard/global', [LeaderboardController::class, 'getGlobalLeaderboard']);
    Route::get('leaderboard/quiz/{quiz}', [LeaderboardController::class, 'getQuizLeaderboard']);
    
    // Protected API endpoints (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Anti-cheat endpoints
        Route::prefix('anti-cheat')->name('anti-cheat.')->group(function () {
            Route::post('tab-switch', [AntiCheatController::class, 'reportTabSwitch']);
            Route::post('suspicious', [AntiCheatController::class, 'reportSuspiciousActivity']);
            Route::get('status/{attempt}', [AntiCheatController::class, 'getCheatingStatus']);
        });
        
        // Leaderboard endpoints
        Route::prefix('leaderboard')->name('leaderboard.')->group(function () {
            Route::get('my-rank', [LeaderboardController::class, 'getUserRank']);
            Route::get('quiz/{quiz}', [LeaderboardController::class, 'getQuizLeaderboard']);
        });
    });
});