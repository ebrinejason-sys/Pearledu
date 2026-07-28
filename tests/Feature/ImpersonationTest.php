<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\Platform\ImpersonationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ActsAsPlatformOperator;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use ActsAsPlatformOperator;
    use RefreshDatabase;

    private User $operator;

    private User $schoolAdmin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $ctx = app(TenantContext::class);
        $ctx->forPlatform();

        $this->operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $this->schoolAdmin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
    }

    /** @return array{reason: string} */
    private function imitatePayload(): array
    {
        return ['reason' => 'Support investigation ticket follow-up'];
    }

    private function actingAsOperatorWithRecentAuth()
    {
        $this->actingAs($this->operator);
        $this->withRecentPlatformAuth();

        return $this;
    }

    public function test_platform_operator_can_imitate_school_user(): void
    {
        $response = $this->actingAsOperatorWithRecentAuth()->post(
            route('platform.schools.imitate', [$this->school, $this->schoolAdmin]),
            $this->imitatePayload()
        );

        $response->assertRedirect(route('app.home'));
        $this->assertAuthenticatedAs($this->schoolAdmin);
        $this->assertTrue(app(ImpersonationService::class)->isActive());
        $this->assertSame($this->operator->id, session(ImpersonationService::SESSION_OPERATOR));
        $this->assertSame($this->school->id, app(TenantContext::class)->schoolId());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.impersonation.started',
            'actor_id' => $this->operator->id,
        ]);
    }

    public function test_imitating_user_cannot_access_platform_console(): void
    {
        $this->actingAsOperatorWithRecentAuth()->post(
            route('platform.schools.imitate', [$this->school, $this->schoolAdmin]),
            $this->imitatePayload()
        );

        $this->get(route('platform.dashboard'))->assertForbidden();
    }

    public function test_operator_can_end_imitation(): void
    {
        $this->actingAsOperatorWithRecentAuth()->post(
            route('platform.schools.imitate', [$this->school, $this->schoolAdmin]),
            $this->imitatePayload()
        );

        $response = $this->post(route('impersonation.stop'));
        $response->assertRedirect(route('platform.dashboard'));
        $this->assertAuthenticatedAs($this->operator);
        $this->assertFalse(app(ImpersonationService::class)->isActive());

        $this->assertTrue(
            AuditLog::where('action', 'user.impersonation.stopped')->exists()
        );
    }

    public function test_cannot_imitate_platform_operator(): void
    {
        $other = new User([
            'full_name' => 'Other Admin',
            'email' => 'other-admin@test.local',
            'status' => 'active',
            'password' => 'password1234',
        ]);
        $other->forceFill(['is_platform' => true])->save();

        $this->actingAsOperatorWithRecentAuth()->post(
            route('platform.schools.imitate', [$this->school, $other]),
            $this->imitatePayload()
        )->assertSessionHasErrors('user');
    }

    public function test_imitate_requires_support_reason(): void
    {
        $this->actingAsOperatorWithRecentAuth()->post(
            route('platform.schools.imitate', [$this->school, $this->schoolAdmin]),
            ['reason' => 'short']
        )->assertSessionHasErrors('reason');
    }

    public function test_imitate_without_recent_auth_resumes_after_password_confirm(): void
    {
        $this->actingAs($this->operator)
            ->post(route('platform.schools.imitate', [$this->school, $this->schoolAdmin]), $this->imitatePayload())
            ->assertRedirect(route('platform.auth.confirm'));

        $this->assertNotNull(session(\App\Http\Middleware\RequireRecentPlatformAuth::PENDING_KEY));

        $this->post(route('platform.auth.confirm.store'), [
            'password' => config('platform.admin_password', 'test-platform-password-CHANGE'),
        ])->assertRedirect(route('platform.auth.confirm.resume'));

        $this->get(route('platform.auth.confirm.resume'))
            ->assertOk()
            ->assertSee('name="reason"', false);
    }
}
