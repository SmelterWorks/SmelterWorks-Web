<?php

namespace App\Providers;

use App\Support\Content\ProjectCatalog;
use App\Support\Currency\ExchangeRateService;
use App\Support\Hosting\HostingCatalog;
use App\Support\Hosting\HostingPurchaseService;
use App\Support\Hosting\HostingStockService;
use App\Support\Relic\RelicCatalog;
use App\Support\Relic\RelicGitHubReleases;
use App\Support\Servers\MasterServerListService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProjectCatalog::class);
        $this->app->singleton(ExchangeRateService::class);
        $this->app->singleton(HostingCatalog::class);
        $this->app->singleton(HostingStockService::class);
        $this->app->singleton(HostingPurchaseService::class);
        $this->app->singleton(RelicGitHubReleases::class);
        $this->app->singleton(RelicCatalog::class);
        $this->app->singleton(MasterServerListService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = (string) config('app.url');

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
