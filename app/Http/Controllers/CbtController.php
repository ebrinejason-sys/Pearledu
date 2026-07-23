<?php
namespace App\Http\Controllers;
use App\Models\CbtAttempt;
use App\Models\CbtAttemptAnswer;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CbtController extends Controller {
    public function index(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $perms = $request->user()->permissionsForSchool($school->id);
        $canManage = in_array('cbt.manage', $perms, true);

        if ($canManage) {
            $exams = CbtExam::where('school_id',$school->id)->withCount('questions')->orderByDesc('id')->get();
            $subjects = Subject::where('school_id',$school->id)->orderBy('name')->get();
            $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
            return view('app.cbt.index', compact('school','exams','subjects','classes','canManage'));
        }

        $student = $this->resolveStudent($request, $school->id);
        $exams = CbtExam::where('school_id',$school->id)->where('is_published', true)
            ->withCount('questions')->orderByDesc('id')->get();
        $attempts = CbtAttempt::where('school_id',$school->id)->where('student_id',$student->id)
            ->get()->keyBy('exam_id');
        return view('app.cbt.take_list', compact('school','exams','attempts','student'));
    }

    public function storeExam(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['title'=>'required|string|max:160','duration_minutes'=>'nullable|integer|min:5','subject_id'=>'nullable|integer','class_id'=>'nullable|integer']);
        CbtExam::create($data + ['school_id'=>$school->id,'duration_minutes'=>$data['duration_minutes'] ?? 30]);
        return back()->with('status','Exam created.');
    }

    public function storeQuestion(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate([
            'exam_id'=>'required|integer','prompt'=>'required|string',
            'choice_a'=>'required|string','choice_b'=>'required|string','choice_c'=>'nullable|string','choice_d'=>'nullable|string',
            'correct_key'=>'required|in:a,b,c,d','points'=>'nullable|numeric|min:0',
        ]);
        $choices = array_filter(['a'=>$data['choice_a'],'b'=>$data['choice_b'],'c'=>$data['choice_c'] ?? null,'d'=>$data['choice_d'] ?? null]);
        CbtQuestion::create([
            'school_id'=>$school->id,'exam_id'=>$data['exam_id'],'prompt'=>$data['prompt'],
            'choices'=>$choices,'correct_key'=>$data['correct_key'],'points'=>$data['points'] ?? 1,
        ]);
        return back()->with('status','Question added.');
    }

    public function publish(CbtExam $exam, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$exam->school_id === (int)$school->id, 404);
        $exam->update(['is_published' => ! $exam->is_published]);
        return back()->with('status', $exam->is_published ? 'Exam published.' : 'Exam unpublished.');
    }

    public function start(CbtExam $exam, Request $request, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$exam->school_id === (int)$school->id, 404);
        abort_unless($exam->is_published, 403, 'Exam is not published.');
        $student = $this->resolveStudent($request, $school->id);

        $attempt = CbtAttempt::firstOrCreate(
            ['exam_id'=>$exam->id,'student_id'=>$student->id],
            [
                'school_id'=>$school->id,
                'user_id'=>$request->user()->id,
                'started_at'=>now(),
                'status'=>'in_progress',
            ]
        );
        abort_if($attempt->status === 'submitted', 422, 'You already submitted this exam.');

        return redirect()->route('app.cbt.attempts.take', $attempt);
    }

    public function take(CbtAttempt $attempt, Request $request, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$attempt->school_id === (int)$school->id, 404);
        $student = $this->resolveStudent($request, $school->id);
        abort_unless((int)$attempt->student_id === (int)$student->id, 403);
        abort_if($attempt->status === 'submitted', 422, 'Already submitted.');

        $attempt->load(['exam.questions']);
        return view('app.cbt.take', compact('school','attempt'));
    }

    public function submit(CbtAttempt $attempt, Request $request, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$attempt->school_id === (int)$school->id, 404);
        $student = $this->resolveStudent($request, $school->id);
        abort_unless((int)$attempt->student_id === (int)$student->id, 403);
        abort_if($attempt->status === 'submitted', 422, 'Already submitted.');

        $attempt->load('exam.questions');
        $data = $request->validate([
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|in:a,b,c,d',
        ]);
        $chosen = $data['answers'] ?? [];

        DB::transaction(function () use ($attempt, $school, $chosen) {
            $score = 0; $max = 0;
            foreach ($attempt->exam->questions as $q) {
                $key = isset($chosen[$q->id]) ? strtolower((string)$chosen[$q->id]) : null;
                $correct = $key !== null && strtolower((string)$q->correct_key) === $key;
                $pts = $correct ? (float)$q->points : 0;
                $max += (float)$q->points;
                $score += $pts;
                CbtAttemptAnswer::updateOrCreate(
                    ['attempt_id'=>$attempt->id,'question_id'=>$q->id],
                    [
                        'school_id'=>$school->id,
                        'chosen_key'=>$key,
                        'is_correct'=>$correct,
                        'points_awarded'=>$pts,
                    ]
                );
            }
            $attempt->update([
                'score'=>$score,'max_score'=>$max,
                'submitted_at'=>now(),'status'=>'submitted',
            ]);
        });

        return redirect()->route('app.cbt.attempts.result', $attempt)->with('status','Exam submitted.');
    }

    public function result(CbtAttempt $attempt, Request $request, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$attempt->school_id === (int)$school->id, 404);
        $student = $this->resolveStudent($request, $school->id);
        abort_unless((int)$attempt->student_id === (int)$student->id, 403);
        abort_unless($attempt->status === 'submitted', 404);

        $attempt->load(['exam','answers.question']);
        return view('app.cbt.result', compact('school','attempt'));
    }

    private function resolveStudent(Request $request, int $schoolId): Student {
        $student = Student::where('school_id',$schoolId)->where('user_id',$request->user()->id)->first();
        abort_unless($student, 403, 'No student profile linked to this account.');
        return $student;
    }
}
