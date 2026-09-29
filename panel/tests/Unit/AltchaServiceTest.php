<?php

namespace Tests\Unit;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
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
        $challenge = Challenge::fromArray($service->createChallenge());

        $altcha = new Altcha(hmacSignatureSecret: 'test-hmac-key');
        $solution = $altcha->solveChallenge(new SolveChallengeOptions(
            challenge: $challenge,
            algorithm: new Pbkdf2,
        ));

        $this->assertNotNull($solution);

        $payload = (new Payload($challenge, $solution))->toBase64();

        $this->assertTrue($service->verify($payload));
    }

    public function test_verification_is_skipped_when_disabled(): void
    {
        config(['panel.altcha.enabled' => false]);

        $service = app(AltchaService::class);

        $this->assertTrue($service->verify(null));
    }
}
