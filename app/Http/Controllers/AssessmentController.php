<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Assessment\MarksheetService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $periods = AssessmentPeriod::query()->with('term')->orderByDesc('id')->get();
        $terms = Term::query()->orderBy('sequence')->get();

        return view('app.assessment.index', compact('school', 'periods', 'terms'));
    }

    public function storePeriod(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'term_id' => 'nullable|integer|exists:terms,id',
            'max_score' => 'nullable|numeric|min:1|max:1000',
        ]);

        AssessmentPeriod::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'term_id' => $data['term_id'] ?? null,
            'max_score' => $data['max_score'] ?? 100,
        ]);

        return back()->with('status', 'Assessment period created.');
    }

    public function marks(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $periods = AssessmentPeriod::query()->orderByDesc('id')->get();
        $classes = SchoolClass::query()->orderBy('name')->get();
        $subjects = Subject::query()->orderBy('name')->get();

        $periodId = (int) $request->query('period_id', $periods->first()?->id ?? 0);
        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);
        $subjectId = (int) $request->query('subject_id', $subjects->first()?->id ?? 0);

        $students = $classId
            ? Student::query()->where('class_id', $classId)->orderBy('full_name')->get()
            : collect();

        $existing = Mark::query()
            ->where('assessment_period_id', $periodId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->get()
            ->keyBy('student_id');

        return view('app.assessment.marks', compact(
            'school', 'periods', 'classes', 'subjects',
            'periodId', 'classId', 'subjectId', 'students', 'existing'
        ));
    }

    public function storeMarks(Request $request, TenantContext $context, MarksheetService $marks)
    {
        $school = $context->school();
        abort_unless($school, 404);

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
            (int) $data['class_id'],
            $payload,
            $request->user()?->id,
        );

        return back()->with('status', 'Marks saved.');
    }

    public function broadsheet(Request $request, TenantContext $context, MarksheetService $marks)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $periods = AssessmentPeriod::query()->orderByDesc('id')->get();
        $classes = SchoolClass::query()->orderBy('name')->get();
        $periodId = (int) $request->query('period_id', $periods->first()?->id ?? 0);
        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);

        $matrix = ($periodId && $classId)
            ? $marks->broadsheet($periodId, $classId)
            : ['students' => collect(), 'subjects' => collect(), 'scores' => []];

        return view('app.assessment.broadsheet', compact('school', 'periods', 'classes', 'periodId', 'classId', 'matrix'));
    }

    public function reportCards(Request $request, TenantContext $context, MarksheetService $marks)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $periods = AssessmentPeriod::query()->orderByDesc('id')->get();
        $classes = SchoolClass::query()->orderBy('name')->get();
        $periodId = (int) $request->query('period_id', $periods->first()?->id ?? 0);
        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);

        $reports = ($periodId && $classId)
            ? $marks->reportCards($periodId, $classId)
            : [];
        $period = $periods->firstWhere('id', $periodId);
        $klass = $classes->firstWhere('id', $classId);

        return view('app.assessment.reports', compact(
            'school', 'periods', 'classes', 'periodId', 'classId', 'reports', 'period', 'klass'
        ));
    }
}
