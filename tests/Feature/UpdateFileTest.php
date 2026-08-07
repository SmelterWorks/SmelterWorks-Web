<?php

namespace Tests\Feature;

use App\Support\Updates\UpdateMirrorService;
use Illuminate\Support\Facades\Cache;
use Tests\Support\AssertsCacheControl;
use Tests\Support\FakesProductUpdates;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class UpdateFileTest extends TestCase
{
    use AssertsCacheControl;
    use FakesProductUpdates;
    use FakesRelicReleases;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->forgetInstance(UpdateMirrorService::class);
    }

    public function test_file_endpoint_serves_mirrored_asset(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $response = $this->get('/files/relic/0.1.0/relic-launcher-v0.1.0-win-x64.zip');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertCacheControlDirectives($response, ['public', 'max-age=31536000', 'immutable']);
    }

    public function test_file_endpoint_rejects_path_traversal(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $this->get('/files/relic/0.1.0/../channels/stable.json')
            ->assertNotFound();
    }

    public function test_file_endpoint_returns_404_for_unlisted_file(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $this->get('/files/relic/0.1.0/not-listed.zip')
            ->assertNotFound();
    }
}
