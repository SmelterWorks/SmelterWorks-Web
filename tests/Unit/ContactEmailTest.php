<?php

namespace Tests\Unit;

use App\Support\ContactEmail;
use Tests\TestCase;

class ContactEmailTest extends TestCase
{
    public function test_obfuscate_replaces_top_level_domain_dot(): void
    {
        $this->assertSame(
            'smelterworks@fastmail[dot]com',
            ContactEmail::obfuscate('smelterworks@fastmail.com'),
        );
    }

    public function test_obfuscate_leaves_address_local_part_unchanged(): void
    {
        $this->assertSame(
            'contact@example[dot]test',
            ContactEmail::obfuscate('contact@example.test'),
        );
    }
}
