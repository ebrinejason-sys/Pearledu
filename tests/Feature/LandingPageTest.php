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
        $response->assertSee('clash-display', false);
        $response->assertSee('satoshi', false);
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

    public function test_hero_renders_gradient_glow_and_preserved_headline(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertSee('vx-hero-glow', false);
        $response->assertSee('include everyone.');
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

    public function test_team_section_renders_updated_roster(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Tusuubira Victor');
        $response->assertSee('CEO/Founder');
        $response->assertSee('Kamanzi Ahmed');
        $response->assertSee('Muwanguzi Joan Najjingo');
        $response->assertSee('Muhumuza Alex');
        $response->assertSee('Naikambo Sandra');
        $response->assertSee('Aaron Marshall Taremwa');
        $response->assertSee('Ebrine Tushabe');
        $response->assertSee('Product Development Expert');
        $response->assertSee('vx-avatar-initials', false);
        $response->assertSee('images/voxsign/team-victor.jpg', false);
        $response->assertDontSee('Oyoka Daniel');
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
        $response->assertSee('What future users are saying', false);
        $response->assertSee("I can't wait to try VoxSign!");
        $response->assertSee('Birabwa Jane Lydia');
        $response->assertSee("I'm really looking forward to VoxSign's launch.");
        $response->assertDontSee('I love using VoxSign');
    }

    public function test_pricing_is_not_present_anywhere_on_the_page(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertDontSee('Pricing');
        $response->assertDontSee('UGX 0');
        $response->assertDontSee('UGX 50,000');
        $response->assertDontSee('#pricing', false);
    }

    public function test_roadmap_section_renders(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('The road ahead', false);
        $response->assertSee('expansion across Africa', false);
    }

    public function test_contact_section_renders(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('+256 770 680769');
        $response->assertSee('voxsign3@gmail.com');
        $response->assertSee('Makerere Innovation and Incubation Centre');
        $response->assertSee('name="website"', false); // honeypot field preserved
        $response->assertDontSee('Accessibility Statement');
        $response->assertDontSee('Privacy Policy');
    }

    public function test_contact_form_sends_admin_notification_and_submitter_confirmation(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello VoxSign',
            'website' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactFormReceived::class, function ($mail) {
            return $mail->hasTo('tusuubiravictor@gmail.com')
                && $mail->name === 'Test User'
                && $mail->email === 'test@example.com'
                && $mail->message === 'Hello VoxSign';
        });

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactFormConfirmation::class, function ($mail) {
            return $mail->hasTo('test@example.com')
                && $mail->name === 'Test User'
                && $mail->from[0]->address === config('mail.from.address')
                && $mail->from[0]->name === 'VoxSign';
        });
    }

    public function test_contact_form_still_validates_required_fields(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'message' => '',
            'website' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
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

    public function test_avatar_demo_renders_3d_container_and_preserved_copy(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertSee('id="vx-avatar-3d"', false);
        $response->assertSee('id="vx-avatar-caption"', false);
        $response->assertSee('How are you?');
        $response->assertSee('Concept preview');
        $response->assertSee('illustrative, not a verified Ugandan Sign Language rendering');
    }

    public function test_section_spacing_uses_responsive_clamp_and_sec_head_class(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('.vx-section{padding:clamp(48px,8vw,88px) 0}', false);
        $response->assertSee('.vx-sec-head{margin-bottom:clamp(28px,4vw,44px)}', false);
        $response->assertSee('class="vx-lead vx-sec-head"', false);
        $response->assertSee('class="vx-h2 vx-sec-head"', false);
        $response->assertDontSee('style="margin-bottom:32px"', false);
    }

    public function test_uses_clash_display_and_satoshi_fonts(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('api.fontshare.com', false);
        $response->assertSee('clash-display', false);
        $response->assertSee('satoshi', false);
        $response->assertSee("--display:'Clash Display',system-ui,sans-serif;", false);
        $response->assertSee("--body:'Satoshi',system-ui,sans-serif;", false);
        $response->assertDontSee('fonts.googleapis.com', false);
        $response->assertDontSee('Bricolage', false);
        $response->assertDontSee('Atkinson', false);
    }

    public function test_nav_renders_inline_svg_logo_not_png(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertSee('<svg class="vx-logo"', false);
        $response->assertDontSee('voxsign-logo.png');
    }

    public function test_mobile_nav_has_hamburger_toggle(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('class="vx-nav-toggle"', false);
        $response->assertSee('aria-label="Menu"', false);
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('.vx-nav-toggle{display:none', false);
        $response->assertSee('@media(max-width:860px)', false);
    }

    public function test_interaction_polish_css_present(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('cubic-bezier(.16,1,.3,1)', false);
        // Check stable .vx-card properties individually to avoid brittle full-rule assertions
        $response->assertSee('background:var(--surface)', false);
        $response->assertSee('border:1px solid var(--line)', false);
        $response->assertSee('padding:22px', false);
        $response->assertSee('transition:transform .2s ease,box-shadow .2s ease', false);
        $response->assertSee('.vx-card:hover{transform:translateY(-3px)', false);
        $response->assertSee('.vx-grid .vx-card:nth-child(1){transition-delay:0ms}', false);
    }

    public function test_layout_includes_v4_visual_pass_css(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertSee('.vx-card::before', false);
        $response->assertSee('vxFlowShift', false);
    }
}
