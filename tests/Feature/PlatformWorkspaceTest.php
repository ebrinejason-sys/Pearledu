<?php
namespace Tests\Feature;
use App\Http\Middleware\ResolveTenant;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->operator = User::where('is_platform', true)->firstOrFail();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forPlatform();
    }

    public function test_students_require_entered_school(): void
    {
        $this->actingAs($this->operator)
            ->get(route('platform.students.index'))
            ->assertRedirect(route('platform.schools.index'));
    }

    public function test_enter_school_opens_workspace_and_allows_student_create(): void
    {
        $this->actingAs($this->operator)
            ->post(route('platform.schools.enter', $this->school))
            ->assertRedirect(route('platform.workspace'));

        $this->assertSame($this->school->id, session('platform.entered_school_id'));

        app(TenantContext::class)->forPlatformInSchool($this->school->id);

        $class = SchoolClass::query()->where('school_id', $this->school->id)->first();

        $response = $this->actingAs($this->operator)
            ->withSession(['platform.entered_school_id' => $this->school->id])
            ->post(route('platform.students.store'), [
                'full_name' => 'Platform Entered Learner',
                'emis_number' => 'PE-PLATFORM-1',
                'class_id' => $class?->id,
                'status' => 'active',
            ]);

        $student = Student::withoutGlobalScopes()
            ->where('emis_number', 'PE-PLATFORM-1')
            ->first();

        $this->assertNotNull($student);
        $response->assertRedirect(route('platform.students.show', $student));
        $this->assertSame($this->school->id, $student->school_id);
    }

    public function test_invitations_index_is_reachable(): void
    {
        $this->actingAs($this->operator)
            ->get(route('platform.invitations.index'))
            ->assertOk()
            ->assertSee('Invitations');
    }
}
