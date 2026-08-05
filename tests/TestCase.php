<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected bool $fakeFrankfurterRates = true;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        if ($this->fakeFrankfurterRates) {
            $this->fakeFrankfurterRates();
        }
    }

    protected function fakeFrankfurterRates(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response([
                'amount' => 1.0,
                'base' => 'USD',
                'date' => '2026-08-04',
                'rates' => ['EUR' => 0.86843],
            ], 200),
        ]);
    }
}
