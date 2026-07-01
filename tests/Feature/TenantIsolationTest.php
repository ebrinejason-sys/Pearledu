<?php
namespace Tests\Feature;
use App\Models\School;
use App\Models\Student;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** GATE: requires a Postgres test DB owned by a NON-superuser role. */
class TenantIsolationTest extends TestCase {
    use RefreshDatabase;
    private School $a; private School $b; private TenantContext $ctx;

    protected function setUp(): void {
        parent::setUp();
        $this->ctx = app(TenantContext::class);
        $this->ctx->forPlatform();
        $this->a = School::create(['name'=>'Alpha','slug'=>'alpha','status'=>'active']);
        $this->b = School::create(['name'=>'Beta','slug'=>'beta','status'=>'active']);
        Student::create(['school_id'=>$this->a->id,'full_name'=>'Alpha Learner']);
        Student::create(['school_id'=>$this->b->id,'full_name'=>'Beta Learner']);
    }

    public function test_eloquent_scope_blocks_cross_tenant(): void {
        $this->ctx->forSchool($this->a->id);
        $n = Student::all()->pluck('full_name');
        $this->assertContains('Alpha Learner', $n);
        $this->assertNotContains('Beta Learner', $n);
    }

    public function test_raw_query_blocked_by_rls(): void {
        $this->ctx->forSchool($this->a->id);
        $n = DB::table('students')->pluck('full_name');
        $this->assertContains('Alpha Learner', $n);
        $this->assertNotContains('Beta Learner', $n);
    }

    public function test_idor_by_raw_id_blocked(): void {
        $betaId = DB::table('students')->where('school_id',$this->b->id)->value('id');
        $this->ctx->forSchool($this->a->id);
        $this->assertNull(Student::find($betaId));
        $this->assertNull(DB::table('students')->find($betaId));
    }

    public function test_no_context_is_fail_closed(): void {
        $this->ctx->clear();
        $this->assertCount(0, DB::table('students')->get());
    }
}
