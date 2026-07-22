<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
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

        $response = $this->actingAs($operator)->post($host.'/platform/schools', [
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

        $show = $this->actingAs($operator)->get($host.'/platform/schools/'.$school->id);
        $show->assertOk();
        $show->assertSee('Buganda High', false);
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
            ->get($host.'/platform/schools/'.$created->id)
            ->assertOk()
            ->assertSee('Second School', false);
    }
}
