<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Navigation\NavigationBuilder;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StaffRoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_dos_can_invite_teachers_but_cannot_edit_existing_staff_roles(): void
    {
        Mail::fake();
        $dos = User::where('email', 'dos@standrews.test')->firstOrFail();
        $head = User::where('email', 'head@standrews.test')->firstOrFail();

        $this->actingAsInSchool($dos)->get(route('app.staff.index'))
            ->assertOk()
            ->assertDontSee('Save responsibilities');

        $this->actingAsInSchool($dos)->post(route('app.staff.store'), [
            'full_name' => 'Invited Teacher',
            'email' => 'invited-teacher@standrews.test',
            'role_keys' => ['subject_teacher'],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'invited-teacher@standrews.test']);

        $this->actingAsInSchool($dos)->put(route('app.staff.roles', $head), [
            'role_keys' => ['subject_teacher'],
        ])->assertForbidden();

        $this->assertTrue($head->fresh()->hasRoleInSchool(Role::HEAD_TEACHER, $this->school->id));
    }

    public function test_head_teacher_cannot_strip_bursar_role(): void
    {
        $head = User::where('email', 'head@standrews.test')->firstOrFail();
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();

        $this->actingAsInSchool($head)
            ->from(route('app.staff.index'))
            ->put(route('app.staff.roles', $bursar), [
                'role_keys' => ['subject_teacher'],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('role_keys');

        $this->assertTrue($bursar->fresh()->hasRoleInSchool(Role::BURSAR, $this->school->id));
        $this->assertFalse($bursar->fresh()->hasRoleInSchool(Role::SUBJECT_TEACHER, $this->school->id));
    }

    public function test_teacher_navigation_does_not_include_finance_or_sms(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $this->actingAsInSchool($teacher);

        $labels = collect(app(NavigationBuilder::class)->build($teacher)['sections'])
            ->flatMap(fn ($section) => collect($section['items'])->pluck('label'))
            ->all();

        $this->assertContains('My Teaching', $labels);
        $this->assertNotContains('SMS', $labels);
        $this->assertNotContains('Fees', $labels);
        $this->assertNotContains('Staff', $labels);
        $this->assertNotContains('Assessment periods', $labels);
    }

    public function test_bursar_navigation_is_finance_not_academics(): void
    {
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();
        $this->actingAsInSchool($bursar);

        $labels = collect(app(NavigationBuilder::class)->build($bursar)['sections'])
            ->flatMap(fn ($section) => collect($section['items'])->pluck('label'))
            ->all();

        $this->assertContains('Fees', $labels);
        $this->assertContains('SMS', $labels);
        $this->assertNotContains('Assessment', $labels);
        $this->assertNotContains('My Teaching', $labels);
        $this->assertNotContains('Students', $labels);
    }
}
