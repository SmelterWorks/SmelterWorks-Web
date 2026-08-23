<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DaemonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\ModController;
use App\Http\Controllers\ServerActionController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubuserController;
use App\Http\Controllers\TotpController;
use App\Http\Middleware\RequireStepUp;
use Illuminate\Support\Facades\Route;

$metricsRoute = (string) config('metrics.route', '/metrics');

Route::middleware('metrics.token')->get($metricsRoute, MetricsController::class)->name('metrics');

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:login');
    Route::get('totp/challenge', [TotpController::class, 'challenge'])->name('totp.challenge');
    Route::post('totp/challenge', [TotpController::class, 'verifyChallenge'])->name('totp.verify');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('security/totp', [TotpController::class, 'show'])->name('totp.show');
    Route::post('security/totp/begin', [TotpController::class, 'begin'])->name('totp.begin');
    Route::post('security/totp/confirm', [TotpController::class, 'confirm'])->name('totp.confirm');
    Route::post('security/totp/disable', [TotpController::class, 'disable'])->name('totp.disable');

    Route::get('subusers', [SubuserController::class, 'index'])->name('subusers.index');
    Route::post('subusers', [SubuserController::class, 'store'])->name('subusers.store');
    Route::delete('subusers/{subuser}', [SubuserController::class, 'destroy'])->name('subusers.destroy');

    Route::get('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::get('billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::post('servers/{server}/billing/subscribe', [BillingController::class, 'subscribe'])->name('servers.billing.subscribe');

    Route::get('servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::post('servers', [ServerController::class, 'store'])->name('servers.store');
    Route::post('servers/{server}/daemon', [ServerActionController::class, 'linkDaemon'])->name('servers.daemon.link');
    Route::post('servers/{server}/power/{action}', [ServerActionController::class, 'power'])->name('servers.power');
    Route::post('servers/{server}/backup', [ServerActionController::class, 'backup'])->name('servers.backup');

    Route::get('servers/{server}/files', [FileController::class, 'index'])->name('servers.files');
    Route::post('servers/{server}/files/list', [FileController::class, 'list'])->name('servers.files.list');
    Route::post('servers/{server}/files/upload', [FileController::class, 'upload'])->name('servers.files.upload');

    Route::get('daemons/pairing', [DaemonController::class, 'pairing'])->name('daemons.pairing');
    Route::post('daemons', [DaemonController::class, 'create'])->name('daemons.store');

    Route::middleware(RequireStepUp::class)->group(function (): void {
        Route::post('servers/{server}/migrate', [MigrationController::class, 'store'])->name('servers.migrate');
        Route::get('servers/{server}/mods', [ModController::class, 'index'])->name('servers.mods');
        Route::post('servers/{server}/mods/install', [ModController::class, 'install'])->name('servers.mods.install');
    });
});
