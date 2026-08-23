<?php

namespace App\Providers;

use App\Support\Database\DatabaseConnectionValidator;
use App\Support\Metrics\MetricsRecorder;
use App\Support\Metrics\MetricsRegistryFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Sentry\Laravel\Integration;
use Sentry\State\Scope;

class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetricsRegistryFactory::class);

        $this->app->singleton(CollectorRegistry::class, function ($app): CollectorRegistry {
            return $app->make(MetricsRegistryFactory::class)->make();
        });

        $this->app->singleton(MetricsRecorder::class, function ($app): MetricsRecorder {
            return new MetricsRecorder($app->make(CollectorRegistry::class));
        });

        $this->app->singleton(DatabaseConnectionValidator::class);
    }

    public function boot(): void
    {
        $this->configureSentryScope();
        $this->validateDatabaseConnection();
    }

    private function configureSentryScope(): void
    {
        if (! $this->sentryEnabled()) {
            return;
        }

        Integration::configureScope(static function (Scope $scope): void {
            $scope->setTag('panel.mode', (string) config('panel.mode', 'managed'));
            $scope->setTag('database.driver', (string) config('database.default', 'sqlite'));
        });
    }

    private function validateDatabaseConnection(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        if (! config('panel.database.validate_on_boot', true)) {
            return;
        }

        try {
            $this->app->make(DatabaseConnectionValidator::class)->assertConfigured(
                (string) config('database.default'),
            );
        } catch (\Throwable $exception) {
            Log::error('Panel database configuration check failed.', [
                'connection' => config('database.default'),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function sentryEnabled(): bool
    {
        return filled(config('sentry.dsn'));
    }
}
