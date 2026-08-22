<?php

namespace Tests\Feature;

use App\Mail\SchoolOnboardingRequestReceived;
use App\Models\PricingPlan;
use Database\Seeders\PricingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PearlEduLandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PricingPlanSeeder::class);
    }

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
        $response->assertSee('vx-logo', false);
        $response->assertSee('class="vx-logo"', false);
        $response->assertSee('pe-brand-name', false);
        $response->assertSee('By VoxSign Technologies', false);
    }

    public function test_nav_links_to_features_pricing_and_faq(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('href="#modules"', false);
        $response->assertSee('href="#pricing"', false);
        $response->assertSee('href="#faq"', false);
        $response->assertSee('href="#onboard"', false);
    }

    public function test_login_buttons_link_to_login(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('href="http://pearledu.voxsign.test/login"', false);
        $content = $response->getContent();
        // Desktop nav Login + mobile menu Login + hero Login
        $this->assertGreaterThanOrEqual(2, substr_count($content, '>Login<'));
    }

    public function test_hero_renders_dark_band_with_dashboard_mockup(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('School Management Platform (PearlEdu)', false);
        $response->assertSee('pe-hero', false);
        $response->assertSee('pe-hero-art', false);
        $response->assertSee('Staff login', false);
    }

    public function test_how_it_works_section_describes_features_with_icons(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('What PearlEdu manages for your school', false);
        $response->assertSee('Attendance', false);
        $response->assertSee('Grading', false);
        $response->assertSee('Fees', false);
        $response->assertSee('Parent communication', false);
        $response->assertSee('pe-module-icon', false);
        $response->assertSee('<svg', false);
    }

    public function test_feature_deep_dives_render_product_mockups(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('pe-module-grid', false);
        $response->assertSee('Staff &amp; roles', false);
        $response->assertSee('Secure school data', false);
        $response->assertSee('mobile money', false);
    }

    public function test_pricing_section_renders_seeded_plans_from_database(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('id="pricing"', false);
        $response->assertSee('Starter');
        $response->assertSee('Standard');
        $response->assertSee('Enterprise');
        $response->assertSee('Contact us');
        $response->assertSee('Most popular');
        $response->assertSee('Fees &amp; mobile money payments', false);
    }

    public function test_pricing_renders_formatted_amount_when_price_is_set(): void
    {
        PricingPlan::where('name', 'Starter')->update(['price' => 250000]);

        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('UGX 250,000', false);
        $response->assertSee('per term', false);
    }

    public function test_inactive_plans_are_hidden_from_the_landing_page(): void
    {
        PricingPlan::where('name', 'Enterprise')->update(['is_active' => false]);

        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertDontSee('Enterprise');
    }

    public function test_plans_render_in_sort_order(): void
    {
        PricingPlan::where('name', 'Enterprise')->update(['sort_order' => 0]);

        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Enterprise', 'Starter', 'Standard']);
    }

    public function test_faq_section_renders_accordion(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('id="faq"', false);
        $response->assertSee('<details>', false);
        $response->assertSee('How long does it take to onboard a school?', false);
        $response->assertSee('mobile money', false);
    }

    public function test_stats_band_renders(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('pe-stats', false);
        $response->assertSee('Unified school system', false);
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
        Mail::fake();

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

        Mail::assertSent(SchoolOnboardingRequestReceived::class, function ($mail) {
            return $mail->schoolName === 'Test Academy'
                && $mail->contactName === 'Jane Doe'
                && $mail->email === 'jane@example.com';
        });
    }

    public function test_onboarding_form_validates_required_fields(): void
    {
        Mail::fake();

        $response = $this->post('http://pearledu.voxsign.test/onboard', [
            'school_name' => '',
            'contact_name' => '',
            'email' => 'not-an-email',
            'website' => '',
        ]);

        $response->assertSessionHasErrors(['school_name', 'contact_name', 'email']);
        Mail::assertNothingSent();
    }

    public function test_login_page_still_reachable_on_pearledu_host(): void
    {
        $response = $this->get('http://pearledu.voxsign.test/login');

        $response->assertStatus(200);
        $response->assertSee('vx-auth-panel', false);
    }
}
