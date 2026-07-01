<?php
namespace Database\Seeders;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Student;
use App\Models\User;
use App\Services\Provisioning\SchoolProvisioner;
use App\Services\Sms\SmsCreditService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class DemoTenantSeeder extends Seeder {
    public function run(): void {
        $ctx = app(TenantContext::class);
        $ctx->forPlatform();

        $operator = User::where('email','admin@voxsign.co.ug')->first();

        $res = app(SchoolProvisioner::class)->onboard(
            school: ['name'=>"St. Andrew's Mixed Schools",'district'=>'Kampala','emis_number'=>'1043221','theme'=>'pearledu'],
            levels: ['preprimary','primary','lower_secondary','upper_secondary'],
            admin:  ['full_name'=>'Grace Nakato','email'=>'admin@standrews.test'],
            operatorId: $operator?->id,
        );
        $school = $res['school'];

        // Activate the seeded School Admin so it is immediately explorable.
        User::where('email','admin@standrews.test')->update(['status'=>'active','password'=>bcrypt('password1234')]);

        // Ready-to-explore profiles, one per school role (password: password1234).
        $profiles = [
            'director'        => ['Daniel Director','director@standrews.test'],
            'head_teacher'    => ['Helen Head','head@standrews.test'],
            'bursar'          => ['Bernard Bursar','bursar@standrews.test'],
            'class_teacher'   => ['Carol Class','classteacher@standrews.test'],
            'subject_teacher' => ['Simon Subject','teacher@standrews.test'],
            'parent'          => ['Patricia Parent','parent@standrews.test'],
            'student'         => ['Stella Student','student@standrews.test'],
        ];
        foreach ($profiles as $roleKey => [$name, $email]) {
            $u = User::firstOrCreate(['email'=>$email],
                ['full_name'=>$name,'status'=>'active','password'=>bcrypt('password1234')]);
            RoleAssignment::firstOrCreate([
                'user_id'=>$u->id,'role_id'=>Role::where('key',$roleKey)->value('id'),
                'school_id'=>$school->id,'is_active'=>true,
            ]);
        }

        // A couple of learner records for context.
        foreach (['Aisha Nabukenya','Joseph Mugisha'] as $n) {
            Student::firstOrCreate(['school_id'=>$school->id,'full_name'=>$n],['status'=>'active']);
        }

        // Allocate demo SMS credit so the school can try the SMS module.
        app(SmsCreditService::class)->topUp($school, 500, $operator?->id, 'demo-allocation');
    }
}
