<?php
namespace Tests\Feature;
use App\Http\Middleware\ResolveTenant;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->withoutMiddleware(ResolveTenant::class);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);
        return $this->actingAs($user);
    }

    public function test_school_admin_sees_communications_section(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $response = $this->actingAsInSchool($admin)->get(route('app.home'));

        $response->assertOk();
        $response->assertSee('Communications');
        $response->assertSee('Send SMS');
        $response->assertSee('Account settings');
    }

    public function test_parent_does_not_see_communications_section(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();

        $response = $this->actingAsInSchool($parent)->get(route('app.home'));

        $response->assertOk();
        $response->assertDontSee('Communications');
        $response->assertDontSee('Send SMS');
        $response->assertSee('Account settings');
    }

    public function test_student_does_not_see_communications_section(): void
    {
        $student = User::where('email', 'student@standrews.test')->firstOrFail();

        $response = $this->actingAsInSchool($student)->get(route('app.home'));

        $response->assertOk();
        $response->assertDontSee('Communications');
        $response->assertSee('Account settings');
    }

    public function test_platform_operator_sees_platform_sections_on_platform_console(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        app(TenantContext::class)->forPlatform();

        $response = $this->actingAs($operator)->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Schools');
        $response->assertSee('Onboard school');
        $response->assertSee('SMS & credits');
    }
}
