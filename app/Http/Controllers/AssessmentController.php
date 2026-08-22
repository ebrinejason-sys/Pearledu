<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Academics\CurrentAcademicContext;
use App\Services\Assessment\AssessmentPeriodWorkflow;
use App\Services\Assessment\MarksheetService;
use App\Services\Assessment\MarksheetWorkflow;
use App\Services\Authorization\AssessmentScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssessmentController extends Controller
{
    public function __construct(
        private AssessmentScope $scope,
        private AssessmentPeriodWorkflow $workflow,
        private CurrentAcademicContext $academic,
        private MarksheetWorkflow $marksheets,
    ) {}

    public function index(TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $user = request()->user();
        abort_unless($user && $this->scope->canManage($user, $school->id), 403);

        $periods = AssessmentPeriod::query()->with('term')->orderByDesc('id')->get();
        $terms = Term::query()->orderBy('sequence')->get();
        $canManage = $this->scope->canManage($user, $school->id);
        $canEnter = $this->scope->canEnterAnywhere($user, $school->id);
        $periodActions = [];
        foreach ($periods as $period) {
            $periodActions[$period->id] = $this->workflow->nextActions($period);
        }

        return view('app.assessment.index', compact('school', 'periods', 'terms', 'canManage', 'canEnter', 'periodActions'));
    }

    public function storePeriod(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);
        abort_unless($request->user() && $this->scope->canManage($request->user(), $school->id), 403);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'term_id' => 'nullable|integer|exists:terms,id',
            'max_score' => 'nullable|numeric|min:1|max:1000',
        ]);

        AssessmentPeriod::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'term_id' => $data['term_id'] ?? $this->academic->term()?->id,
            'max_score' => $data['max_score'] ?? 100,
            'status' => 'draft',
        ]);

        return back()->with('status', 'Assessment period created as a draft. Open mark entry when teachers should begin.');
    }

    public function transitionPeriod(Request $request, AssessmentPeriod $period, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && (int) $period->school_id === (int) $school->id, 404);
        abort_unless($request->user() && $this->scope->canManage($request->user(), $school->id), 403);

        $data = $request->validate([
            'to' => 'required|in:'.implode(',', AssessmentPeriodWorkflow::STATUSES),
        ]);

        $this->workflow->advance($period, $data['to']);

        return back()->with('status', 'Period is now '.str_replace('_', ' ', $period->fresh()->status).'.');
    }

    public function marks(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canEnterAnywhere($user, $school->id), 403);

        $periods = AssessmentPeriod::query()->orderByDesc('id')->get();
        $classes = $this->scopedClasses($this->scope->enterableClassIds($user, $school->id));

        $periodId = (int) $request->query('period_id', $this->academic->assessmentPeriod()?->id ?? $periods->first()?->id ?? 0);
        $classId = (int) $request->query('class_id', $this->academic->classesFor($user)->first()?->id ?? $classes->first()?->id ?? 0);

        if ($classId && ! $classes->contains('id', $classId)) {
            abort(403);
        }

        $subjectIds = $classId
            ? $this->scope->enterableSubjectIds($user, $school->id, $classId)
            : [];
        $subjects = $this->scopedSubjects($subjectIds);

        $subjectId = (int) $request->query('subject_id', $subjects->first()?->id ?? 0);
        if ($subjectId && ! $subjects->contains('id', $subjectId)) {
            abort(403);
        }

        if ($classId && $subjectId) {
            abort_unless($this->scope->canEnter($user, $school->id, $classId, $subjectId), 403);
        }

        $students = $classId
            ? Student::query()
                ->where(function ($q) use ($classId) {
                    $q->where('class_id', $classId)
                        ->orWhereHas('enrollments', fn ($e) => $e->where('class_id', $classId)->where('status', 'active'));
                })
                ->orderBy('full_name')
                ->get()
            : collect();

        $existing = Mark::query()
            ->where('assessment_period_id', $periodId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->get()
            ->keyBy('student_id');

        $hasAssignments = $this->scope->canManage($user, $school->id) || $classes->isNotEmpty();
        $period = $periods->firstWhere('id', $periodId);
        $marksheet = ($period && $classId && $subjectId)
            ? $this->marksheets->find($school->id, (int) $period->id, $classId, $subjectId)
            : null;
        $canEnterMarks = $period ? $this->marksheets->canEditMarks($user, $period, $marksheet) : false;
        $perms = $user->permissionsForSchool($school->id);
        $canSubmitMarksheet = $period
            && $classId
            && $subjectId
            && $this->workflow->canEnterMarks($period)
            && in_array('marksheet.submit', $perms, true)
            && ($marksheet === null || $marksheet->status === 'draft');
        $canVerifyMarksheet = $marksheet?->status === 'submitted'
            && in_array('marksheet.verify', $perms, true);
        $canReturnMarksheet = $marksheet
            && in_array($marksheet->status, ['submitted', 'verified'], true)
            && $this->scope->canManage($user, $school->id);

        return view('app.assessment.marks', compact(
            'school', 'periods', 'classes', 'subjects',
            'periodId', 'classId', 'subjectId', 'students', 'existing', 'hasAssignments',
            'period', 'canEnterMarks', 'marksheet',
            'canSubmitMarksheet', 'canVerifyMarksheet', 'canReturnMarksheet'
        ));
    }

    public function storeMarks(Request $request, TenantContext $context, MarksheetService $marks)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canEnterAnywhere($user, $school->id), 403);

        $data = $request->validate([
            'period_id' => 'required|integer|exists:assessment_periods,id',
            'class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'rows' => 'required|array|min:1',
            'rows.*.student_id' => 'required|integer|exists:students,id',
            'rows.*.score' => 'nullable|numeric|min:0',
            'rows.*.grade' => 'nullable|string|max:10',
            'rows.*.comment' => 'nullable|string|max:500',
        ]);

        $classId = (int) $data['class_id'];
        $subjectId = (int) $data['subject_id'];

        abort_unless($this->scope->canEnter($user, $school->id, $classId, $subjectId), 403);

        $period = AssessmentPeriod::query()->findOrFail((int) $data['period_id']);
        abort_unless((int) $period->school_id === (int) $school->id, 404);
        if (! $this->workflow->canEnterMarks($period)) {
            throw ValidationException::withMessages([
                'period_id' => 'Mark entry is closed for this period. An administrator must reopen it.',
            ]);
        }
        $sheet = $this->marksheets->find($school->id, (int) $period->id, $classId, $subjectId);
        abort_unless($this->marksheets->canEditMarks($user, $period, $sheet), 403, 'This marksheet is locked. Submit, verify, or return it first.');

        $studentIds = collect($data['rows'])->pluck('student_id')->map(fn ($id) => (int) $id)->all();
        $validCount = Student::query()
            ->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)
                    ->orWhereHas('enrollments', fn ($e) => $e->where('class_id', $classId)->where('status', 'active'));
            })
            ->whereIn('id', $studentIds)
            ->count();

        if ($validCount !== count(array_unique($studentIds))) {
            throw ValidationException::withMessages([
                'rows' => 'Every student must belong to the selected class.',
            ]);
        }

        $payload = array_map(fn ($row) => [
            'student_id' => $row['student_id'],
            'subject_id' => $data['subject_id'],
            'score' => $row['score'] ?? null,
            'grade' => $row['grade'] ?? null,
            'comment' => $row['comment'] ?? null,
        ], $data['rows']);

        $marks->saveMarks(
            $school->id,
            (int) $data['period_id'],
            $classId,
            $payload,
            $user->id,
        );

        return back()->with('status', 'Marks saved.');
    }

    public function submitMarksheet(Request $request, TenantContext $context)
    {
        return $this->transitionMarksheet($request, $context, 'submit');
    }

    public function verifyMarksheet(Request $request, TenantContext $context)
    {
        return $this->transitionMarksheet($request, $context, 'verify');
    }

    public function returnMarksheet(Request $request, TenantContext $context)
    {
        return $this->transitionMarksheet($request, $context, 'return');
    }

    private function transitionMarksheet(Request $request, TenantContext $context, string $action)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'period_id' => 'required|integer|exists:assessment_periods,id',
            'class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
        ]);
        $period = AssessmentPeriod::query()->findOrFail((int) $data['period_id']);
        abort_unless((int) $period->school_id === (int) $school->id, 404);

        $sheet = match ($action) {
            'submit' => $this->marksheets->submit($request->user(), $period, (int) $data['class_id'], (int) $data['subject_id']),
            'verify' => $this->marksheets->verify($request->user(), $period, (int) $data['class_id'], (int) $data['subject_id']),
            default => $this->marksheets->returnToDraft($request->user(), $period, (int) $data['class_id'], (int) $data['subject_id']),
        };

        return back()->with('status', 'Marksheet is now '.$sheet->status.'.');
    }

    public function broadsheet(Request $request, TenantContext $context, MarksheetService $marks)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canViewAnywhere($user, $school->id), 403);

        $periods = AssessmentPeriod::query()->orderByDesc('id')->get();
        $classes = $this->scopedClasses($this->scope->viewableClassIds($user, $school->id));
        $periodId = (int) $request->query('period_id', $periods->first()?->id ?? 0);
        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);

        if ($classId && ! $this->scope->canViewClass($user, $school->id, $classId)) {
            abort(403);
        }

        $matrix = ($periodId && $classId)
            ? $marks->broadsheet($periodId, $classId)
            : ['students' => collect(), 'subjects' => collect(), 'scores' => []];

        return view('app.assessment.broadsheet', compact('school', 'periods', 'classes', 'periodId', 'classId', 'matrix'));
    }

    public function reportCards(Request $request, TenantContext $context, MarksheetService $marks)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canViewAnywhere($user, $school->id), 403);

        $periods = AssessmentPeriod::query()->orderByDesc('id')->get();
        $classes = $this->scopedClasses($this->scope->viewableClassIds($user, $school->id));
        $periodId = (int) $request->query('period_id', $periods->first()?->id ?? 0);
        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);

        if ($classId && ! $this->scope->canViewClass($user, $school->id, $classId)) {
            abort(403);
        }

        $reports = ($periodId && $classId)
            ? $marks->reportCards($periodId, $classId, $school->report_settings ?? [])
            : [];
        $period = $periods->firstWhere('id', $periodId);
        $klass = $classes->firstWhere('id', $classId);
        $reportSettings = $school->report_settings ?? [
            'show_position' => true,
            'show_total' => true,
            'show_average' => true,
        ];

        return view('app.assessment.reports', compact(
            'school', 'periods', 'classes', 'periodId', 'classId', 'reports', 'period', 'klass', 'reportSettings'
        ));
    }

    /**
     * @param  list<int>|null  $classIds
     * @return Collection<int, SchoolClass>
     */
    private function scopedClasses(?array $classIds)
    {
        $query = SchoolClass::query()->orderBy('name');

        if ($classIds !== null) {
            if ($classIds === []) {
                return SchoolClass::query()->whereRaw('0 = 1')->get();
            }
            $query->whereIn('id', $classIds);
        }

        return $query->get();
    }

    /**
     * @param  list<int>|null  $subjectIds
     * @return Collection<int, Subject>
     */
    private function scopedSubjects(?array $subjectIds)
    {
        $query = Subject::query()->orderBy('name');

        if ($subjectIds !== null) {
            if ($subjectIds === []) {
                return Subject::query()->whereRaw('0 = 1')->get();
            }
            $query->whereIn('id', $subjectIds);
        }

        return $query->get();
    }
}
