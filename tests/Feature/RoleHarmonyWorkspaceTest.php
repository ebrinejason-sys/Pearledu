<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\HelpdeskTicket;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StaffConversation;
use App\Models\Student;
use App\Models\User;
use App\Services\Dashboard\RoleWorkspaceService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleHarmonyWorkspaceTest extends TestCase
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

    private function bindHomeroom(): SchoolClass
    {
        $stella = Student::query()
            ->where('school_id', $this->school->id)
            ->where('full_name', 'Stella Student')
            ->first();
        $class = $stella?->class_id
            ? SchoolClass::query()->findOrFail($stella->class_id)
            : (SchoolClass::query()->where('school_id', $this->school->id)->where('code', 'P5A')->first()
                ?? SchoolClass::query()->where('school_id', $this->school->id)->firstOrFail());
        $teacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $roleId = Role::query()->where('key', Role::CLASS_TEACHER)->value('id');
        RoleAssignment::query()
            ->where('user_id', $teacher->id)
            ->where('school_id', $this->school->id)
            ->where('role_id', $roleId)
            ->update(['class_id' => $class->id]);

        return $class;
    }

    public function test_school_admin_home_is_hygiene_not_mark_entry(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $this->actingAsInSchool($admin)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('Setup &amp; data hygiene', false)
            ->assertSee('Keep the engine running', false)
            ->assertDontSee('Enter scores — grades are calculated for you.', false);
    }

    public function test_director_home_is_governance_without_mutate_chrome(): void
    {
        $director = User::where('email', 'director@standrews.test')->firstOrFail();

        $this->actingAsInSchool($director)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('School pulse', false)
            ->assertSee('Set the destination', false)
            ->assertDontSee('Enter marks', false)
            ->assertDontSee('Take register', false);

        $this->actingAsInSchool($director)->post(route('app.fees.payments.store'), [
            'invoice_id' => 1,
            'amount' => 10,
            'method' => 'cash',
        ])->assertForbidden();
    }

    public function test_head_home_is_approvals_and_cannot_enter_marks(): void
    {
        $head = User::where('email', 'head@standrews.test')->firstOrFail();

        $this->actingAsInSchool($head)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('Approvals', false)
            ->assertSee('Commit promotions', false);

        $this->actingAsInSchool($head)->get(route('app.assessment.marks'))->assertForbidden();
    }

    public function test_deputy_home_is_logistics_without_promotions(): void
    {
        $deputy = User::where('email', 'deputy@standrews.test')->firstOrFail();

        $this->actingAsInSchool($deputy)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('Today’s board', false)
            ->assertSee('Absence heatmap', false)
            ->assertDontSee('Commit promotions', false);
    }

    public function test_dos_home_is_academic_os_and_cannot_open_fees(): void
    {
        $dos = User::where('email', 'dos@standrews.test')->firstOrFail();

        $this->actingAsInSchool($dos)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('Academic OS', false)
            ->assertSee('what is taught is measured correctly', false);

        $this->actingAsInSchool($dos)->get(route('app.fees.index'))->assertForbidden();
    }

    public function test_class_teacher_home_is_homeroom_without_mark_entry(): void
    {
        $this->bindHomeroom();
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('Take register', false)
            ->assertSee('Fees cleared', false)
            ->assertDontSee('Enter marks', false);

        $this->actingAsInSchool($classTeacher)->get(route('app.assessment.marks'))->assertForbidden();
    }

    public function test_teacher_home_is_my_classes_workspace(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $board = app(RoleWorkspaceService::class)->build(
            $this->school,
            $teacher,
            $teacher->permissionsForSchool($this->school->id),
        );

        $this->assertSame('teacher', $board['primary']);

        $this->actingAsInSchool($teacher)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('My classes', false)
            ->assertSee('Deliver content and assess mastery', false);
    }

    public function test_parent_portal_messages_class_teacher_via_helpdesk(): void
    {
        $this->bindHomeroom();
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();

        $this->actingAsInSchool($parent)
            ->get(route('app.portal.home'))
            ->assertOk()
            ->assertSee('Message class teacher', false)
            ->assertDontSee('Simon Subject', false);

        $this->actingAsInSchool($parent)
            ->post(route('app.helpdesk.store'), [
                'subject' => 'About Stella',
                'body' => 'Please call me about homework.',
                'category' => 'class_teacher',
            ])
            ->assertRedirect();

        $ticket = HelpdeskTicket::query()->where('subject', 'About Stella')->firstOrFail();
        $this->assertSame((int) $classTeacher->id, (int) $ticket->assigned_to);
        $this->assertSame('class_teacher', $ticket->category);

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.helpdesk.index'))
            ->assertOk()
            ->assertSee('About Stella', false);

        $other = HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => User::where('email', 'teacher@standrews.test')->firstOrFail()->id,
            'subject' => 'Teacher private ticket',
            'body' => 'Not for the class teacher',
            'status' => 'open',
        ]);

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.helpdesk.index'))
            ->assertDontSee('Teacher private ticket', false);

        $this->actingAsInSchool($classTeacher)
            ->post(route('app.helpdesk.close', $other))
            ->assertForbidden();
    }

    public function test_student_portal_has_no_pay_and_no_classmates(): void
    {
        $student = User::where('email', 'student@standrews.test')->firstOrFail();

        $this->actingAsInSchool($student)
            ->get(route('app.portal.home'))
            ->assertOk()
            ->assertSee('Your timetable, results, and statement.', false)
            ->assertDontSee('>Pay</a>', false)
            ->assertDontSee('Message class teacher', false);

        $this->assertNotContains('fees.pay', $student->permissionsForSchool($this->school->id));
    }

    public function test_director_does_not_receive_operations_lead(): void
    {
        $director = User::where('email', 'director@standrews.test')->firstOrFail();
        $board = app(RoleWorkspaceService::class)->build(
            $this->school,
            $director,
            $director->permissionsForSchool($this->school->id),
        );

        $this->assertSame('governance', $board['primary']);
        $this->assertNull($board['operationsLead']);
    }

    public function test_teacher_flags_concern_to_class_teacher_not_a_new_product(): void
    {
        $class = $this->bindHomeroom();
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();

        $this->actingAsInSchool($teacher)
            ->post(route('app.staff.messages.store'), [
                'intent' => 'concern',
                'class_id' => $class->id,
                'body' => 'Stella is struggling in English.',
            ])
            ->assertRedirect();

        $conversation = StaffConversation::query()
            ->where('school_id', $this->school->id)
            ->where('subject', 'Learner concern')
            ->where('created_by', $teacher->id)
            ->firstOrFail();

        $this->assertTrue($conversation->participants()->where('user_id', $classTeacher->id)->exists());
        $this->assertTrue($conversation->participants()->where('user_id', $teacher->id)->exists());
    }

    public function test_class_teacher_escalates_to_deputy_via_staff_messages(): void
    {
        $this->bindHomeroom();
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $deputy = User::where('email', 'deputy@standrews.test')->firstOrFail();

        $this->actingAsInSchool($classTeacher)
            ->post(route('app.staff.messages.store'), [
                'intent' => 'escalate',
                'role_key' => Role::DEPUTY_HEAD_TEACHER,
                'body' => 'Need cover for tomorrow morning.',
            ])
            ->assertRedirect();

        $conversation = StaffConversation::query()
            ->where('school_id', $this->school->id)
            ->where('subject', 'Escalation to Deputy Head')
            ->where('created_by', $classTeacher->id)
            ->firstOrFail();

        $this->assertTrue($conversation->participants()->where('user_id', $deputy->id)->exists());
    }
}
