<?php

namespace Tests\Unit;

use App\Support\Database\DatabaseConnectionValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseConnectionValidatorTest extends TestCase
{
    private DatabaseConnectionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new DatabaseConnectionValidator;
    }

    #[DataProvider('tcpDriverProvider')]
    public function test_tcp_drivers_require_host_database_and_username(string $driver): void
    {
        config([
            "database.connections.{$driver}.host" => null,
            "database.connections.{$driver}.database" => null,
            "database.connections.{$driver}.username" => null,
        ]);

        $missing = $this->validator->missingEnvKeys($driver);

        $this->assertContains('DB_HOST', $missing);
        $this->assertContains('DB_DATABASE', $missing);
        $this->assertContains('DB_USERNAME', $missing);
    }

    public static function tcpDriverProvider(): array
    {
        return [
            'mysql' => ['mysql'],
            'mariadb' => ['mariadb'],
            'pgsql' => ['pgsql'],
        ];
    }

    public function test_sqlite_memory_database_is_valid(): void
    {
        config([
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->assertSame([], $this->validator->missingEnvKeys('sqlite'));
    }

    public function test_assert_configured_throws_for_missing_mysql_env(): void
    {
        config([
            'database.connections.mysql.host' => null,
            'database.connections.mysql.database' => null,
            'database.connections.mysql.username' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('mysql');

        $this->validator->assertConfigured('mysql');
    }
}
