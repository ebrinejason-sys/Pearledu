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

    public function test_mission_how_it_works_and_features_render(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('inclusive learning, accessibility, and collaboration', false);
        $response->assertSee('Speech captured live or recorded', false);
        $response->assertSee('Download', false);
        $response->assertSee('Create account', false);
        $response->assertSee('Tap Listen', false);
        $response->assertSee('Automatic voice recognition that accommodates varied accents', false);
        $response->assertSee('Multi-Device Accessibility', false);
    }
}
