<?php

namespace SmelterWorks\VintageStoryServerList;

final class ServerListSnapshot
{
    /**
     * @param  list<array<string, mixed>>  $servers
     */
    public function __construct(
        public readonly array $servers,
        public readonly int $fetchedAt,
        public readonly ?string $contentHash = null,
    ) {}

    public function count(): int
    {
        return count($this->servers);
    }

    public function totalPlayers(): int
    {
        $total = 0;

        foreach ($this->servers as $server) {
            $players = $server['players'] ?? 0;

            if (is_numeric($players)) {
                $total += (int) $players;
            }
        }

        return $total;
    }

    /**
     * @return array{status: string, data: list<array<string, mixed>>}
     */
    public function toResponse(): array
    {
        return [
            'status' => 'ok',
            'data' => $this->servers,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toResponse(), JSON_THROW_ON_ERROR);
    }
}
