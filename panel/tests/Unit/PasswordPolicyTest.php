<?php

namespace Tests\Unit;

use App\Support\Security\PasswordPolicy;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function test_default_policy_accepts_eight_character_mixed_password(): void
    {
        config([
            'panel.security.min_password_length' => 8,
            'panel.security.password_require_lowercase' => true,
            'panel.security.password_require_uppercase' => false,
            'panel.security.password_require_number' => true,
            'panel.security.password_require_symbol' => false,
            'panel.security.password_min_character_classes' => 2,
        ]);

        $policy = new PasswordPolicy;

        $this->assertTrue($policy->passes('panel8ok'));
        $this->assertFalse($policy->passes('short1'));
    }

    public function test_symbol_requirement_can_be_enabled(): void
    {
        config([
            'panel.security.min_password_length' => 8,
            'panel.security.password_require_symbol' => true,
            'panel.security.password_min_character_classes' => 0,
        ]);

        $policy = new PasswordPolicy;

        $this->assertFalse($policy->passes('panel8ok'));
        $this->assertTrue($policy->passes('panel8!ok'));
    }
}
