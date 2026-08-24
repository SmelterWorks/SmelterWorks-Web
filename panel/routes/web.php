<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\AltchaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DaemonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\ModController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\ServerActionController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubuserController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TotpController;
use App\Http\Middleware\RequireStepUp;
use Illuminate\Support\Facades\Route;

$metricsRoute = (string) config('metrics.route', '/metrics');

Route::middleware('metrics.token')->get($metricsRoute, MetricsController::class)->name('metrics');

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('altcha/challenge', [AltchaController::class, 'challenge'])->name('altcha.challenge');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::get('totp/challenge', [TotpController::class, 'challenge'])->name('totp.challenge');
    Route::post('totp/challenge', [TotpController::class, 'verifyChallenge'])->name('totp.verify');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:verification'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:verification')
        ->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('servers/purchase', [PurchaseController::class, 'index'])->name('servers.purchase');
        Route::post('servers/purchase', [PurchaseController::class, 'store'])->name('servers.purchase.store');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings/organization', [SettingsController::class, 'updateOrganization'])->name('settings.organization');
        Route::get('settings/security', [SecurityController::class, 'show'])->name('settings.security');
        Route::post('settings/security/begin', [SecurityController::class, 'begin'])->name('settings.security.begin');
        Route::post('settings/security/confirm', [SecurityController::class, 'confirm'])->name('settings.security.confirm');
        Route::post('settings/security/disable', [SecurityController::class, 'disable'])->name('settings.security.disable');
        Route::get('settings/sessions', [SessionController::class, 'index'])->name('settings.sessions');
        Route::delete('settings/sessions/others', [SessionController::class, 'destroyOthers'])->name('settings.sessions.destroy-others');
        Route::delete('settings/sessions/{session}', [SessionController::class, 'destroy'])->name('settings.sessions.destroy');

        Route::get('security/totp', fn () => redirect()->route('settings.security'))->name('totp.show');

        Route::get('subusers', [SubuserController::class, 'index'])->name('subusers.index');
        Route::post('subusers', [SubuserController::class, 'store'])->name('subusers.store');
        Route::delete('subusers/{subuser}', [SubuserController::class, 'destroy'])->name('subusers.destroy');

        Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('support/create', [SupportTicketController::class, 'create'])->name('support.create');
        Route::post('support', [SupportTicketController::class, 'store'])->name('support.store');
        Route::get('support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('support/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
        Route::get('support/tickets/{ticket}/poll', [SupportTicketController::class, 'poll'])->name('support.poll');

        Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('chat/rooms/{room}', [ChatController::class, 'show'])->name('chat.show');
        Route::post('chat/rooms/{room}', [ChatController::class, 'store'])->name('chat.store');
        Route::get('chat/rooms/{room}/poll', [ChatController::class, 'poll'])->name('chat.poll');

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

        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
            Route::get('tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
            Route::put('tickets/{ticket}', [AdminTicketController::class, 'update'])->name('tickets.update');
            Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
        });
    });
});
