<?php

namespace App\Http\Controllers;

use App\Models\LmsAssignment;
use App\Models\LmsMaterial;
use App\Models\LmsSubmission;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Authorization\LmsScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LmsController extends Controller
{
    public function __construct(private LmsScope $scope) {}

    public function index(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $user = $request->user();
        $perms = $user->permissionsForSchool($school->id);
        $canManage = in_array('lms.manage', $perms, true);

        if ($canManage) {
            $classIds = $this->scope->writableClassIds($user, $school->id);
            $materials = LmsMaterial::where('school_id', $school->id)
                ->when(is_array($classIds), fn ($q) => $q->where(function ($inner) use ($classIds) {
                    $inner->whereNull('class_id')->orWhereIn('class_id', $classIds ?: [0]);
                }))
                ->with(['subject', 'schoolClass'])->orderByDesc('id')->get();
            $assignments = LmsAssignment::where('school_id', $school->id)
                ->when(is_array($classIds), fn ($q) => $q->where(function ($inner) use ($classIds) {
                    $inner->whereNull('class_id')->orWhereIn('class_id', $classIds ?: [0]);
                }))
                ->with(['subject', 'schoolClass'])->orderByDesc('id')->get();
            $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
            $classes = SchoolClass::where('school_id', $school->id)
                ->when(is_array($classIds), fn ($q) => $q->whereIn('id', $classIds))
                ->orderBy('name')->get();
            $submissions = LmsSubmission::where('school_id', $school->id)
                ->whereIn('assignment_id', $assignments->pluck('id')->all() ?: [0])
                ->with(['assignment', 'student'])->orderByDesc('id')->limit(100)->get();

            return view('app.lms.index', compact('school', 'materials', 'assignments', 'subjects', 'classes', 'submissions', 'canManage'));
        }

        $student = $this->resolveStudent($request, $school->id);
        $materials = LmsMaterial::where('school_id', $school->id)
            ->where(fn ($q) => $q->whereNull('class_id')->orWhere('class_id', $student->class_id))
            ->with(['subject', 'schoolClass'])
            ->orderByDesc('id')
            ->get();
        $assignments = LmsAssignment::where('school_id', $school->id)
            ->where(fn ($q) => $q->whereNull('class_id')->orWhere('class_id', $student->class_id))
            ->with(['subject', 'schoolClass'])
            ->orderByDesc('id')
            ->get();
        $mySubmissions = LmsSubmission::where('school_id', $school->id)->where('student_id', $student->id)
            ->get()->keyBy('assignment_id');

        return view('app.lms.browse', compact('school', 'materials', 'assignments', 'student', 'mySubmissions'));
    }

    public function storeMaterial(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'body' => 'nullable|string',
            'url' => 'nullable|url',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'class_id' => 'nullable|integer|exists:school_classes,id',
        ]);
        abort_unless($this->scope->canWrite(
            $request->user(),
            $school->id,
            isset($data['class_id']) ? (int) $data['class_id'] : null,
            isset($data['subject_id']) ? (int) $data['subject_id'] : null,
        ), 403, 'You can only post materials for your assigned class and subject.');
        LmsMaterial::create($data + ['school_id' => $school->id, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Material posted.');
    }

    public function storeAssignment(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'instructions' => 'nullable|string',
            'due_at' => 'nullable|date',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'class_id' => 'nullable|integer|exists:school_classes,id',
        ]);
        abort_unless($this->scope->canWrite(
            $request->user(),
            $school->id,
            isset($data['class_id']) ? (int) $data['class_id'] : null,
            isset($data['subject_id']) ? (int) $data['subject_id'] : null,
        ), 403, 'You can only create assignments for your assigned class and subject.');
        LmsAssignment::create($data + ['school_id' => $school->id, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Assignment created.');
    }

    public function submit(LmsAssignment $assignment, Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $assignment->school_id === (int) $school->id, 404);
        $student = $this->resolveStudent($request, $school->id);
        $this->assertAssignmentVisibleToStudent($assignment, $student);

        if ($assignment->due_at && now()->greaterThan($assignment->due_at)) {
            throw ValidationException::withMessages([
                'body' => 'This assignment is past its due date and can no longer be submitted.',
            ]);
        }

        $data = $request->validate([
            'body' => 'nullable|string|max:5000',
            'url' => 'nullable|url|max:500',
        ]);
        abort_unless(($data['body'] ?? null) || ($data['url'] ?? null), 422, 'Provide text or a URL.');

        LmsSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'school_id' => $school->id,
                'user_id' => $request->user()->id,
                'body' => $data['body'] ?? null,
                'url' => $data['url'] ?? null,
                'submitted_at' => now(),
                'score' => null,
                'feedback' => null,
                'graded_at' => null,
                'graded_by' => null,
            ]
        );

        return back()->with('status', 'Assignment submitted.');
    }

    public function grade(LmsSubmission $submission, Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $submission->school_id === (int) $school->id, 404);
        $submission->loadMissing('assignment');
        abort_unless($this->scope->canWrite(
            $request->user(),
            $school->id,
            $submission->assignment?->class_id ? (int) $submission->assignment->class_id : null,
            $submission->assignment?->subject_id ? (int) $submission->assignment->subject_id : null,
        ), 403, 'You can only grade submissions for your assigned class and subject.');
        $data = $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);
        $submission->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => now(),
            'graded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Submission graded.');
    }

    private function resolveStudent(Request $request, int $schoolId): Student
    {
        $student = Student::where('school_id', $schoolId)->where('user_id', $request->user()->id)->first();
        abort_unless($student, 403, 'No student profile linked to this account.');

        return $student;
    }

    private function assertAssignmentVisibleToStudent(LmsAssignment $assignment, Student $student): void
    {
        if ($assignment->class_id !== null && (int) $assignment->class_id !== (int) $student->class_id) {
            abort(403, 'This assignment is not for your class.');
        }
    }
}
