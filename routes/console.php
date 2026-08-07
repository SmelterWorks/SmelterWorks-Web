<?php

use App\Support\Currency\ExchangeRateService;
use App\Support\Servers\MasterServerListService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(ExchangeRateService::class)->warmCache();
})->everySixHours();

Schedule::call(function (): void {
    app(MasterServerListService::class)->warmCache();
})->everyTwoMinutes();
