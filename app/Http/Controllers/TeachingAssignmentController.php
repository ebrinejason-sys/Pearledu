<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\TeachingLoadService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeachingAssignmentController extends Controller
{
    /** Roles that may receive a teaching assignment. */
    private const TEACHING_CAPABLE = Role::TEACHING_CAPABLE;

    public function index(TenantContext $context, TeachingLoadService $load)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $assignments = TeachingAssignment::query()
            ->with(['teacher', 'subject', 'schoolClass', 'academicYear', 'term'])
            ->orderByDesc('id')
            ->get();

        $teachers = User::query()
            ->whereIn(
                'id',
                RoleAssignment::query()
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->whereHas('role', fn ($q) => $q->whereIn('key', self::TEACHING_CAPABLE))
                    ->pluck('user_id'),
            )
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $subjects = Subject::query()->orderBy('name')->get();
        $classes = SchoolClass::query()->orderBy('name')->orderBy('stream')->get();
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $terms = Term::query()->orderBy('sequence')->get();
        $currentYearId = $years->firstWhere('is_current')?->id ?? $years->first()?->id;
        $occupancy = $load->occupancy($school, $currentYearId ? (int) $currentYearId : null);
        $maxTeacherPeriods = max(1, ...array_map(
            fn (array $card) => (int) $card['total_periods'],
            $occupancy['teacherCards'] !== [] ? $occupancy['teacherCards'] : [['total_periods' => 1]],
        ));

        return view('app.teaching.index', compact(
            'school', 'assignments', 'teachers', 'subjects', 'classes', 'years', 'terms', 'currentYearId', 'occupancy', 'maxTeacherPeriods'
        ));
    }

    public function store(Request $request, TenantContext $context, TeachingLoadService $load)
    {
        $school = $context->school();
        abort_unless($school, 404);

        if ($request->exists('teaching_assignments')) {
            return $this->storeLoad($request, $school, $load);
        }

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'class_id' => 'required|integer|exists:school_classes,id',
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $school->id),
            ],
            'term_id' => [
                'nullable',
                'integer',
                Rule::exists('terms', 'id')->where('school_id', $school->id),
            ],
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'periods_per_week' => 'nullable|integer|min:1|max:20',
        ]);

        $this->assertTeachingCapable((int) $data['user_id'], $school->id);

        if (! empty($data['term_id'])) {
            $term = Term::query()->findOrFail($data['term_id']);
            if ((int) $term->academic_year_id !== (int) $data['academic_year_id']) {
                throw ValidationException::withMessages([
                    'term_id' => 'Selected term must belong to the academic year.',
                ]);
            }
        }

        $duplicate = TeachingAssignment::query()
            ->where('school_id', $school->id)
            ->where('user_id', $data['user_id'])
            ->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->when(
                ! empty($data['term_id']),
                fn ($q) => $q->where('term_id', $data['term_id']),
                fn ($q) => $q->whereNull('term_id'),
            )
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'user_id' => 'This teacher already has that class and subject for the selected period.',
            ]);
        }

        TeachingAssignment::create([
            'school_id' => $school->id,
            'user_id' => $data['user_id'],
            'subject_id' => $data['subject_id'],
            'class_id' => $data['class_id'],
            'academic_year_id' => $data['academic_year_id'],
            'term_id' => $data['term_id'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'status' => 'active',
            'periods_per_week' => (int) ($data['periods_per_week'] ?? 3),
        ]);

        return back()->with('status', 'Teaching assignment saved.');
    }

    public function destroy(TeachingAssignment $assignment, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && $assignment->school_id === $school->id, 404);
        $assignment->delete();

        return back()->with('status', 'Teaching assignment removed.');
    }

    private function storeLoad(Request $request, School $school, TeachingLoadService $load): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $school->id),
            ],
            'term_id' => [
                'nullable',
                'integer',
                Rule::exists('terms', 'id')->where('school_id', $school->id),
            ],
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'teaching_assignments' => 'required|array|min:1',
            'teaching_assignments.*.subject_id' => 'nullable|integer',
            'teaching_assignments.*.class_ids' => 'nullable|array',
            'teaching_assignments.*.class_ids.*' => 'integer',
            'teaching_assignments.*.periods_per_week' => 'nullable|integer|min:1|max:20',
        ]);

        $this->assertTeachingCapable((int) $data['user_id'], $school->id);

        $teacher = User::query()->findOrFail((int) $data['user_id']);
        $count = $load->sync(
            $school,
            $teacher,
            $data['teaching_assignments'] ?? [],
            (int) $data['academic_year_id'],
            ! empty($data['term_id']) ? (int) $data['term_id'] : null,
            true,
            [
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
            ],
        );

        return back()->with('status', $count === 1
            ? 'Teaching assignment saved.'
            : $count.' teaching assignments saved.');
    }

    private function assertTeachingCapable(int $userId, int $schoolId): void
    {
        $ok = RoleAssignment::query()
            ->where('user_id', $userId)
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()))
            ->whereHas('role', fn ($q) => $q->whereIn('key', self::TEACHING_CAPABLE))
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'user_id' => 'Selected staff member does not have a teaching-capable role in this school.',
            ]);
        }
    }
}
