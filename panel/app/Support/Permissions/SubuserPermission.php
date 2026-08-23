<?php

namespace App\Support\Permissions;

final class SubuserPermission
{
    public const VIEW_SERVERS = 'servers.view';

    public const MANAGE_SERVERS = 'servers.manage';

    public const MANAGE_BACKUPS = 'backups.manage';

    public const MANAGE_MODS = 'mods.manage';

    public const MANAGE_MIGRATIONS = 'migrations.manage';

    public const MANAGE_FILES = 'files.manage';

    public const MANAGE_SUBUSERS = 'subusers.manage';

    public const MANAGE_BILLING = 'billing.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW_SERVERS,
            self::MANAGE_SERVERS,
            self::MANAGE_BACKUPS,
            self::MANAGE_MODS,
            self::MANAGE_MIGRATIONS,
            self::MANAGE_FILES,
            self::MANAGE_SUBUSERS,
            self::MANAGE_BILLING,
        ];
    }
}
