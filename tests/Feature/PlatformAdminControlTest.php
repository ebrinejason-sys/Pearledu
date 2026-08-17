<?php

namespace Tests\Feature;

use App\Mail\Auth\ResetPasswordMail;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\Platform\PlatformStaffService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ActsAsPlatformOperator;
use Tests\TestCase;

class PlatformAdminControlTest extends TestCase
{
    use ActsAsPlatformOperator;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        app(TenantContext::class)->forPlatform();
        $this->admin = User::where('is_platform', true)->firstOrFail();
    }

    public function test_admin_can_open_system_overview_and_audit_trail(): void
    {
        $this->actingAs($this->admin)
            ->get(route('platform.system.index'))
            ->assertOk()
            ->assertSee('System &amp; security overview', false);

        $this->actingAs($this->admin)
            ->get(route('platform.audit.index'))
            ->assertOk()
            ->assertSee('Audit trail');
    }

    public function test_platform_admin_can_manage_peer_admin_but_not_self(): void
    {
        $peer = $this->createPeerAdmin();
        $service = app(PlatformStaffService::class);

        $this->assertTrue($service->canManage($this->admin, $peer));
        $this->assertFalse($service->canManage($this->admin, $this->admin));

        $this->actingAs($this->admin)
            ->get(route('platform.operators.edit', $peer))
            ->assertOk()
            ->assertSee($peer->full_name);
    }

    public function test_admin_can_force_logout_and_reset_two_factor_for_peer(): void
    {
        $peer = $this->createPeerAdmin();
        $peer->forceFill([
            'two_factor_secret' => 'encrypted-test-secret',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['recovery-code'],
        ])->save();

        DB::table('sessions')->insert([
            'id' => 'peer-session',
            'user_id' => $peer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->admin);
        $this->withRecentPlatformAuth();

        $this->post(route('platform.operators.force-logout', $peer))->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'peer-session']);
        $this->assertTrue(AuditLog::where('action', 'platform.staff.force_logout')->where('entity_id', $peer->id)->exists());

        $this->post(route('platform.operators.reset-two-factor', $peer))->assertRedirect();
        $peer->refresh();
        $this->assertFalse($peer->hasTwoFactorEnabled());
        $this->assertNull($peer->two_factor_secret);
        $this->assertTrue(AuditLog::where('action', 'platform.staff.two_factor_reset')->where('entity_id', $peer->id)->exists());
    }

    public function test_platform_admin_can_send_password_reset_for_staff(): void
    {
        $peer = $this->createPeerAdmin();
        $originalHash = $peer->password;

        Mail::fake();
        $this->actingAs($this->admin);
        $this->withRecentPlatformAuth();

        $this->post(route('platform.operators.reset-password', $peer))
            ->assertRedirect()
            ->assertSessionHas('status');

        $peer->refresh();
        $this->assertSame($originalHash, $peer->password);
        Mail::assertSent(ResetPasswordMail::class, fn ($mail) => $mail->hasTo($peer->email));
        $this->assertTrue(
            AuditLog::where('action', 'platform.staff.password_reset')->where('entity_id', $peer->id)->exists()
        );
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $peer->email]);
    }

    public function test_platform_admin_cannot_send_password_reset_for_self(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);
        $this->withRecentPlatformAuth();

        $this->post(route('platform.operators.reset-password', $this->admin))
            ->assertRedirect()
            ->assertSessionHasErrors('password');

        Mail::assertNothingSent();
    }

    public function test_support_agent_cannot_send_staff_password_reset(): void
    {
        $peer = $this->createPeerAdmin();
        $agent = $this->createPlatformStaff('support_agent', 'agent@pearledu.test');

        Mail::fake();
        $this->actingAs($agent);
        $this->withRecentPlatformAuth();

        $this->post(route('platform.operators.reset-password', $peer))->assertForbidden();
        Mail::assertNothingSent();
    }

    public function test_disabled_staff_cannot_receive_password_reset(): void
    {
        $peer = $this->createPeerAdmin();
        $peer->forceFill(['status' => 'disabled'])->save();

        Mail::fake();
        $this->actingAs($this->admin);
        $this->withRecentPlatformAuth();

        $this->post(route('platform.operators.reset-password', $peer))
            ->assertRedirect()
            ->assertSessionHasErrors('password');

        Mail::assertNothingSent();
    }

    private function createPeerAdmin(): User
    {
        return $this->createPlatformStaff('platform_admin', 'peer-admin@pearledu.test', 'Peer Platform Admin');
    }

    private function createPlatformStaff(string $roleKey, string $email, string $name = 'PearlEdu Staff'): User
    {
        $user = User::create([
            'full_name' => $name,
            'email' => $email,
            'status' => 'active',
            'password' => 'long-test-password',
        ]);
        $user->forceFill(['is_platform' => true])->save();

        RoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => Role::where('key', $roleKey)->value('id'),
            'school_id' => null,
            'is_active' => true,
            'assigned_by' => $this->admin->id,
        ]);

        return $user->refresh();
    }
}
