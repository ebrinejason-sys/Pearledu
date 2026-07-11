<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Mail\GuardianInvitationMail;
use App\Models\Guardianship;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentRecordsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $this->withoutMiddleware(ResolveTenant::class);
    }

    private function actingAsAdmin(): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($this->admin);
    }

    public function test_admin_can_list_and_search_students(): void
    {
        app(TenantContext::class)->forSchool($this->school->id);
        Student::factory()->create(['full_name' => 'Zainab Searchable', 'emis_number' => 'EMIS9001']);
        Student::factory()->create(['full_name' => 'Other Learner', 'emis_number' => 'EMIS9002']);

        $response = $this->actingAsAdmin()->get(route('app.students.index', ['q' => 'Zainab']));

        $response->assertOk();
        $response->assertSee('Zainab Searchable');
        $response->assertDontSee('Other Learner');
    }

    public function test_admin_can_create_view_update_and_soft_delete_student(): void
    {
        $create = $this->actingAsAdmin()->post(route('app.students.store'), [
            'full_name' => 'New Learner',
            'emis_number' => 'EMIS7777',
            'status' => 'active',
            'class_id' => '',
            'lin' => '',
            'nin' => '',
        ]);

        $create->assertRedirect();
        $student = Student::where('emis_number', 'EMIS7777')->firstOrFail();
        $this->assertSame($this->school->id, $student->school_id);
        $this->assertNull($student->user_id);

        $this->actingAsAdmin()->get(route('app.students.show', $student))->assertOk()->assertSee('New Learner');

        $this->actingAsAdmin()->put(route('app.students.update', $student), [
            'full_name' => 'Renamed Learner',
            'emis_number' => 'EMIS7777',
            'status' => 'inactive',
            'class_id' => '',
            'lin' => '',
            'nin' => '',
        ])->assertRedirect(route('app.students.show', $student));

        $this->assertSame('Renamed Learner', $student->fresh()->full_name);
        $this->assertSame('inactive', $student->fresh()->status);

        $this->actingAsAdmin()->delete(route('app.students.destroy', $student))
            ->assertRedirect(route('app.students.index'));

        $this->assertSoftDeleted($student);
    }

    public function test_parent_cannot_manage_students(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $this->actingAs($parent)->get(route('app.students.index'))->assertForbidden();
    }

    public function test_admin_can_attach_and_detach_existing_guardian(): void
    {
        app(TenantContext::class)->forSchool($this->school->id);
        $student = Student::factory()->create(['full_name' => 'Child One']);
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();

        $this->actingAsAdmin()->post(route('app.students.guardians.store', $student), [
            'mode' => 'attach',
            'email' => $parent->email,
            'relationship' => 'mother',
            'is_primary' => '1',
        ])->assertRedirect();

        $link = Guardianship::where('student_id', $student->id)->where('guardian_user_id', $parent->id)->firstOrFail();
        $this->assertTrue($link->is_primary);
        $this->assertSame('mother', $link->relationship);

        $this->actingAsAdmin()->delete(route('app.students.guardians.destroy', [$student, $link]))
            ->assertRedirect();

        $this->assertDatabaseMissing('guardianships', ['id' => $link->id]);
        $this->assertDatabaseHas('users', ['id' => $parent->id, 'email' => $parent->email]);
    }

    public function test_admin_can_invite_new_guardian_and_set_primary(): void
    {
        Mail::fake();
        app(TenantContext::class)->forSchool($this->school->id);
        $student = Student::factory()->create(['full_name' => 'Child Two']);
        $existing = User::where('email', 'parent@standrews.test')->firstOrFail();

        $this->actingAsAdmin()->post(route('app.students.guardians.store', $student), [
            'mode' => 'attach',
            'email' => $existing->email,
            'relationship' => 'mother',
            'is_primary' => '1',
        ])->assertRedirect();

        $this->actingAsAdmin()->post(route('app.students.guardians.store', $student), [
            'mode' => 'invite',
            'full_name' => 'Uncle Guardian',
            'email' => 'uncle@newguardian.test',
            'phone' => '',
            'relationship' => 'uncle',
        ])->assertRedirect();

        Mail::assertSent(GuardianInvitationMail::class, fn ($mail) => $mail->hasTo('uncle@newguardian.test'));

        $invited = User::where('email', 'uncle@newguardian.test')->firstOrFail();
        $this->assertSame('invited', $invited->status);
        $this->assertTrue($invited->hasRoleInSchool('parent', $this->school->id));

        $primary = Guardianship::where('student_id', $student->id)->where('guardian_user_id', $existing->id)->firstOrFail();
        $secondary = Guardianship::where('student_id', $student->id)->where('guardian_user_id', $invited->id)->firstOrFail();

        $this->actingAsAdmin()->put(route('app.students.guardians.primary', [$student, $secondary]))
            ->assertRedirect();

        $this->assertFalse($primary->fresh()->is_primary);
        $this->assertTrue($secondary->fresh()->is_primary);
    }

    public function test_inviting_an_existing_active_users_email_is_rejected(): void
    {
        Mail::fake();
        app(TenantContext::class)->forSchool($this->school->id);
        $student = Student::factory()->create(['full_name' => 'Child Three']);

        // An active account that already exists but has no relationship to this
        // school at all — simulates a teacher/admin elsewhere, or any other real,
        // already-active user whose email an admin here happens to type in.
        $outsider = User::create([
            'full_name' => 'Outsider Person',
            'email' => 'outsider@elsewhere.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
            'status' => 'active',
        ]);

        $response = $this->actingAsAdmin()->post(route('app.students.guardians.store', $student), [
            'mode' => 'invite',
            'full_name' => 'Outsider',
            'email' => $outsider->email,
            'phone' => '',
            'relationship' => 'uncle',
        ]);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();
        $this->assertFalse($outsider->fresh()->hasRoleInSchool('parent', $this->school->id));
        $this->assertDatabaseMissing('guardianships', [
            'student_id' => $student->id,
            'guardian_user_id' => $outsider->id,
        ]);
    }

    public function test_cannot_view_another_schools_student(): void
    {
        $ctx = app(TenantContext::class);
        $ctx->forPlatform();

        $other = School::create(['name' => 'Other School', 'slug' => 'other-school', 'status' => 'active']);
        $ctx->forSchool($other->id);
        $foreign = Student::factory()->create(['full_name' => 'Foreign Student', 'school_id' => $other->id]);

        $this->actingAsAdmin()->get(route('app.students.show', $foreign))->assertNotFound();
    }

    public function test_home_shows_students_quick_action_for_admin(): void
    {
        $response = $this->actingAsAdmin()->get(route('app.home'));

        $response->assertOk();
        $response->assertSee('Learners');
        $response->assertSee('Students');
    }
}
