<?php

use App\Http\Controllers\Api\V1\AgentConnectController;
use App\Http\Controllers\Api\V1\RelicApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/agent')->middleware('throttle:agent')->group(function (): void {
    Route::post('connect', [AgentConnectController::class, 'connect']);
    Route::post('complete', [AgentConnectController::class, 'complete']);
    Route::post('heartbeat', [AgentConnectController::class, 'heartbeat']);
    Route::post('poll', [AgentConnectController::class, 'poll']);
    Route::post('ack', [AgentConnectController::class, 'acknowledge']);
});

Route::prefix('v1/relic')->middleware(['auth.api', 'throttle:api'])->group(function (): void {
    Route::get('servers', [RelicApiController::class, 'servers']);
    Route::get('migrations', [RelicApiController::class, 'migrations']);
    Route::post('tokens', [RelicApiController::class, 'createToken']);
});
