<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DatabaseConnectionValidator
{
    /**
     * @return list<string>
     */
    public function missingEnvKeys(string $connection): array
    {
        return match ($connection) {
            'sqlite' => $this->missingSqliteKeys($connection),
            'mysql', 'mariadb', 'pgsql', 'sqlsrv' => $this->missingTcpKeys($connection),
            default => throw new InvalidArgumentException("Unsupported database connection [{$connection}]."),
        };
    }

    public function assertConfigured(string $connection): void
    {
        $missing = $this->missingEnvKeys($connection);

        if ($missing !== []) {
            throw new RuntimeException(
                'Database connection ['.$connection.'] is missing required environment variables: '.implode(', ', $missing),
            );
        }
    }

    public function assertReachable(?string $connection = null): void
    {
        $connection ??= (string) config('database.default');

        $this->assertConfigured($connection);

        DB::connection($connection)->getPdo();
    }

    /**
     * @return list<string>
     */
    private function missingTcpKeys(string $connection): array
    {
        $config = (array) config("database.connections.{$connection}", []);
        $required = [
            'host' => 'DB_HOST',
            'database' => 'DB_DATABASE',
            'username' => 'DB_USERNAME',
        ];
        $missing = [];

        foreach ($required as $key => $envKey) {
            if (! filled($config[$key] ?? null)) {
                $missing[] = $envKey;
            }
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    private function missingSqliteKeys(string $connection): array
    {
        $config = (array) config("database.connections.{$connection}", []);

        if (filled($config['url'] ?? null)) {
            return [];
        }

        $database = $config['database'] ?? null;

        if (! filled($database)) {
            return ['DB_DATABASE'];
        }

        if ($database === ':memory:') {
            return [];
        }

        if (! is_string($database)) {
            return ['DB_DATABASE'];
        }

        if (! str_starts_with($database, '/') && ! str_contains($database, DIRECTORY_SEPARATOR)) {
            $database = database_path($database);
        }

        if (! file_exists($database)) {
            return ['DB_DATABASE'];
        }

        return [];
    }
}
