<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseConnectionValidator;
use Illuminate\Console\Command;

class PanelDoctorCommand extends Command
{
    protected $signature = 'panel:doctor';

    protected $description = 'Validate panel database configuration and observability settings';

    public function handle(DatabaseConnectionValidator $validator): int
    {
        $connection = (string) config('database.default');

        $this->components->info('Checking database configuration for ['.$connection.']...');

        $missing = $validator->missingEnvKeys($connection);

        if ($missing !== []) {
            $this->components->error('Missing environment variables: '.implode(', ', $missing));

            return self::FAILURE;
        }

        try {
            $validator->assertReachable($connection);
            $this->components->info('Database connection is reachable.');
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->reportObservability();

        return self::SUCCESS;
    }

    private function reportObservability(): void
    {
        $dsn = config('sentry.dsn');

        if (filled($dsn)) {
            $this->components->info('Error tracking DSN is configured (Sentry or GlitchTip).');
        } else {
            $this->components->warn('Error tracking DSN is not configured.');
        }

        if (config('metrics.enabled')) {
            $token = (string) config('metrics.token', '');

            if ($token === '') {
                $this->components->warn('Metrics are enabled but METRICS_TOKEN is empty.');
            } else {
                $this->components->info('Prometheus metrics are enabled at '.config('metrics.route').'.');
            }
        } else {
            $this->components->warn('Prometheus metrics are disabled.');
        }
    }
}
