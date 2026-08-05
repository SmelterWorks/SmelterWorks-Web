<?php

namespace Tests\Unit;

use App\Support\ContactEmail;
use Tests\TestCase;

class ContactEmailTest extends TestCase
{
    public function test_obfuscate_replaces_at_and_top_level_domain_dot(): void
    {
        $this->assertSame(
            'team [at] smelterworks[dot]com',
            ContactEmail::obfuscate('team@smelterworks.com'),
        );
    }

    public function test_obfuscate_leaves_address_local_part_unchanged(): void
    {
        $this->assertSame(
            'contact [at] example[dot]test',
            ContactEmail::obfuscate('contact@example.test'),
        );
    }
}
