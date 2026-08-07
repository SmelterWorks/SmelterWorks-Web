<?php

namespace Tests\Support;

use Illuminate\Testing\TestResponse;

trait AssertsCacheControl
{
    /**
     * @param  list<string>  $directives
     */
    protected function assertCacheControlDirectives(TestResponse $response, array $directives): void
    {
        $header = (string) $response->headers->get('Cache-Control');

        foreach ($directives as $directive) {
            $this->assertStringContainsString($directive, $header);
        }
    }
}
