<?php

use App\Support\Currency\ExchangeRateService;
use App\Support\Servers\MasterServerListService;
use App\Support\Updates\UpdateMirrorService;
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

$updateWarmMinutes = max(5, (int) config('smelterworks.updates.warm_interval_minutes', 30));

Schedule::call(function (): void {
    app(UpdateMirrorService::class)->warmAll();
})->cron('*/'.$updateWarmMinutes.' * * * *');

Artisan::command('updates:warm {product?} {channel?}', function (?string $product = null, ?string $channel = null): void {
    $mirror = app(UpdateMirrorService::class);

    if ($product === null) {
        $mirror->warmAll();
        $this->info('Warmed all update products.');

        return;
    }

    $mirror->warmProduct($product, $channel);
    $this->info("Warmed update product [{$product}]".($channel !== null ? " channel [{$channel}]" : '').'.');
})->purpose('Mirror product update manifests and binaries from upstream sources');
