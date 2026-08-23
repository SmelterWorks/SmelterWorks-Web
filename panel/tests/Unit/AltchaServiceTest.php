<?php

namespace Tests\Unit;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Hasher\Algorithm;
use App\Support\Altcha\AltchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AltchaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_challenge_can_be_verified(): void
    {
        config([
            'panel.altcha.enabled' => true,
            'panel.altcha.driver' => 'standalone',
            'panel.altcha.hmac_key' => 'test-hmac-key',
        ]);

        $service = app(AltchaService::class);
        $challenge = $service->createChallenge();

        $altcha = new Altcha('test-hmac-key');
        $solution = $altcha->solveChallenge(
            (string) $challenge['challenge'],
            (string) $challenge['salt'],
            Algorithm::from($challenge['algorithm']),
            (int) $challenge['maxnumber'],
        );

        $this->assertNotNull($solution);

        $payload = base64_encode(json_encode([
            'algorithm' => $challenge['algorithm'],
            'challenge' => $challenge['challenge'],
            'number' => $solution->number,
            'salt' => $challenge['salt'],
            'signature' => $challenge['signature'],
        ], JSON_THROW_ON_ERROR));

        $this->assertTrue($service->verify($payload));
    }

    public function test_verification_is_skipped_when_disabled(): void
    {
        config(['panel.altcha.enabled' => false]);

        $service = app(AltchaService::class);

        $this->assertTrue($service->verify(null));
    }
}
