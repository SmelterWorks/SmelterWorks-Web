<?php

use App\Http\Controllers\ServerListApiController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/servers/list', ServerListApiController::class)
    ->middleware('throttle:60,1')
    ->name('api.servers.list');
