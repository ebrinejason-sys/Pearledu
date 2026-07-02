<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_layout_uses_light_mode_palette_and_fonts(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('--paper:#FBFAF7', false);
        $response->assertSee('--voice:#FF6A3D', false);
        $response->assertSee('--sign:#12B3A6', false);
        $response->assertSee('Bricolage+Grotesque', false);
        $response->assertSee('Atkinson+Hyperlegible', false);
        $response->assertDontSee('--vx-bg:#0A0A0A', false);
        $response->assertDontSee('#pricing', false);
    }

    public function test_hero_renders_platform_level_copy(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Technology built to include everyone.', false);
        $response->assertSee('PearlEdu', false);
        $response->assertSee('VoxSign Accessibility', false);
        $response->assertSee('Talk to us');
        $response->assertDontSee('Communication gaps between hearing instructors', false);
    }

    public function test_how_it_works_and_features_render_with_headings(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('From spoken word to signed meaning', false);
        $response->assertSee('Speech captured live or recorded', false);
        $response->assertSee('Download', false);
        $response->assertSee('Create account', false);
        $response->assertSee('Tap Listen', false);
        $response->assertSee('Everything needed for real, everyday inclusion', false);
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

    public function test_partners_marquee_renders_logos_and_text_credits(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('vx-marquee', false);
        $response->assertSee('images/voxsign/partner-unad.png', false);
        $response->assertSee('images/voxsign/partner-kyu.png', false);
        $response->assertSee('images/voxsign/partner-youtube.webp', false);
        $response->assertSee('images/voxsign/partner-4.jpg', false);
        $response->assertSee('Makerere University');
        $response->assertSee('Makerere Innovation and Incubation Centre');
    }

    public function test_partners_section_appears_directly_after_hero(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $heroPos = strpos($response->getContent(), 'Technology built to');
        $partnersPos = strpos($response->getContent(), 'vx-marquee');
        $howItWorksPos = strpos($response->getContent(), 'How it works');

        $this->assertNotFalse($heroPos);
        $this->assertNotFalse($partnersPos);
        $this->assertGreaterThan($heroPos, $partnersPos);
        $this->assertLessThan($howItWorksPos, $partnersPos);
    }

    public function test_testimonials_render_in_anticipatory_tense(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee("I can't wait to try VoxSign!");
        $response->assertSee('Birabwa Jane Lydia');
        $response->assertSee("I'm really looking forward to VoxSign's launch.");
        $response->assertDontSee('I love using VoxSign');
    }

    public function test_pricing_table_renders_all_four_tiers(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('UGX 0');
        $response->assertSee('3,000 words/day limit');
        $response->assertSee('UGX 50,000/month');
        $response->assertSee('UGX 50,000,000/year');
        $response->assertSee('UGX 500,000,000/year');
        $response->assertSee('Government/NGOs');
    }

    public function test_roadmap_and_contact_section_render(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('expansion across Africa', false);
        $response->assertSee('+256 770 680769');
        $response->assertSee('voxsign3@gmail.com');
        $response->assertSee('Makerere Innovation and Incubation Centre');
        $response->assertSee('name="website"', false); // honeypot field preserved
        $response->assertDontSee('Accessibility Statement');
        $response->assertDontSee('Privacy Policy');
    }

    public function test_contact_form_still_validates_and_submits(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello VoxSign',
            'website' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_two_divisions_section_introduces_pearledu_and_accessibility(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Two divisions, one mission', false);
        $response->assertSee('school management platform', false);
        $response->assertSee('href="#pearledu"', false);
        $response->assertSee('href="#accessibility"', false);
    }

    public function test_pearledu_section_describes_institution_features(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('id="pearledu"', false);
        $response->assertSee('Attendance', false);
        $response->assertSee('Grading', false);
        $response->assertSee('Fees', false);
        $response->assertSee('Communication', false);
    }

    public function test_accessibility_section_describes_both_products(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('id="accessibility"', false);
        $response->assertSee('Ugandan Sign Language', false);
        $response->assertSee('non-standard speech', false);
        $response->assertSee('Whisper', false);
        $response->assertSee('speech impairments', false);
    }

    public function test_avatar_demo_is_labeled_as_concept_preview(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Concept preview', false);
        $response->assertSee('How are you?', false);
        $response->assertSee('What is your name?', false);
        $response->assertSee('vx-avatar-demo', false);
    }
}
