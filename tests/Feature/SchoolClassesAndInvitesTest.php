<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\Provisioning\StaffInvitationService;
use App\Services\Sms\Gateway\ProductionBlockedGateway;
use App\Services\Sms\Gateway\SmsGateway;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SchoolClassesAndInvitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    public function test_school_admin_can_create_class_with_stream(): void
    {
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($school->id);

        $this->actingAs($admin)
            ->post(route('app.classes.store'), [
                'level' => 'primary',
                'name' => 'P.5',
                'stream' => 'East',
                'code' => '',
            ])
            ->assertRedirect();

        $class = SchoolClass::query()->where('school_id', $school->id)->where('name', 'P.5')->first();
        $this->assertNotNull($class);
        $this->assertSame('East', $class->stream);
        $this->assertSame('P.5 East', $class->displayName());
        $this->assertNotSame('', $class->code);
    }

    public function test_staff_invite_succeeds_when_sms_is_blocked_but_email_works(): void
    {
        $this->app->instance(SmsGateway::class, new ProductionBlockedGateway('fake'));

        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $result = app(StaffInvitationService::class)->invite($school, [
            'full_name' => 'SMS Soft Fail',
            'email' => 'softfail@invite.test',
            'phone' => '0700123456',
            'role_keys' => ['subject_teacher'],
        ], $admin, false);

        $this->assertTrue($result['delivery']['email']);
        $this->assertFalse($result['delivery']['sms']);
        $this->assertNotEmpty($result['delivery']['warnings']);
        $this->assertNotEmpty($result['invitations']);
    }

    public function test_session_lifetime_defaults_to_thirty_minutes(): void
    {
        $this->assertSame(30, (int) config('session.lifetime'));
    }
}
