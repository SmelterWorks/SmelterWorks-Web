<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\HostingController;
use App\Http\Controllers\HostingFeedController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RelicController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', HomeController::class)->name('home');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('/hosting', [HostingController::class, 'index'])->name('hosting');
Route::get('/hosting/rss.xml', HostingFeedController::class)->name('hosting.feed');
Route::get('/hosting/{plan}/purchase', [HostingController::class, 'create'])->name('hosting.purchase');
Route::post('/hosting/purchase', [HostingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('hosting.purchase.store');
Route::get('/hosting/orders/{purchase}', [HostingController::class, 'show'])->name('hosting.purchases.show');

Route::get('/mods', [PageController::class, 'mods'])->name('mods');
Route::get('/relic', [RelicController::class, 'show'])->name('relic');
Route::get('/relic/download', [RelicController::class, 'download'])->name('relic.download');
Route::get('/panel', [PageController::class, 'panel'])->name('panel');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/branding', [PageController::class, 'branding'])->name('branding');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/donate', [PageController::class, 'donate'])->name('donate');
Route::get('/contribute', [PageController::class, 'contribute'])->name('contribute');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
