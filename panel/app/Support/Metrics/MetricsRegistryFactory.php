<?php

namespace App\Support\Metrics;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use InvalidArgumentException;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class MetricsRegistryFactory
{
    public function __construct(
        private readonly ?RedisFactory $redis = null,
    ) {}

    public function make(): CollectorRegistry
    {
        return new CollectorRegistry($this->resolveStorage());
    }

    private function resolveStorage(): APC|InMemory|Redis
    {
        $driver = config('metrics.storage', 'memory');

        return match ($driver) {
            'memory' => new InMemory,
            'apc' => new APC,
            'redis' => $this->makeRedisStorage(),
            default => throw new InvalidArgumentException("Unsupported metrics storage driver [{$driver}]."),
        };
    }

    private function makeRedisStorage(): Redis
    {
        if ($this->redis === null) {
            throw new InvalidArgumentException('Redis is not configured for metrics storage.');
        }

        $connectionName = (string) config('metrics.redis.connection', 'default');
        $connection = $this->redis->connection($connectionName);
        $config = $connection->getConfig();

        Redis::setDefaultOptions([
            'host' => (string) ($config['host'] ?? '127.0.0.1'),
            'port' => (int) ($config['port'] ?? 6379),
            'password' => $config['password'] ?? null,
            'database' => (int) ($config['database'] ?? 0),
            'timeout' => 0.1,
            'read_timeout' => 10,
            'persistent_connections' => false,
        ]);

        return new Redis;
    }
}
