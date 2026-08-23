<?php

namespace Tests\Unit;

use App\Support\Updates\UpdateMirrorService;
use Illuminate\Support\Facades\Cache;
use Tests\Support\FakesProductUpdates;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class UpdateMirrorServiceTest extends TestCase
{
    use FakesProductUpdates;
    use FakesRelicReleases;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->forgetInstance(UpdateMirrorService::class);
    }

    public function test_fetch_upstream_release_uses_latest_http_fake(): void
    {
        $this->resetUpdateMirror();
        $this->resetHttpFakes();
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.2.0'));

        $upstream = app(UpdateMirrorService::class)->fetchUpstreamRelease('relic', 'stable', fresh: true);

        $this->assertSame('0.2.0', $upstream?->version);
    }

    public function test_channel_is_stale_when_upstream_version_is_newer(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.2.0'));

        $mirror = app(UpdateMirrorService::class);

        $this->assertTrue($mirror->channelIsStale('relic', 'stable'));
    }

    public function test_channel_is_not_stale_when_mirror_matches_upstream(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $mirror = app(UpdateMirrorService::class);

        $this->assertFalse($mirror->channelIsStale('relic', 'stable'));
    }

    public function test_channel_is_stale_without_mirror_manifest(): void
    {
        $this->fakeRelicEmptyMirror();
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.2.0'));

        $mirror = app(UpdateMirrorService::class);

        $this->assertTrue($mirror->channelIsStale('relic', 'stable'));
    }
}
