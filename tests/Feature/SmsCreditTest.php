<?php
namespace Tests\Feature;
use App\Models\School;
use App\Services\Sms\SmsCreditService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsCreditTest extends TestCase {
    use RefreshDatabase;

    public function test_topup_and_spend_track_balance_and_block_overspend(): void {
        $ctx = app(TenantContext::class); $ctx->forPlatform();
        $school = School::create(['name'=>'Credit','slug'=>'credit','status'=>'active']);
        $svc = app(SmsCreditService::class);

        $ctx->forSchool($school->id);
        $svc->topUp($school, 10, null, 'test');
        $this->assertEquals(10, $svc->balance($school->id));

        $svc->spend($school->id, 4, 'msg1', null);
        $this->assertEquals(6, $svc->balance($school->id));

        $this->expectExceptionMessage('Insufficient SMS credit.');
        $svc->spend($school->id, 99, 'msg2', null);
    }
}
