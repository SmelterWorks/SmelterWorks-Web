<?php

use App\Http\Controllers\Api\V1\AgentConnectController;
use App\Http\Controllers\Api\V1\ProvisionController;
use App\Http\Controllers\Api\V1\RelicApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/agent')->middleware('throttle:agent')->group(function (): void {
    Route::post('connect', [AgentConnectController::class, 'connect']);
    Route::post('complete', [AgentConnectController::class, 'complete']);
    Route::post('heartbeat', [AgentConnectController::class, 'heartbeat']);
    Route::post('poll', [AgentConnectController::class, 'poll']);
    Route::post('ack', [AgentConnectController::class, 'acknowledge']);
});

Route::post('v1/provision', [ProvisionController::class, 'store'])
    ->middleware('throttle:api');

Route::prefix('v1/relic')->middleware(['auth.api', 'throttle:api'])->group(function (): void {
    Route::get('servers', [RelicApiController::class, 'servers'])
        ->middleware('api.ability:relic:read');
    Route::get('migrations', [RelicApiController::class, 'migrations'])
        ->middleware('api.ability:relic:read');
    Route::get('servers/{server}/console/logs', [RelicApiController::class, 'consoleLogs'])
        ->middleware('api.ability:relic:console');
    Route::post('tokens', [RelicApiController::class, 'createToken'])
        ->middleware('api.ability:relic:read');
});
