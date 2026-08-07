<?php

namespace Tests\Feature;

use App\Support\Updates\UpdateMirrorService;
use Illuminate\Support\Facades\Cache;
use Tests\Support\AssertsCacheControl;
use Tests\Support\FakesProductUpdates;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class UpdateManifestTest extends TestCase
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

    public function test_manifest_returns_public_json_for_warmed_channel(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $response = $this->get('/updates/relic/stable.json');

        $response->assertOk()
            ->assertHeader('ETag');
        $this->assertCacheControlDirectives($response, ['public', 'max-age=300', 'stale-while-revalidate=3600']);
        $response->assertJson([
            'schemaVersion' => 1,
            'product' => 'relic',
            'channel' => 'stable',
            'version' => '0.1.0',
        ]);

        $this->assertStringContainsString(
            '/files/relic/0.1.0/relic-launcher-v0.1.0-win-x64.zip',
            (string) $response->json('assets.0.url'),
        );
        $this->assertStringNotContainsString('github.com', (string) $response->json('assets.0.url'));
    }

    public function test_manifest_returns_304_when_etag_matches(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $etag = (string) $this->get('/updates/relic/stable.json')->headers->get('ETag');

        $this->withHeader('If-None-Match', $etag)
            ->get('/updates/relic/stable.json')
            ->assertStatus(304);
    }

    public function test_manifest_returns_404_for_unknown_product(): void
    {
        $this->get('/updates/unknown/stable.json')
            ->assertNotFound()
            ->assertJson(['error' => 'not_found']);
    }

    public function test_manifest_returns_503_when_mirror_not_ready(): void
    {
        $this->fakeRelicEmptyMirror();

        $this->get('/updates/relic/stable.json')
            ->assertStatus(503)
            ->assertJson(['error' => 'not_ready']);
    }
}
