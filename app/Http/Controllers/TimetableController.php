<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetablePeriod;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use App\Services\Timetable\TimetableService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    public function index(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $classes = SchoolClass::query()->orderBy('name')->get();
        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);

        $slotsQuery = TimetableSlot::query()
            ->with(['period', 'schoolClass', 'subject', 'teacher', 'room'])
            ->orderBy('day_of_week')
            ->orderBy('period_id');

        if ($classId) {
            $slotsQuery->where('class_id', $classId);
        }

        $slots = $slotsQuery->get();
        $periods = TimetablePeriod::query()->orderBy('sequence')->get();
        $rooms = Room::query()->orderBy('name')->get();
        $subjects = Subject::query()->orderBy('name')->get();
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $teachers = User::query()
            ->whereHas('roleAssignments', fn ($q) => $q->where('school_id', $school->id)
                ->where('is_active', true)
                ->whereHas('role', fn ($r) => $r->whereIn('key', [
                    'class_teacher', 'subject_teacher', 'head_teacher', 'deputy_head_teacher', 'school_admin',
                ])))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot->day_of_week][$slot->period_id] = $slot;
        }

        return view('app.timetable.index', compact(
            'school', 'slots', 'periods', 'rooms', 'classes', 'subjects', 'years', 'teachers',
            'classId', 'days', 'grid'
        ));
    }

    public function storeSlot(Request $request, TenantContext $context, TimetableService $timetable)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'period_id' => 'nullable|integer|exists:timetable_periods,id',
            'class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'teacher_id' => 'required|integer|exists:users,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'period_name' => 'nullable|string|max:80|required_without:period_id',
            'starts_at' => 'nullable|date_format:H:i|required_with:period_name',
            'ends_at' => 'nullable|date_format:H:i|required_with:period_name',
            'room_name' => 'nullable|string|max:80',
        ]);

        if (empty($data['period_id'])) {
            $period = TimetablePeriod::create([
                'school_id' => $school->id,
                'name' => $data['period_name'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'sequence' => TimetablePeriod::query()->count() + 1,
            ]);
            $data['period_id'] = $period->id;
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
            ->with('status', 'Timetable slot added.');
    }

    public function destroySlot(TimetableSlot $slot, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && $slot->school_id === $school->id, 404);
        $classId = $slot->class_id;
        $slot->delete();

        return redirect()->route('app.timetable.index', ['class_id' => $classId])
            ->with('status', 'Slot removed.');
    }
}
