<?php

namespace Tests\Feature;

use App\Models\PricingPlan;
use App\Models\User;
use Database\Seeders\PricingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ActsAsPlatformOperator;
use Tests\TestCase;

class PricingPlanAdminTest extends TestCase
{
    use ActsAsPlatformOperator;
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PricingPlanSeeder::class);

        $this->operator = new User([
            'full_name' => 'Platform Admin',
            'email' => 'platform-admin@test.local',
            'status' => 'active',
            'password' => 'password1234',
        ]);
        $this->operator->forceFill(['is_platform' => true])->save();
        $this->ensurePlatformAdminRole($this->operator);
    }

    public function test_platform_operator_can_view_pricing_console(): void
    {
        $response = $this->actingAs($this->operator)->get(route('platform.pricing.index'));

        $response->assertStatus(200);
        $response->assertSee('PearlEdu landing pricing');
        $response->assertSee('Starter');
        $response->assertSee('Standard');
        $response->assertSee('Enterprise');
    }

    public function test_platform_operator_can_create_a_plan(): void
    {
        $response = $this->actingAs($this->operator)->withRecentPlatformAuth()->post(route('platform.pricing.store'), [
            'name' => 'Pilot',
            'tagline' => 'Free pilot term',
            'price' => 0,
            'currency' => 'UGX',
            'billing_period' => 'per term',
            'features_text' => "Up to 50 students\nAttendance tracking\n\n  Email support  ",
            'is_highlighted' => '0',
            'is_active' => '1',
            'sort_order' => 4,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $plan = PricingPlan::where('name', 'Pilot')->firstOrFail();
        $this->assertSame(['Up to 50 students', 'Attendance tracking', 'Email support'], $plan->features);
        $this->assertSame(0, $plan->price);
        $this->assertTrue($plan->is_active);
        $this->assertFalse($plan->is_highlighted);
    }

    public function test_platform_operator_can_update_a_plan(): void
    {
        $plan = PricingPlan::where('name', 'Starter')->firstOrFail();

        $response = $this->actingAs($this->operator)->withRecentPlatformAuth()->put(route('platform.pricing.update', $plan), [
            'name' => 'Starter',
            'tagline' => 'Updated tagline',
            'price' => 300000,
            'currency' => 'UGX',
            'billing_period' => 'per year',
            'features_text' => 'One feature',
            'is_highlighted' => '1',
            'is_active' => '0',
            'sort_order' => 9,
        ]);

        $response->assertRedirect();

        $plan->refresh();
        $this->assertSame(300000, $plan->price);
        $this->assertSame('per year', $plan->billing_period);
        $this->assertSame(['One feature'], $plan->features);
        $this->assertTrue($plan->is_highlighted);
        $this->assertFalse($plan->is_active);
        $this->assertSame(9, $plan->sort_order);
    }

    public function test_blank_price_is_stored_as_null_for_contact_us(): void
    {
        $plan = PricingPlan::where('name', 'Starter')->firstOrFail();
        $plan->update(['price' => 100000]);

        $this->actingAs($this->operator)->withRecentPlatformAuth()->put(route('platform.pricing.update', $plan), [
            'name' => 'Starter',
            'tagline' => '',
            'price' => '',
            'currency' => 'UGX',
            'billing_period' => 'per term',
            'features_text' => '',
            'is_highlighted' => '0',
            'is_active' => '1',
            'sort_order' => 1,
        ]);

        $this->assertNull($plan->refresh()->price);
    }

    public function test_platform_operator_can_delete_a_plan(): void
    {
        $plan = PricingPlan::where('name', 'Enterprise')->firstOrFail();

        $response = $this->actingAs($this->operator)->withRecentPlatformAuth()->delete(route('platform.pricing.destroy', $plan));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pricing_plans', ['id' => $plan->id]);
    }

    public function test_non_platform_user_cannot_access_pricing_console(): void
    {
        $user = User::create([
            'full_name' => 'School Person',
            'email' => 'school-person@test.local',
            'status' => 'active',
            'password' => 'password1234',
        ]);

        $this->actingAs($user)->get(route('platform.pricing.index'))->assertForbidden();
        $this->actingAs($user)->post(route('platform.pricing.store'), [])->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('platform.pricing.index'))->assertRedirect();
    }

    public function test_admin_changes_appear_on_the_public_landing_page(): void
    {
        $plan = PricingPlan::where('name', 'Standard')->firstOrFail();

        $this->actingAs($this->operator)->withRecentPlatformAuth()->put(route('platform.pricing.update', $plan), [
            'name' => 'Growth',
            'tagline' => 'For ambitious schools',
            'price' => 450000,
            'currency' => 'UGX',
            'billing_period' => 'per term',
            'features_text' => 'Everything included',
            'is_highlighted' => '1',
            'is_active' => '1',
            'sort_order' => 2,
        ]);

        auth()->logout();
        $response = $this->get('http://pearledu.voxsign.test/');

        $response->assertStatus(200);
        $response->assertSee('Growth');
        $response->assertSee('UGX 450,000', false);
        $response->assertSee('For ambitious schools');
    }
}
