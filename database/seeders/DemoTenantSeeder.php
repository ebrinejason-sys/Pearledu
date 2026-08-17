<?php

namespace Database\Seeders;

use App\Models\Guardianship;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Provisioning\SchoolProvisioner;
use App\Services\Sms\SmsCreditService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Local/test structure only — never publishes usable login credentials.
 * Accounts are active for actingAs/impersonation tests, but passwords are random.
 */
class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $ctx = app(TenantContext::class);
        $ctx->forPlatform();

        $operator = User::where('is_platform', true)->first();

        $res = app(SchoolProvisioner::class)->onboard(
            school: ['name' => "St. Andrew's Mixed Schools", 'district' => 'Kampala', 'emis_number' => '1043221', 'theme' => 'pearledu'],
            levels: ['preprimary', 'primary', 'lower_secondary', 'upper_secondary'],
            admin: ['full_name' => 'Grace Nakato', 'email' => 'admin@standrews.test'],
            operatorId: $operator?->id,
        );
        $school = $res['school'];

        // Activate structure users with unusable random passwords (no shared demo logins).
        User::where('email', 'admin@standrews.test')->update([
            'status' => 'active',
            'password' => Hash::make(Str::password(40)),
        ]);
        $schoolAdmin = User::where('email', 'admin@standrews.test')->firstOrFail();
        RoleAssignment::query()
            ->where('user_id', $schoolAdmin->id)
            ->where('school_id', $school->id)
            ->whereHas('role', fn ($query) => $query->where('key', 'school_admin'))
            ->update(['is_active' => true]);

        $profiles = [
            'director' => ['Daniel Director', 'director@standrews.test'],
            'head_teacher' => ['Helen Head', 'head@standrews.test'],
            'deputy_head_teacher' => ['Diana Deputy', 'deputy@standrews.test'],
            'director_of_studies' => ['Doris Studies', 'dos@standrews.test'],
            'bursar' => ['Bernard Bursar', 'bursar@standrews.test'],
            'class_teacher' => ['Carol Class', 'classteacher@standrews.test'],
            'subject_teacher' => ['Simon Subject', 'teacher@standrews.test'],
            'parent' => ['Patricia Parent', 'parent@standrews.test'],
            'student' => ['Stella Student', 'student@standrews.test'],
        ];
        $users = [];
        foreach ($profiles as $roleKey => [$name, $email]) {
            $u = User::firstOrCreate(
                ['email' => $email],
                [
                    'full_name' => $name,
                    'status' => 'active',
                    'password' => Hash::make(Str::password(40)),
                ],
            );
            if ($u->status !== 'active') {
                $u->forceFill([
                    'status' => 'active',
                    'password' => Hash::make(Str::password(40)),
                ])->save();
            }
            RoleAssignment::firstOrCreate([
                'user_id' => $u->id, 'role_id' => Role::where('key', $roleKey)->value('id'),
                'school_id' => $school->id, 'is_active' => true,
            ]);
            $users[$roleKey] = $u;
        }

        $ctx->forSchool($school->id);

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $school->id, 'code' => 'P5A'],
            ['level' => 'primary', 'name' => 'P5A'],
        );

        // Learner login: student role user ↔ Student.user_id (portal / LMS / CBT).
        $linkedStudent = Student::firstOrCreate(
            ['school_id' => $school->id, 'full_name' => 'Stella Student'],
            [
                'status' => 'active',
                'class_id' => $class->id,
                'user_id' => $users['student']->id,
            ],
        );
        if (! $linkedStudent->user_id) {
            $linkedStudent->forceFill([
                'user_id' => $users['student']->id,
                'class_id' => $linkedStudent->class_id ?: $class->id,
            ])->save();
        }

        Guardianship::firstOrCreate(
            [
                'school_id' => $school->id,
                'student_id' => $linkedStudent->id,
                'guardian_user_id' => $users['parent']->id,
            ],
            [
                'relationship' => 'parent',
                'is_primary' => true,
            ],
        );

        foreach (['Aisha Nabukenya', 'Joseph Mugisha'] as $n) {
            Student::firstOrCreate(
                ['school_id' => $school->id, 'full_name' => $n],
                ['status' => 'active', 'class_id' => $class->id],
            );
        }

        $ctx->forPlatform();
        app(SmsCreditService::class)->topUp($school, 500, $operator?->id, 'demo-allocation');
    }
}
