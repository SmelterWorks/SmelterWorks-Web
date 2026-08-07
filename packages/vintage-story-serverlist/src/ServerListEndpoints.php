<?php

namespace SmelterWorks\VintageStoryServerList;

final class ServerListEndpoints
{
    public const DEFAULT_BASE_URL = 'https://masterserver.vintagestory.at';

    public const REGISTRATION_PATH = '/api/v1/servers/';

    public const LIST_PATH = '/api/v1/servers/list';

    public static function listUrl(string $baseUrl = self::DEFAULT_BASE_URL, string $listPath = self::LIST_PATH): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($listPath, '/');
    }
}
