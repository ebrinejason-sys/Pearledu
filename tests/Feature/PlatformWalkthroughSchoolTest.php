<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireRecentPlatformAuth;
use App\Http\Middleware\ResolveTenant;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\Provisioning\WalkthroughSchoolService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\ActsAsPlatformOperator;
use Tests\TestCase;

class PlatformWalkthroughSchoolTest extends TestCase
{
    use ActsAsPlatformOperator;
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        app(TenantContext::class)->forPlatform();
        $this->operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
    }

    public function test_school_admin_cannot_open_walkthrough_form(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $this->actingAs($admin)->get(route('platform.schools.walkthrough'))->assertForbidden();
        $this->actingAs($admin)->post(route('platform.schools.walkthrough.store'), $this->validPayload())->assertForbidden();
    }

    public function test_bursar_cannot_seed_walkthrough_school(): void
    {
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();

        $this->actingAs($bursar)->get(route('platform.schools.walkthrough'))->assertForbidden();
        $this->actingAs($bursar)->post(route('platform.schools.walkthrough.store'), $this->validPayload())->assertForbidden();
    }

    public function test_support_agent_cannot_seed_walkthrough_school(): void
    {
        $agent = $this->createPlatformStaff('support_agent', 'agent-walkthrough@pearledu.test');

        $this->actingAs($agent)->get(route('platform.schools.walkthrough'))->assertForbidden();
        $this->actingAs($agent)->withRecentPlatformAuth()
            ->post(route('platform.schools.walkthrough.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_platform_admin_can_open_walkthrough_form(): void
    {
        $this->actingAs($this->operator)
            ->get(route('platform.schools.walkthrough'))
            ->assertOk()
            ->assertSee('Demonstration school')
            ->assertSee('walkthrough_password')
            ->assertSee('head@stkizito.test')
            ->assertSee('bursar@stkizito.test')
            ->assertDontSee('name="password"', false);
    }

    public function test_store_requires_recent_platform_password(): void
    {
        $this->actingAs($this->operator)
            ->post(route('platform.schools.walkthrough.store'), $this->validPayload())
            ->assertRedirect(route('platform.auth.confirm'));

        $pending = session(RequireRecentPlatformAuth::PENDING_KEY);
        $this->assertSame('Walkthrough-shared-99', $pending['input']['walkthrough_password'] ?? null);
        $this->assertArrayNotHasKey('password', $pending['input'] ?? []);
    }

    public function test_store_rejects_short_password(): void
    {
        $this->actingAs($this->operator);
        $this->withRecentPlatformAuth();

        $this->from(route('platform.schools.walkthrough'))
            ->post(route('platform.schools.walkthrough.store'), [
                'walkthrough_password' => 'short',
                'walkthrough_password_confirmation' => 'short',
            ])
            ->assertRedirect(route('platform.schools.walkthrough'))
            ->assertSessionHasErrors('walkthrough_password');
    }

    public function test_platform_admin_can_seed_walkthrough_and_staff_can_sign_in(): void
    {
        $this->actingAs($this->operator);
        $this->withRecentPlatformAuth();

        $password = 'Walkthrough-shared-99';

        $this->post(route('platform.schools.walkthrough.store'), [
            'walkthrough_password' => $password,
            'walkthrough_password_confirmation' => $password,
        ])->assertRedirect();

        $school = app(WalkthroughSchoolService::class)->existing();
        $this->assertNotNull($school);
        $this->assertSame(WalkthroughSchoolService::EMIS_NUMBER, $school->emis_number);
        $this->assertSame(1, School::query()->where('emis_number', WalkthroughSchoolService::EMIS_NUMBER)->count());

        $audit = AuditLog::withoutGlobalScopes()->where('action', 'walkthrough.seeded')->first();
        $this->assertNotNull($audit);
        $this->assertSame($this->operator->id, $audit->actor_id);
        $this->assertStringNotContainsString($password, json_encode($audit->metadata));

        $this->post(route('logout'));

        $this->post('/login', [
            'identifier' => 'head@stkizito.test',
            'password' => $password,
        ])->assertRedirect();

        $this->assertAuthenticatedAs(User::query()->where('email', 'head@stkizito.test')->firstOrFail());

        $this->post(route('logout'));

        $this->post('/login', [
            'identifier' => 'bursar@stkizito.test',
            'password' => $password,
        ])->assertRedirect();

        $this->assertAuthenticatedAs(User::query()->where('email', 'bursar@stkizito.test')->firstOrFail());

        $this->assertTrue(Hash::check($password, User::query()->where('email', 'english@stkizito.test')->value('password')));
        $this->assertTrue(Hash::check($password, User::query()->where('email', 'parent@stkizito.test')->value('password')));
        $this->assertTrue(Hash::check($password, User::query()->where('email', 'learner.p4@stkizito.test')->value('password')));
    }

    public function test_platform_console_can_reset_existing_test_passwords(): void
    {
        app(WalkthroughSchoolService::class)->seed('Walkthrough-first-99');

        $this->actingAs($this->operator);
        $this->withRecentPlatformAuth();

        $password = 'Walkthrough-next-99';

        $this->post(route('platform.schools.walkthrough.store'), [
            'walkthrough_password' => $password,
            'walkthrough_password_confirmation' => $password,
        ])->assertRedirect();

        $this->post(route('logout'));

        $this->post('/login', [
            'identifier' => 'dos@stkizito.test',
            'password' => 'Walkthrough-first-99',
        ])->assertSessionHasErrors('identifier');

        $this->post('/login', [
            'identifier' => 'dos@stkizito.test',
            'password' => $password,
        ])->assertRedirect();

        $this->assertAuthenticatedAs(User::query()->where('email', 'dos@stkizito.test')->firstOrFail());
    }

    public function test_platform_console_can_seed_when_app_is_production(): void
    {
        $this->app['env'] = 'production';

        $this->actingAs($this->operator);
        $this->withRecentPlatformAuth();

        $password = 'Walkthrough-live-99';

        $this->post(route('platform.schools.walkthrough.store'), [
            'walkthrough_password' => $password,
            'walkthrough_password_confirmation' => $password,
        ])->assertRedirect();

        $this->assertNotNull(app(WalkthroughSchoolService::class)->existing());
        $this->assertTrue(Hash::check($password, User::query()->where('email', 'admin@stkizito.test')->value('password')));
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'walkthrough_password' => 'Walkthrough-shared-99',
            'walkthrough_password_confirmation' => 'Walkthrough-shared-99',
        ];
    }

    private function createPlatformStaff(string $roleKey, string $email): User
    {
        $user = User::create([
            'full_name' => 'PearlEdu Staff',
            'email' => $email,
            'status' => 'active',
            'password' => 'long-test-password',
        ]);
        $this->ensurePlatformAdminRole($user, $roleKey);

        return $user->refresh();
    }
}
