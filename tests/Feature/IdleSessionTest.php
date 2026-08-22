<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdleSessionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $this->teacher->forceFill([
            'password' => Hash::make('idle-password-12'),
            'status' => 'active',
        ])->save();
    }

    private function actingAsTeacher(): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($this->teacher)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_idle_session_signs_out_after_lifetime(): void
    {
        $this->teacher->forceFill(['last_seen_at' => now()->subMinutes(31)])->save();

        $this->actingAsTeacher()
            ->get(route('app.home'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_recent_activity_keeps_the_session(): void
    {
        $this->teacher->forceFill(['last_seen_at' => now()->subMinutes(10)])->save();

        $this->actingAsTeacher()->get(route('app.home'))->assertOk();
        $this->assertAuthenticatedAs($this->teacher);
        $this->assertTrue($this->teacher->fresh()->last_seen_at->greaterThan(now()->subMinute()));
    }

    public function test_heartbeat_extends_last_seen(): void
    {
        $this->teacher->forceFill(['last_seen_at' => now()->subMinutes(20)])->save();

        $this->actingAsTeacher()
            ->postJson(route('session.heartbeat'))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertTrue($this->teacher->fresh()->last_seen_at->greaterThan(now()->subMinute()));
    }

    public function test_expired_idle_window_rejects_remember_me_resurrection(): void
    {
        $this->post('/login', [
            'identifier' => $this->teacher->email,
            'password' => 'idle-password-12',
            'remember' => '1',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($this->teacher);

        $this->travel(31)->minutes();

        $this->get(route('app.home'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_page_explains_idle_logout(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="remember"', false)
            ->assertSee('minutes of inactivity');
    }
}
