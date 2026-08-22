<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Navigation\NavigationBuilder;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SchoolDashboardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function actingAsAdmin(): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($this->admin)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_school_home_shows_stats_charts_and_moves_access_to_bottom(): void
    {
        $class = SchoolClass::where('school_id', $this->school->id)->first()
            ?? SchoolClass::create([
                'school_id' => $this->school->id,
                'name' => 'P.1',
                'level' => 'primary',
                'code' => 'P1',
            ]);

        Student::factory()->count(2)->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        $invoice = FeeInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => Student::where('school_id', $this->school->id)->value('id'),
            'reference' => 'INV-DASH-1',
            'amount' => 50000,
            'balance' => 20000,
            'status' => 'partial',
        ]);

        FeePayment::create([
            'school_id' => $this->school->id,
            'invoice_id' => $invoice->id,
            'amount' => 30000,
            'method' => 'cash',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAsAdmin()->get(route('app.home'));

        $response->assertOk();
        $response->assertSee('Setup &amp; data hygiene', false);
        $response->assertSee('Keep the engine running', false);
        $response->assertSee('Learners by class', false);
        $response->assertSee('Fee collections', false);
        $response->assertSee('Quick access', false);
        $response->assertSee('View Learners', false);
        $response->assertSee('dash-access', false);
        $response->assertSee('Your access', false);

        $html = $response->getContent();
        $quickPos = strpos($html, 'Quick access');
        $accessPos = strpos($html, 'Your access');
        $this->assertNotFalse($quickPos);
        $this->assertNotFalse($accessPos);
        $this->assertGreaterThan($quickPos, $accessPos);
    }

    public function test_all_sidebar_nav_routes_exist(): void
    {
        $nav = app(NavigationBuilder::class)->build($this->admin);

        foreach ($nav['sections'] as $section) {
            foreach ($section['items'] as $item) {
                $this->assertNavItemRoutesExist($item);
            }
        }
    }

    /** @param  array<string, mixed>  $item */
    private function assertNavItemRoutesExist(array $item): void
    {
        foreach ($item['children'] ?? [] as $child) {
            $this->assertNavItemRoutesExist($child);
        }
        if (! empty($item['children'])) {
            return;
        }
        $this->assertNotEmpty($item['url'], 'Dead nav item: '.$item['label']);
        $this->assertTrue(
            Route::has($item['route']),
            'Missing route for nav item '.$item['label'].' ('.$item['route'].')'
        );
    }
}
