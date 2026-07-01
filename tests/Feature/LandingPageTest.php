<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_hero_renders_new_voxsign_copy(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Speak the Future. See It Signed.');
        $response->assertSee('Record, Transcribe, Collaborate. Effortlessly with', false);
        $response->assertSee('Get in touch');
        $response->assertDontSee('Software, built deliberately.');
    }
}
