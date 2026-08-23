<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DaemonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\ModController;
use App\Http\Controllers\ServerController;
use App\Http\Middleware\RequireStepUp;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::post('servers', [ServerController::class, 'store'])->name('servers.store');

    Route::get('daemons/pairing', [DaemonController::class, 'pairing'])->name('daemons.pairing');
    Route::post('daemons', [DaemonController::class, 'create'])->name('daemons.store');

    Route::middleware(RequireStepUp::class)->group(function (): void {
        Route::post('servers/{server}/migrate', [MigrationController::class, 'store'])->name('servers.migrate');
        Route::get('servers/{server}/mods', [ModController::class, 'index'])->name('servers.mods');
        Route::post('servers/{server}/mods/install', [ModController::class, 'install'])->name('servers.mods.install');
    });
});
