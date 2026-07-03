<?php

namespace Tests\Feature;

use Tests\TestCase;

class PearlEduLandingPageTest extends TestCase
{
    public function test_root_renders_pearledu_landing_page_not_a_login_redirect(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('PearlEdu', false);
    }

    public function test_header_shows_logo_name_and_tagline(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('logo.svg', false);
        $response->assertSee('pe-brand-name', false);
        $response->assertSee('By VoxSign Technologies', false);
    }

    public function test_login_buttons_link_to_login(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Login', 'href="http://pearledu.voxsign.test/login"'], false);
        $content = $response->getContent();
        $this->assertSame(2, substr_count($content, '>Login<'));
    }

    public function test_how_it_works_section_describes_features_with_icons(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('How it works', false);
        $response->assertSee('Attendance', false);
        $response->assertSee('Grading', false);
        $response->assertSee('Fees', false);
        $response->assertSee('Communication', false);
        $response->assertSee('pe-card-icon', false);
        $response->assertSee('<svg', false);
    }

    public function test_onboarding_form_renders_with_required_fields_and_csrf(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('id="onboard"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('name="school_name"', false);
        $response->assertSee('name="contact_name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="message"', false);
        $response->assertSee('name="website"', false); // honeypot
    }

    public function test_onboarding_form_submission_sends_notification_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('http://pearledu.voxsign.test/onboard', [
            'school_name' => 'Test Academy',
            'contact_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '0700000000',
            'message' => 'We would like to onboard.',
            'website' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SchoolOnboardingRequestReceived::class, function ($mail) {
            return $mail->schoolName === 'Test Academy'
                && $mail->contactName === 'Jane Doe'
                && $mail->email === 'jane@example.com';
        });
    }

    public function test_onboarding_form_validates_required_fields(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('http://pearledu.voxsign.test/onboard', [
            'school_name' => '',
            'contact_name' => '',
            'email' => 'not-an-email',
            'website' => '',
        ]);

        $response->assertSessionHasErrors(['school_name', 'contact_name', 'email']);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_login_page_still_reachable_on_pearledu_host(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/login');

        $response->assertStatus(200);
        $response->assertSee('vx-auth-panel', false);
    }
}
