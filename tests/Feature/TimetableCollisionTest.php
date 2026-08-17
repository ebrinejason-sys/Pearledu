<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetablePeriod;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use App\Services\Timetable\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TimetableCollisionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private SchoolClass $classA;

    private SchoolClass $classB;

    private Subject $subject;

    private TimetablePeriod $period;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);

        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $this->teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $this->classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P5A', 'code' => 'TT-P5A-'.uniqid()]);
        $this->classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P5B', 'code' => 'TT-P5B-'.uniqid()]);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Math', 'code' => 'MTH-'.uniqid()]);
        $this->period = TimetablePeriod::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'kind' => 'class',
            'starts_at' => '08:00',
            'ends_at' => '08:40',
            'sequence' => 1,
        ]);
        $this->room = Room::create(['school_id' => $this->school->id, 'name' => 'Lab '.uniqid(), 'capacity' => 40]);
    }

    public function test_rejects_teacher_collision(): void
    {
        $svc = app(TimetableService::class);
        $base = [
            'school_id' => $this->school->id,
            'day_of_week' => 1,
            'period_id' => $this->period->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => null,
        ];

        $svc->storeSlot($base + ['class_id' => $this->classA->id]);

        $this->expectException(ValidationException::class);
        $svc->storeSlot($base + ['class_id' => $this->classB->id]);
    }

    public function test_rejects_class_collision(): void
    {
        $svc = app(TimetableService::class);
        $otherTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();

        $svc->storeSlot([
            'school_id' => $this->school->id,
            'day_of_week' => 2,
            'period_id' => $this->period->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => null,
        ]);

        $this->expectException(ValidationException::class);
        $svc->storeSlot([
            'school_id' => $this->school->id,
            'day_of_week' => 2,
            'period_id' => $this->period->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $otherTeacher->id,
            'room_id' => null,
        ]);
    }

    public function test_rejects_room_collision(): void
    {
        $svc = app(TimetableService::class);
        $otherTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();

        $svc->storeSlot([
            'school_id' => $this->school->id,
            'day_of_week' => 3,
            'period_id' => $this->period->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
        ]);

        $this->expectException(ValidationException::class);
        $svc->storeSlot([
            'school_id' => $this->school->id,
            'day_of_week' => 3,
            'period_id' => $this->period->id,
            'class_id' => $this->classB->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $otherTeacher->id,
            'room_id' => $this->room->id,
        ]);
    }
}
