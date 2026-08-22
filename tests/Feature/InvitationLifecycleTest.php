<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\User;
use App\Services\Account\InvitationService;
use App\Services\Auth\PasswordResetService;
use App\Services\Provisioning\SchoolProvisioner;
use App\Services\Provisioning\StaffInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\Support\TeacherInviteLoad;
use Tests\TestCase;

class InvitationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    public function test_staff_invite_of_existing_active_user_stays_inactive_until_accept(): void
    {
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $existing = User::factory()->create([
            'email' => 'already@active.test',
            'status' => 'active',
            'password' => Hash::make('password12345'),
        ]);

        $load = TeacherInviteLoad::ensure($school);
        $result = app(StaffInvitationService::class)->invite($school, [
            'full_name' => $existing->full_name,
            'email' => $existing->email,
            'role_keys' => ['subject_teacher'],
            'teaching_assignments' => $load['teaching_assignments'],
        ], $admin, false);

        $roleId = Role::where('key', 'subject_teacher')->value('id');
        $assignment = RoleAssignment::query()
            ->where('user_id', $existing->id)
            ->where('school_id', $school->id)
            ->where('role_id', $roleId)
            ->firstOrFail();

        $this->assertFalse($assignment->is_active);

        app(InvitationService::class)->accept(
            $result['invitations'][0]->id,
            $result['tokens'][0],
            'newpassword99',
        );

        $this->assertTrue($assignment->fresh()->is_active);
    }

    public function test_onboard_admin_assignment_inactive_until_invite_accepted(): void
    {
        $result = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Pending Admin School', 'district' => 'Jinja', 'theme' => 'pearledu'],
            levels: ['primary'],
            admin: ['full_name' => 'Pam Pending', 'email' => 'pam@pending.test'],
            operatorId: null,
        );

        $roleId = Role::where('key', 'school_admin')->value('id');
        $assignment = RoleAssignment::query()
            ->where('user_id', $result['admin']->id)
            ->where('school_id', $result['school']->id)
            ->where('role_id', $roleId)
            ->firstOrFail();

        $this->assertFalse($assignment->is_active);

        $invitation = SchoolInvitation::where('school_id', $result['school']->id)->latest('id')->firstOrFail();
        app(InvitationService::class)->accept($invitation->id, $result['invite_token'], 'password12345');

        $this->assertTrue($assignment->fresh()->is_active);
    }

    public function test_accepting_one_batch_does_not_activate_unrelated_pending_role(): void
    {
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $bursarInvite = app(StaffInvitationService::class)->invite($school, [
            'full_name' => 'Multi Role',
            'email' => 'multi@roles.test',
            'role_keys' => ['bursar'],
        ], $admin, false);

        $load = TeacherInviteLoad::ensure($school);
        app(StaffInvitationService::class)->invite($school, [
            'full_name' => 'Multi Role',
            'email' => 'multi@roles.test',
            'role_keys' => ['subject_teacher'],
            'teaching_assignments' => $load['teaching_assignments'],
        ], $admin, false);

        app(InvitationService::class)->accept(
            $bursarInvite['invitations'][0]->id,
            $bursarInvite['tokens'][0],
            'password12345',
        );

        $user = User::where('email', 'multi@roles.test')->firstOrFail();
        $bursarRole = Role::where('key', 'bursar')->value('id');
        $teacherRole = Role::where('key', 'subject_teacher')->value('id');

        $this->assertTrue(
            RoleAssignment::query()
                ->where('user_id', $user->id)
                ->where('role_id', $bursarRole)
                ->where('is_active', true)
                ->exists()
        );
        $this->assertFalse(
            RoleAssignment::query()
                ->where('user_id', $user->id)
                ->where('role_id', $teacherRole)
                ->where('is_active', true)
                ->exists()
        );
    }

    public function test_multi_role_invite_batch_activates_all_roles_in_batch(): void
    {
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $load = TeacherInviteLoad::ensure($school);
        $result = app(StaffInvitationService::class)->invite($school, [
            'full_name' => 'Batch Person',
            'email' => 'batch@roles.test',
            'role_keys' => ['deputy_head_teacher', 'subject_teacher'],
            'teaching_assignments' => $load['teaching_assignments'],
        ], $admin, false);

        $this->assertCount(2, $result['invitations']);
        $this->assertSame(
            $result['invitations'][0]->batch_id,
            $result['invitations'][1]->batch_id
        );

        app(InvitationService::class)->accept(
            $result['invitations'][0]->id,
            $result['tokens'][0],
            'password12345',
        );

        $user = User::where('email', 'batch@roles.test')->firstOrFail();
        foreach (['deputy_head_teacher', 'subject_teacher'] as $key) {
            $this->assertTrue(
                RoleAssignment::query()
                    ->where('user_id', $user->id)
                    ->where('role_id', Role::where('key', $key)->value('id'))
                    ->where('is_active', true)
                    ->exists(),
                "Expected {$key} to be active after batch accept"
            );
        }
    }

    public function test_disabled_user_cannot_reset_password_with_old_token(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled@reset.test',
            'status' => 'active',
            'password' => Hash::make('oldpassword12'),
        ]);

        $token = Password::broker()->createToken($user);
        $user->forceFill(['status' => 'disabled'])->save();
        app(PasswordResetService::class)->revokeTokens($user);

        $status = app(PasswordResetService::class)->reset(
            $user->email,
            $token,
            'brandnewpass99',
        );

        $this->assertSame(Password::INVALID_USER, $status);
        $this->assertSame('disabled', $user->fresh()->status);
        $this->assertTrue(Hash::check('oldpassword12', $user->fresh()->password));
    }

    public function test_platform_invite_accept_requires_2fa_challenge(): void
    {
        $user = User::factory()->platform()->create([
            'email' => 'ops.invite@test.local',
            'status' => 'invited',
            'password' => null,
        ]);

        $roleId = Role::where('key', 'support_agent')->value('id');
        RoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'school_id' => null,
            'is_active' => false,
        ]);

        $raw = 'platform-invite-token-raw-value';
        $invitation = SchoolInvitation::create([
            'school_id' => null,
            'user_id' => $user->id,
            'email' => $user->email,
            'role_key' => 'support_agent',
            'token_hash' => Hash::make($raw),
            'expires_at' => now()->addDay(),
            'batch_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $response = $this->post('/invitations/'.$invitation->id.'/accept', [
            'token' => $raw,
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ]);

        $response->assertRedirect('/login/2fa/challenge');
        $this->assertGuest();
        $this->assertTrue(session()->has('2fa_pending_user_id'));
        $this->assertSame($user->id, (int) session('2fa_pending_user_id'));
        $this->assertTrue($user->fresh()->isPlatformOperator());
        $this->assertSame('active', $user->fresh()->status);
    }
}
