<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\Authorization\InvitePolicy;
use App\Services\Provisioning\StaffInvitationService;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HierarchicalInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_bursar_cannot_invite_staff(): void
    {
        $this->seed(RoleSeeder::class);
        Mail::fake();

        app(TenantContext::class)->forPlatform();
        $school = School::create(['name' => 'Invite School', 'slug' => 'invite1', 'status' => 'active']);
        $bursar = User::factory()->create(['status' => 'active', 'email' => 'bursar@s.test']);
        $roleId = Role::where('key', 'bursar')->value('id');
        RoleAssignment::create([
            'user_id' => $bursar->id,
            'role_id' => $roleId,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $policy = app(InvitePolicy::class);
        $this->assertFalse($policy->canInvite($bursar, 'class_teacher', $school->id));

        $this->expectException(ValidationException::class);
        app(StaffInvitationService::class)->invite($school, [
            'full_name' => 'New Teacher',
            'email' => 'teacher@s.test',
            'role_keys' => ['class_teacher'],
        ], $bursar, false);
    }

    public function test_school_admin_can_invite_deputy_by_phone(): void
    {
        $this->seed(RoleSeeder::class);
        Mail::fake();

        app(TenantContext::class)->forPlatform();
        $school = School::create(['name' => 'Invite School 2', 'slug' => 'invite2', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active', 'email' => 'admin@s.test']);
        RoleAssignment::create([
            'user_id' => $admin->id,
            'role_id' => Role::where('key', 'school_admin')->value('id'),
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        app(TenantContext::class)->forSchool($school->id);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'level' => 'primary',
            'name' => 'P4 Red',
            'code' => 'P4R',
        ]);

        $result = app(StaffInvitationService::class)->invite($school, [
            'full_name' => 'Deputy Dee',
            'phone' => '0777123456',
            'role_keys' => ['deputy_head_teacher', 'class_teacher'],
            'class_id' => $class->id,
        ], $admin, false);

        $this->assertSame('invited', $result['user']->status);
        $this->assertNotNull($result['user']->phone);
        $this->assertCount(2, $result['invitations']);
    }
}
