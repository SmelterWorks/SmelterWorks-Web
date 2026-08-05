<?php

namespace Tests\Unit;

use App\Support\Seo\JsonLdBuilder;
use Tests\TestCase;

class JsonLdBuilderTest extends TestCase
{
    public function test_graph_includes_organization_and_website(): void
    {
        $graph = app(JsonLdBuilder::class)->graph();

        $this->assertSame('Organization', $graph[0]['@type']);
        $this->assertSame('WebSite', $graph[1]['@type']);
        $this->assertSame(config('app.name'), $graph[0]['name']);
        $this->assertContains(config('smelterworks.links.github'), $graph[0]['sameAs']);
    }

    public function test_encode_merges_extra_nodes(): void
    {
        $json = app(JsonLdBuilder::class)->encode([
            [
                '@type' => 'SoftwareApplication',
                'name' => 'Relic Launcher',
            ],
        ]);

        $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $payload['@context']);
        $this->assertCount(3, $payload['@graph']);
        $this->assertSame('SoftwareApplication', $payload['@graph'][2]['@type']);
    }
}
