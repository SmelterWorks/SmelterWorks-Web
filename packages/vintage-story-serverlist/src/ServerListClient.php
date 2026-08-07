<?php

namespace SmelterWorks\VintageStoryServerList;

use RuntimeException;

final class ServerListClient
{
    /**
     * @param  callable(string): string  $fetcher
     */
    public function __construct(
        private $fetcher,
        private readonly int $maxBytes = 8_388_608,
    ) {}

    public function fetch(string $listUrl): ServerListSnapshot
    {
        if (! str_starts_with($listUrl, 'https://')) {
            throw new RuntimeException('Server list URL must use HTTPS.');
        }

        $body = ($this->fetcher)($listUrl);

        if ($body === '') {
            throw new RuntimeException('Server list response was empty.');
        }

        if (strlen($body) > $this->maxBytes) {
            throw new RuntimeException('Server list response exceeded the configured size limit.');
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Server list response was not valid JSON.');
        }

        if (($decoded['status'] ?? null) !== 'ok') {
            throw new RuntimeException('Server list upstream status was not ok.');
        }

        if (! isset($decoded['data']) || ! is_array($decoded['data'])) {
            throw new RuntimeException('Server list response did not include a data array.');
        }

        $servers = [];

        foreach ($decoded['data'] as $entry) {
            if (is_array($entry)) {
                $servers[] = $entry;
            }
        }

        return new ServerListSnapshot(
            servers: $servers,
            fetchedAt: time(),
            contentHash: hash('sha256', $body),
        );
    }
}
