<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireRecentPlatformAuth;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ActsAsPlatformOperator;
use Tests\TestCase;

class ConfirmPlatformAuthTest extends TestCase
{
    use ActsAsPlatformOperator;
    use RefreshDatabase;

    private User $operator;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        app(TenantContext::class)->forPlatform();
        $this->operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
    }

    public function test_resume_page_shows_continue_and_submits_after_the_form(): void
    {
        $this->actingAs($this->operator);
        session([
            RequireRecentPlatformAuth::PENDING_KEY => [
                'uri' => '/admin/schools/'.$this->school->id,
                'method' => 'PUT',
                'input' => [
                    'name' => 'Updated School',
                    'admin' => [
                        'full_name' => 'Jane Admin',
                        'email' => 'jane@school.test',
                    ],
                    'levels' => ['primary', 'preprimary'],
                ],
            ],
        ]);

        $html = $this->get(route('platform.auth.confirm.resume'))
            ->assertOk()
            ->assertSee('Continue', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="admin[full_name]"', false)
            ->assertSee('name="admin[email]"', false)
            ->assertSee('name="levels[0]"', false)
            ->getContent();

        $formPos = strpos($html, 'id="resume-sensitive"');
        $scriptPos = strpos($html, 'getElementById(\'resume-sensitive\').submit()');

        $this->assertNotFalse($formPos);
        $this->assertNotFalse($scriptPos);
        $this->assertGreaterThan($formPos, $scriptPos);
    }

    public function test_resume_without_pending_action_returns_to_dashboard(): void
    {
        $this->actingAs($this->operator)
            ->get(route('platform.auth.confirm.resume'))
            ->assertRedirect(route('platform.dashboard'));
    }

    public function test_sensitive_post_resumes_after_password_confirm(): void
    {
        $this->actingAs($this->operator)
            ->put(route('platform.schools.update', $this->school), [
                'name' => $this->school->name,
                'district' => $this->school->district ?: 'Kampala',
                'theme' => $this->school->theme ?: 'pearledu',
                'status' => $this->school->status,
            ])
            ->assertRedirect(route('platform.auth.confirm'));

        $this->post(route('platform.auth.confirm.store'), [
            'password' => config('platform.admin_password', 'test-platform-password-CHANGE'),
        ])->assertRedirect(route('platform.auth.confirm.resume'));

        $this->get(route('platform.auth.confirm.resume'))
            ->assertOk()
            ->assertSee('Continue', false)
            ->assertSee('name="name"', false)
            ->assertSee(e((string) $this->school->name), false);
    }
}
