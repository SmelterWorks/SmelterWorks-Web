<?php

namespace SmelterWorks\VintageStoryServerList;

final class ServerListQuery
{
    /**
     * @param  list<array<string, mixed>>  $servers
     * @param  array{
     *     search?: string|null,
     *     version?: string|null,
     *     playstyle?: string|null,
     *     hasPassword?: bool|null,
     *     whitelisted?: bool|null,
     *     hasMods?: bool|null,
     *     hideEmpty?: bool|null,
     *     minPlayers?: int|null,
     *     sort?: string|null
     * }  $filters
     * @return list<array<string, mixed>>
     */
    public static function apply(array $servers, array $filters = []): array
    {
        $filtered = array_values(array_filter(
            $servers,
            fn (array $server): bool => self::matches($server, $filters),
        ));

        return self::sort($filtered, (string) ($filters['sort'] ?? 'players'));
    }

    /**
     * @param  list<array<string, mixed>>  $servers
     * @return list<string>
     */
    public static function versions(array $servers): array
    {
        $versions = [];

        foreach ($servers as $server) {
            $version = trim((string) ($server['gameVersion'] ?? ''));

            if ($version !== '') {
                $versions[$version] = true;
            }
        }

        $list = array_keys($versions);
        usort($list, [self::class, 'compareVersions']);

        return $list;
    }

    /**
     * @param  list<array<string, mixed>>  $servers
     * @return list<string>
     */
    public static function playstyles(array $servers): array
    {
        $playstyles = [];

        foreach ($servers as $server) {
            $playstyle = $server['playstyle'] ?? null;

            if (! is_array($playstyle)) {
                continue;
            }

            $id = trim((string) ($playstyle['id'] ?? ''));

            if ($id !== '') {
                $playstyles[$id] = true;
            }
        }

        $list = array_keys($playstyles);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }

    /**
     * @param  list<array<string, mixed>>  $servers
     * @return array{
     *     server_count: int,
     *     total_players: int,
     *     latest_major_version: string|null,
     *     latest_major_share: int|null
     * }
     */
    public static function summary(array $servers): array
    {
        $share = self::latestMajorVersionShare($servers);

        return [
            'server_count' => count($servers),
            'total_players' => array_sum(array_map(
                fn (array $server): int => self::players($server),
                $servers,
            )),
            'latest_major_version' => self::latestMajorVersion($servers),
            'latest_major_share' => $share,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $servers
     */
    public static function latestMajorVersionShare(array $servers): ?int
    {
        if ($servers === []) {
            return null;
        }

        $latestMajor = self::latestMajorVersion($servers);

        if ($latestMajor === null || $latestMajor === '') {
            return null;
        }

        $matching = 0;

        foreach ($servers as $server) {
            $version = (string) ($server['gameVersion'] ?? '');

            if (self::majorVersion($version) === $latestMajor) {
                $matching++;
            }
        }

        return (int) round(($matching / count($servers)) * 100);
    }

    /**
     * @param  list<array<string, mixed>>  $servers
     */
    public static function latestMajorVersion(array $servers): ?string
    {
        $versions = self::versions($servers);

        if ($versions === []) {
            return null;
        }

        return self::majorVersion($versions[count($versions) - 1]);
    }

    /**
     * @param  array<string, mixed>  $server
     * @param  array{
     *     search?: string|null,
     *     version?: string|null,
     *     playstyle?: string|null,
     *     hasPassword?: bool|null,
     *     whitelisted?: bool|null,
     *     hasMods?: bool|null,
     *     hideEmpty?: bool|null,
     *     minPlayers?: int|null
     * }  $filters
     */
    private static function matches(array $server, array $filters): bool
    {
        if (($filters['hideEmpty'] ?? false) && self::players($server) < 1) {
            return false;
        }

        $minPlayers = $filters['minPlayers'] ?? null;

        if (is_int($minPlayers) && self::players($server) < $minPlayers) {
            return false;
        }

        $version = $filters['version'] ?? null;

        if (is_string($version) && $version !== '' && (string) ($server['gameVersion'] ?? '') !== $version) {
            return false;
        }

        $playstyle = $filters['playstyle'] ?? null;

        if (is_string($playstyle) && $playstyle !== '') {
            $serverPlaystyle = $server['playstyle'] ?? null;
            $serverId = is_array($serverPlaystyle) ? (string) ($serverPlaystyle['id'] ?? '') : '';

            if ($serverId !== $playstyle) {
                return false;
            }
        }

        if (array_key_exists('hasPassword', $filters) && $filters['hasPassword'] !== null) {
            if ((bool) ($server['hasPassword'] ?? false) !== (bool) $filters['hasPassword']) {
                return false;
            }
        }

        if (array_key_exists('whitelisted', $filters) && $filters['whitelisted'] !== null) {
            if ((bool) ($server['whitelisted'] ?? false) !== (bool) $filters['whitelisted']) {
                return false;
            }
        }

        if (array_key_exists('hasMods', $filters) && $filters['hasMods'] !== null) {
            $mods = $server['mods'] ?? [];
            $hasMods = is_array($mods) && $mods !== [];

            if ($hasMods !== (bool) $filters['hasMods']) {
                return false;
            }
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search === '') {
            return true;
        }

        $haystack = strtolower(implode(' ', [
            (string) ($server['serverName'] ?? ''),
            (string) ($server['serverIP'] ?? ''),
            (string) ($server['gameDescription'] ?? ''),
        ]));

        return str_contains($haystack, strtolower($search));
    }

    /**
     * @param  list<array<string, mixed>>  $servers
     * @return list<array<string, mixed>>
     */
    private static function sort(array $servers, string $sort): array
    {
        $sorted = $servers;

        usort($sorted, function (array $left, array $right) use ($sort): int {
            return match ($sort) {
                'name' => strnatcasecmp(
                    (string) ($left['serverName'] ?? ''),
                    (string) ($right['serverName'] ?? ''),
                ),
                'version' => self::compareVersions(
                    (string) ($right['gameVersion'] ?? ''),
                    (string) ($left['gameVersion'] ?? ''),
                ),
                default => self::players($right) <=> self::players($left),
            };
        });

        return $sorted;
    }

    /**
     * @param  array<string, mixed>  $server
     */
    private static function players(array $server): int
    {
        $players = $server['players'] ?? 0;

        return is_numeric($players) ? (int) $players : 0;
    }

    private static function majorVersion(string $version): string
    {
        if (preg_match('/^(\d+\.\d+)/', $version, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private static function compareVersions(string $left, string $right): int
    {
        $leftParts = array_map('intval', explode('.', preg_replace('/[^0-9.]/', '', $left) ?: '0'));
        $rightParts = array_map('intval', explode('.', preg_replace('/[^0-9.]/', '', $right) ?: '0'));
        $length = max(count($leftParts), count($rightParts));

        for ($index = 0; $index < $length; $index++) {
            $leftValue = $leftParts[$index] ?? 0;
            $rightValue = $rightParts[$index] ?? 0;

            if ($leftValue !== $rightValue) {
                return $leftValue <=> $rightValue;
            }
        }

        return 0;
    }
}
