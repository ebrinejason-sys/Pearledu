<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\Platform\PlatformStaffService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function createPeerAdmin(): User
    {
        $peer = User::create([
            'full_name' => 'Peer Platform Admin',
            'email' => 'peer-admin@pearledu.test',
            'status' => 'active',
            'password' => 'long-test-password',
        ]);
        $peer->forceFill(['is_platform' => true])->save();

        RoleAssignment::create([
            'user_id' => $peer->id,
            'role_id' => Role::where('key', 'platform_admin')->value('id'),
            'school_id' => null,
            'is_active' => true,
            'assigned_by' => $this->admin->id,
        ]);

        return $peer->refresh();
    }
}
