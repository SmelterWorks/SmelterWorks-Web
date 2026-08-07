<?php

namespace App\Providers;

use App\Support\Content\ProjectCatalog;
use App\Support\Currency\ExchangeRateService;
use App\Support\Hosting\HostingCatalog;
use App\Support\Hosting\HostingPurchaseService;
use App\Support\Hosting\HostingStockService;
use App\Support\Relic\RelicCatalog;
use App\Support\Servers\MasterServerListService;
use App\Support\Updates\AssetMatcher;
use App\Support\Updates\Sources\GitHubReleaseSource;
use App\Support\Updates\Sources\RepoUrlParser;
use App\Support\Updates\Sources\UpdateSourceResolver;
use App\Support\Updates\UpdateFileServer;
use App\Support\Updates\UpdateManifestPresenter;
use App\Support\Updates\UpdateMirrorService;
use App\Support\Updates\UpdatePathValidator;
use App\Support\Updates\UpdateProductRegistry;
use App\Support\Updates\UpstreamUrlValidator;
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
        $this->app->singleton(UpdatePathValidator::class);
        $this->app->singleton(UpdateProductRegistry::class);
        $this->app->singleton(UpstreamUrlValidator::class);
        $this->app->singleton(AssetMatcher::class);
        $this->app->singleton(RepoUrlParser::class);
        $this->app->singleton(GitHubReleaseSource::class);
        $this->app->singleton(UpdateSourceResolver::class);
        $this->app->singleton(UpdateMirrorService::class);
        $this->app->singleton(UpdateManifestPresenter::class);
        $this->app->singleton(UpdateFileServer::class);
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
