<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TimetablePeriod;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use App\Services\Timetable\TimetableGenerator;
use App\Services\Timetable\TimetableService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    public function index(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $classes = SchoolClass::query()->orderBy('name')->orderBy('stream')->get();
        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);

        $slotsQuery = TimetableSlot::query()
            ->with(['period', 'schoolClass', 'subject', 'teacher', 'room'])
            ->orderBy('day_of_week')
            ->orderBy('period_id');

        if ($classId) {
            $slotsQuery->where('class_id', $classId);
        }

        $slots = $slotsQuery->get();
        $periods = TimetablePeriod::query()->orderBy('sequence')->orderBy('starts_at')->get();
        $rooms = Room::query()->orderBy('name')->get();
        $subjects = Subject::query()->orderBy('name')->get();
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $currentYearId = $years->firstWhere('is_current')?->id ?? $years->first()?->id;

        $teachers = User::query()
            ->whereHas('roleAssignments', fn ($q) => $q->where('school_id', $school->id)
                ->where('is_active', true)
                ->whereHas('role', fn ($r) => $r->whereIn('key', [
                    'class_teacher', 'subject_teacher', 'head_teacher', 'deputy_head_teacher', 'school_admin',
                ])))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $assignments = TeachingAssignment::query()
            ->with(['teacher', 'schoolClass', 'subject'])
            ->effective()
            ->when($currentYearId, fn ($q) => $q->where('academic_year_id', $currentYearId))
            ->orderBy('id')
            ->get();

        $teachingDayNums = $school->teachingDays();
        $allDays = School::WEEK_DAYS;
        $days = [];
        foreach ($teachingDayNums as $num) {
            if (isset($allDays[$num])) {
                $days[$num] = $allDays[$num];
            }
        }
        if ($days === []) {
            $days = array_intersect_key($allDays, array_flip([1, 2, 3, 4, 5]));
        }

        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot->day_of_week][$slot->period_id] = $slot;
        }

        return view('app.timetable.index', [
            'school' => $school,
            'slots' => $slots,
            'periods' => $periods,
            'periodKinds' => TimetablePeriod::KINDS,
            'rooms' => $rooms,
            'classes' => $classes,
            'subjects' => $subjects,
            'years' => $years,
            'teachers' => $teachers,
            'classId' => $classId,
            'days' => $days,
            'allDays' => $allDays,
            'teachingDayNums' => $teachingDayNums,
            'grid' => $grid,
            'assignments' => $assignments,
            'currentYearId' => $currentYearId,
        ]);
    }

    public function updateScheduleDays(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'teaching_days' => ['required', 'array', 'min:1'],
            'teaching_days.*' => ['integer', Rule::in(array_keys(School::WEEK_DAYS))],
        ]);

        $selected = collect($data['teaching_days'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();
        $settings = $school->schedule_settings ?? [];
        $settings['teaching_days'] = $selected;
        $school->forceFill(['schedule_settings' => $settings])->save();

        return back()->with('status', 'Teaching days saved. Lessons are only scheduled on these days.');
    }

    public function storePeriod(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'kind' => ['required', Rule::in(array_keys(TimetablePeriod::KINDS))],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'sequence' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);

        TimetablePeriod::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'kind' => $data['kind'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'sequence' => (int) ($data['sequence'] ?? ((int) TimetablePeriod::query()->max('sequence') + 1)),
        ]);

        return back()->with('status', 'Period block added to the daily schedule.');
    }

    public function updatePeriod(Request $request, TimetablePeriod $period, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && (int) $period->school_id === (int) $school->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'kind' => ['required', Rule::in(array_keys(TimetablePeriod::KINDS))],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'sequence' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);

        $period->update([
            'name' => $data['name'],
            'kind' => $data['kind'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'sequence' => (int) ($data['sequence'] ?? $period->sequence),
        ]);

        return back()->with('status', 'Period updated.');
    }

    public function destroyPeriod(TimetablePeriod $period, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && (int) $period->school_id === (int) $school->id, 404);

        if (TimetableSlot::query()->where('period_id', $period->id)->exists()) {
            return back()->withErrors(['period' => 'Remove lessons in this period before deleting it.']);
        }

        $period->delete();

        return back()->with('status', 'Period removed.');
    }

    public function storeSlot(Request $request, TenantContext $context, TimetableService $timetable)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'period_id' => 'nullable|integer|exists:timetable_periods,id',
            'teaching_assignment_id' => 'nullable|integer|exists:teaching_assignments,id',
            'class_id' => 'nullable|integer|exists:school_classes,id',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'teacher_id' => 'nullable|integer|exists:users,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'period_name' => 'nullable|string|max:80|required_without:period_id',
            'starts_at' => 'nullable|date_format:H:i|required_with:period_name',
            'ends_at' => 'nullable|date_format:H:i|required_with:period_name',
            'period_kind' => ['nullable', Rule::in(array_keys(TimetablePeriod::KINDS))],
            'room_name' => 'nullable|string|max:80',
        ]);

        if (! empty($data['teaching_assignment_id'])) {
            $assignment = TeachingAssignment::query()->findOrFail((int) $data['teaching_assignment_id']);
            abort_unless((int) $assignment->school_id === (int) $school->id, 404);
            $data['teacher_id'] = $assignment->user_id;
            $data['class_id'] = $assignment->class_id;
            $data['subject_id'] = $assignment->subject_id;
            $data['academic_year_id'] = $data['academic_year_id'] ?? $assignment->academic_year_id;
        }

        if (empty($data['class_id']) || empty($data['subject_id']) || empty($data['teacher_id'])) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => 'Pick a teaching assignment, or choose class, subject, and teacher.',
            ]);
        }

        if (! in_array((int) $data['day_of_week'], $school->teachingDays(), true)) {
            throw ValidationException::withMessages([
                'day_of_week' => 'That day is not marked as a teaching day.',
            ]);
        }

        if (empty($data['period_id'])) {
            $period = TimetablePeriod::create([
                'school_id' => $school->id,
                'name' => $data['period_name'],
                'kind' => $data['period_kind'] ?? 'class',
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'sequence' => (int) TimetablePeriod::query()->max('sequence') + 1,
            ]);
            $data['period_id'] = $period->id;
        }

        $period = TimetablePeriod::query()->findOrFail((int) $data['period_id']);
        if (! $period->isLessonPeriod()) {
            throw ValidationException::withMessages([
                'period_id' => 'Lessons can only go in class periods — not breakfast, break, lunch, or sports blocks.',
            ]);
        }

        if (! empty($data['room_name'])) {
            $room = Room::firstOrCreate(
                ['school_id' => $school->id, 'name' => $data['room_name']],
                ['capacity' => null],
            );
            $data['room_id'] = $room->id;
        }

        try {
            $timetable->storeSlot([
                'school_id' => $school->id,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'day_of_week' => (int) $data['day_of_week'],
                'period_id' => (int) $data['period_id'],
                'class_id' => (int) $data['class_id'],
                'subject_id' => (int) $data['subject_id'],
                'teacher_id' => (int) $data['teacher_id'],
                'room_id' => $data['room_id'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('app.timetable.index', ['class_id' => $data['class_id']])
            ->with('status', 'Lesson placed on the timetable.');
    }

    public function generate(Request $request, TenantContext $context, TimetableGenerator $generator)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $generator->generate(
                $school,
                isset($data['class_id']) ? (int) $data['class_id'] : null,
                isset($data['academic_year_id']) ? (int) $data['academic_year_id'] : null,
                (bool) ($data['replace_existing'] ?? false),
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $message = sprintf(
            'Generated %d lesson(s). Collisions skipped: %d. Incomplete loads: %d.',
            $result['created'],
            $result['skipped'],
            count($result['unplaced'])
        );

        $redirect = redirect()->route('app.timetable.index', array_filter([
            'class_id' => $data['class_id'] ?? null,
        ]))->with('status', $message);

        if ($result['unplaced'] !== []) {
            return $redirect->withErrors([
                'generate' => implode(' · ', array_slice($result['unplaced'], 0, 5)),
            ]);
        }

        return $redirect;
    }

    public function destroySlot(TimetableSlot $slot, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && $slot->school_id === $school->id, 404);
        $classId = $slot->class_id;
        $slot->delete();

        return redirect()->route('app.timetable.index', ['class_id' => $classId])
            ->with('status', 'Lesson removed.');
    }
}
