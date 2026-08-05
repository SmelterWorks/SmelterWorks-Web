<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAndContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_covers_policy_basics(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Privacy', false)
            ->assertSee('functional only', false)
            ->assertSee('telemetry', false)
            ->assertSee('No ads', false)
            ->assertSee(route('contact'), false);
    }

    public function test_terms_page_is_successful(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms of use', false)
            ->assertSee(route('contact'), false);
    }

    public function test_contact_page_shows_email_from_env(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Contact', false)
            ->assertSee('contact [at] example[dot]test', false)
            ->assertSee('mailto:contact@example.test', false);
    }

    public function test_donate_page_links_kofi(): void
    {
        $this->get(route('donate'))
            ->assertOk()
            ->assertSee('Donate', false)
            ->assertSee('ko-fi.com/smelterworks', false)
            ->assertSee('Ko-Fi', false);
    }
}
