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

    public function test_team_section_renders_all_six_members(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Tusuubira Victor');
        $response->assertSee('CEO/Founder');
        $response->assertSee('Kamanzi Ahmed');
        $response->assertSee('Muwanguzi Joan Najjingo');
        $response->assertSee('Muhumuza Alex');
        $response->assertSee('Naikambo Sandra');
        $response->assertSee('Oyoka Daniel');
        $response->assertSee('images/voxsign/team-victor.jpg', false);
    }

    public function test_partners_section_renders_logos_and_text_credits(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('images/voxsign/partner-unad.png', false);
        $response->assertSee('images/voxsign/partner-kyu.png', false);
        $response->assertSee('images/voxsign/partner-youtube.webp', false);
        $response->assertSee('images/voxsign/partner-4.jpg', false);
        $response->assertSee('Makerere University');
        $response->assertSee('Makerere Innovation and Incubation Centre');
    }
}
