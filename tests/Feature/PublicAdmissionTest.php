<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\School;
use App\Models\SchoolClass;
use App\Services\Provisioning\SchoolProvisioner;
use App\Services\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAdmissionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private SchoolClass $class;
    private string $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P1A',
            'code' => 'P1A',
        ]);
        $this->host = 'http://'.$this->school->slug.'.'.config('tenancy.base_domain');
    }

    public function test_apply_form_renders_on_tenant_host(): void
    {
        $response = $this->get($this->host.'/apply');

        $response->assertOk();
        $response->assertSee('Apply for admission', false);
        $response->assertSee('name="website"', false);
    }

    public function test_valid_application_is_stored(): void
    {
        $response = $this->post($this->host.'/apply', [
            'applicant_name' => 'Amina Okello',
            'guardian_name' => 'Grace Okello',
            'guardian_phone' => '+256700000001',
            'guardian_email' => 'grace@example.com',
            'requested_class_id' => $this->class->id,
            'notes' => 'Looking for Term 1',
            'website' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('admission_applications', [
            'school_id' => $this->school->id,
            'applicant_name' => 'Amina Okello',
            'requested_class_id' => $this->class->id,
            'status' => 'pending',
        ]);
    }

    public function test_honeypot_blocks_bots(): void
    {
        $response = $this->from($this->host.'/apply')->post($this->host.'/apply', [
            'applicant_name' => 'Bot Applicant',
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect($this->host.'/apply');
        $response->assertSessionHasErrors('website');
        $this->assertSame(0, AdmissionApplication::count());
    }

    public function test_rejects_class_from_another_school(): void
    {
        app(TenantContext::class)->forPlatform();
        $other = app(SchoolProvisioner::class)->onboard(
            school: [
                'name' => 'Other School',
                'district' => 'Jinja',
                'emis_number' => '8880001',
                'theme' => 'pearledu',
            ],
            levels: ['primary'],
            admin: [
                'full_name' => 'Other Admin',
                'email' => 'admin@other-apply.test',
            ],
            operatorId: User::where('is_platform', true)->value('id'),
        )['school'];

        app(TenantContext::class)->forSchool($other->id);
        $foreignClass = SchoolClass::create([
            'school_id' => $other->id,
            'level' => 'primary',
            'name' => 'Foreign P1',
            'code' => 'FP1',
        ]);
        app(TenantContext::class)->clear();

        $response = $this->from($this->host.'/apply')->post($this->host.'/apply', [
            'applicant_name' => 'Cross Tenant',
            'requested_class_id' => $foreignClass->id,
            'website' => '',
        ]);

        $response->assertRedirect($this->host.'/apply');
        $response->assertSessionHasErrors('requested_class_id');
        $this->assertSame(0, AdmissionApplication::count());
    }
}
