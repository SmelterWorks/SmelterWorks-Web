<?php

namespace Tests\Unit;

use App\Support\Icons\IconLoader;
use App\Support\Url\SafeExternalUrl;
use Tests\TestCase;

class SecurityHelpersTest extends TestCase
{
    public function test_icon_loader_rejects_path_traversal(): void
    {
        $loader = new IconLoader;

        $this->assertSame('', $loader->contents('lucide', '../simple/forgejo'));
        $this->assertSame('', $loader->contents('evil', 'forgejo'));
        $this->assertNotSame('', $loader->contents('simple', 'forgejo'));
    }

    public function test_icon_loader_resolves_known_flag(): void
    {
        $loader = new IconLoader;

        $this->assertNotNull($loader->path('flags', 'US'));
        $this->assertNull($loader->path('flags', 'US/../../etc/passwd'));
    }

    public function test_safe_external_url_requires_https_host(): void
    {
        $this->assertSame(
            'https://panel.example.test/app',
            SafeExternalUrl::httpsOrNull('https://panel.example.test/app'),
        );
        $this->assertNull(SafeExternalUrl::httpsOrNull('http://panel.example.test/app'));
        $this->assertNull(SafeExternalUrl::httpsOrNull('javascript:alert(1)'));
        $this->assertNull(SafeExternalUrl::httpsOrNull('not-a-url'));
        $this->assertNull(SafeExternalUrl::httpsOrNull(null));
    }
}
