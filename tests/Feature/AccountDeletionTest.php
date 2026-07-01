<?php
namespace Tests\Feature;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Account\AccountDeletionService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountDeletionTest extends TestCase {
    use RefreshDatabase;

    public function test_erase_removes_identity_and_assignments(): void {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $ctx = app(TenantContext::class); $ctx->forPlatform();
        $school = School::create(['name'=>'Del','slug'=>'del','status'=>'active']);
        $user = User::create(['full_name'=>'Temp','email'=>'temp@x.test','status'=>'active']);
        RoleAssignment::create(['user_id'=>$user->id,'role_id'=>Role::where('key','parent')->value('id'),'school_id'=>$school->id,'is_active'=>true]);

        app(AccountDeletionService::class)->erase($user, 'self');

        $this->assertEquals(0, DB::table('users')->where('id',$user->id)->count());          // hard-deleted
        $this->assertEquals(0, DB::table('role_assignments')->where('user_id',$user->id)->count());
    }
}
