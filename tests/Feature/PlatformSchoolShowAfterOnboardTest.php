<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireRecentPlatformAuth;
use App\Models\School;
use App\Models\User;
use App\Services\Auth\InvitationMailer;
use App\Services\Provisioning\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformSchoolShowAfterOnboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_onboard_redirects_to_school_show_on_pearledu_host(): void
    {
        Mail::fake();

        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $host = 'http://pearledu.voxsign.test';

        $response = $this->actingAs($operator)->post($host.'/admin/schools', [
            'name' => 'Buganda High',
            'district' => 'Kampala',
            'emis_number' => 'EMIS-404-FIX',
            'theme' => 'pearledu',
            'levels' => ['primary'],
            'admin' => [
                'full_name' => 'School Contact',
                'email' => 'contact@buganda.test',
            ],
        ]);

        $school = School::where('emis_number', 'EMIS-404-FIX')->first();
        $this->assertNotNull($school);

        $response->assertRedirect(route('platform.schools.show', $school));

        $show = $this->actingAs($operator)->get($host.'/admin/schools/'.$school->id);
        $show->assertOk();
        $show->assertSee('Buganda High', false);
        $show->assertSee('Tenant ID', false);
    }

    public function test_onboard_succeeds_when_invitation_delivery_throws(): void
    {
        $this->mock(InvitationMailer::class, function ($mock) {
            $mock->shouldReceive('send')->once()->andThrow(new \ErrorException('mailer transport failed'));
        });

        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $host = 'http://pearledu.voxsign.test';

        $response = $this->actingAs($operator)->post($host.'/admin/schools', [
            'name' => 'Mail Fail Academy',
            'district' => 'Mbale',
            'emis_number' => 'EMIS-MAIL-FAIL',
            'theme' => 'pearledu',
            'levels' => ['primary'],
            'admin' => [
                'full_name' => 'Mail Fail Admin',
                'email' => 'mailfail@academy.test',
            ],
        ]);

        $school = School::where('emis_number', 'EMIS-MAIL-FAIL')->first();
        $this->assertNotNull($school, 'School must be committed even when invite delivery fails');
        $response->assertRedirect(route('platform.schools.show', $school));
        $response->assertSessionHas('status');
        $this->assertStringContainsString('delivery failed', session('status'));
    }

    public function test_schools_index_ok_while_another_school_is_entered(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $existing = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $host = 'http://pearledu.voxsign.test';

        $this->actingAs($operator)
            ->withSession(['platform.entered_school_id' => $existing->id])
            ->get($host.'/admin/schools')
            ->assertOk()
            ->assertSee('Onboard school', false);
    }

    public function test_onboard_requires_uganda_district(): void
    {
        Mail::fake();

        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $host = 'http://pearledu.voxsign.test';

        $this->actingAs($operator)->post($host.'/admin/schools', [
            'name' => 'No District School',
            'district' => 'NotARealDistrict',
            'theme' => 'pearledu',
            'levels' => ['primary'],
            'admin' => [
                'full_name' => 'Contact',
                'email' => 'nodistrict@test.example',
            ],
        ])->assertSessionHasErrors('district');
    }

    public function test_operator_can_delete_school(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Delete Me Academy', 'district' => 'Jinja', 'theme' => 'pearledu'],
            levels: ['primary'],
            admin: ['full_name' => 'Del Admin', 'email' => 'del@delete.test'],
            operatorId: $operator->id,
        )['school'];

        $host = 'http://pearledu.voxsign.test';
        $id = $school->id;

        $this->actingAs($operator)
            ->withSession([RequireRecentPlatformAuth::SESSION_KEY => time()])
            ->delete($host.'/admin/schools/'.$id, ['confirm_name' => 'Delete Me Academy'])
            ->assertRedirect(route('platform.schools.index'));

        $school->refresh();
        $this->assertSame('deletion_scheduled', $school->status);
        $this->assertNotNull($school->deletion_scheduled_at);
        $this->assertNotNull(School::find($id));
    }

    public function test_scheduling_deletion_twice_does_not_500(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Already Scheduled Academy', 'district' => 'Jinja', 'theme' => 'pearledu'],
            levels: ['primary'],
            admin: ['full_name' => 'Sched Admin', 'email' => 'sched@delete.test'],
            operatorId: $operator->id,
        )['school'];

        $host = 'http://pearledu.voxsign.test';
        $session = [RequireRecentPlatformAuth::SESSION_KEY => time()];

        $this->actingAs($operator)
            ->withSession($session)
            ->delete($host.'/admin/schools/'.$school->id, ['confirm_name' => 'Already Scheduled Academy'])
            ->assertRedirect(route('platform.schools.index'));

        $this->actingAs($operator)
            ->withSession($session)
            ->delete($host.'/admin/schools/'.$school->id, ['confirm_name' => 'Already Scheduled Academy'])
            ->assertRedirect(route('platform.schools.show', $school))
            ->assertSessionHasErrors('school');

        $this->assertSame('deletion_scheduled', $school->fresh()->status);
    }

    public function test_operator_can_permanently_delete_school(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Wipe Me Academy', 'district' => 'Gulu', 'theme' => 'pearledu'],
            levels: ['primary'],
            admin: ['full_name' => 'Wipe Admin', 'email' => 'wipe@delete.test'],
            operatorId: $operator->id,
        )['school'];

        $host = 'http://pearledu.voxsign.test';
        $id = $school->id;

        $this->actingAs($operator)
            ->withSession([RequireRecentPlatformAuth::SESSION_KEY => time()])
            ->delete($host.'/admin/schools/'.$id, [
                'confirm_name' => 'Wipe Me Academy',
                'permanent' => '1',
            ])
            ->assertRedirect(route('platform.schools.index'));

        $this->assertNull(School::find($id));
    }

    public function test_school_show_is_ok_after_failed_delete_redirect(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = School::query()->where('slug', 'like', 'pearledu%')->firstOrFail();
        $host = 'http://pearledu.voxsign.test';

        $this->actingAs($operator)
            ->get($host.'/admin/schools/'.$school->id)
            ->assertOk()
            ->assertSee('Delete permanently', false);
    }

    public function test_school_show_is_reachable_when_another_school_is_entered(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $existing = School::where('slug', 'like', 'pearledu%')->firstOrFail();

        $created = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Second School', 'district' => 'Gulu', 'theme' => 'pearledu'],
            levels: ['primary'],
            admin: ['full_name' => 'Admin Two', 'email' => 'two@second.test'],
            operatorId: $operator->id,
        )['school'];

        $host = 'http://pearledu.voxsign.test';

        $this->actingAs($operator)
            ->withSession(['platform.entered_school_id' => $existing->id])
            ->get($host.'/admin/schools/'.$created->id)
            ->assertOk()
            ->assertSee('Second School', false);
    }
}
