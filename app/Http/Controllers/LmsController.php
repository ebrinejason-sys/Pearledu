<?php
namespace App\Http\Controllers;
use App\Models\LmsAssignment;
use App\Models\LmsMaterial;
use App\Models\LmsSubmission;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class LmsController extends Controller {
    public function index(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $perms = $request->user()->permissionsForSchool($school->id);
        $canManage = in_array('lms.manage', $perms, true);

        $materials = LmsMaterial::where('school_id',$school->id)->with(['subject','schoolClass'])->orderByDesc('id')->get();
        $assignments = LmsAssignment::where('school_id',$school->id)->with(['subject','schoolClass'])->orderByDesc('id')->get();

        if ($canManage) {
            $subjects = Subject::where('school_id',$school->id)->orderBy('name')->get();
            $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
            $submissions = LmsSubmission::where('school_id',$school->id)
                ->with(['assignment','student'])->orderByDesc('id')->limit(100)->get();
            return view('app.lms.index', compact('school','materials','assignments','subjects','classes','submissions','canManage'));
        }

        $student = $this->resolveStudent($request, $school->id);
        $mySubmissions = LmsSubmission::where('school_id',$school->id)->where('student_id',$student->id)
            ->get()->keyBy('assignment_id');
        return view('app.lms.browse', compact('school','materials','assignments','student','mySubmissions'));
    }

    public function storeMaterial(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['title'=>'required|string|max:160','body'=>'nullable|string','url'=>'nullable|url','subject_id'=>'nullable|integer','class_id'=>'nullable|integer']);
        LmsMaterial::create($data + ['school_id'=>$school->id,'created_by'=>$request->user()->id]);
        return back()->with('status','Material posted.');
    }

    public function storeAssignment(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['title'=>'required|string|max:160','instructions'=>'nullable|string','due_at'=>'nullable|date','subject_id'=>'nullable|integer','class_id'=>'nullable|integer']);
        LmsAssignment::create($data + ['school_id'=>$school->id,'created_by'=>$request->user()->id]);
        return back()->with('status','Assignment created.');
    }

    public function submit(LmsAssignment $assignment, Request $request, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$assignment->school_id === (int)$school->id, 404);
        $student = $this->resolveStudent($request, $school->id);
        $data = $request->validate([
            'body'=>'nullable|string|max:5000',
            'url'=>'nullable|url|max:500',
        ]);
        abort_unless(($data['body'] ?? null) || ($data['url'] ?? null), 422, 'Provide text or a URL.');

        LmsSubmission::updateOrCreate(
            ['assignment_id'=>$assignment->id,'student_id'=>$student->id],
            [
                'school_id'=>$school->id,
                'user_id'=>$request->user()->id,
                'body'=>$data['body'] ?? null,
                'url'=>$data['url'] ?? null,
                'submitted_at'=>now(),
                'score'=>null,
                'feedback'=>null,
                'graded_at'=>null,
                'graded_by'=>null,
            ]
        );
        return back()->with('status','Assignment submitted.');
    }

    public function grade(LmsSubmission $submission, Request $request, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$submission->school_id === (int)$school->id, 404);
        $data = $request->validate([
            'score'=>'required|numeric|min:0|max:100',
            'feedback'=>'nullable|string|max:2000',
        ]);
        $submission->update([
            'score'=>$data['score'],
            'feedback'=>$data['feedback'] ?? null,
            'graded_at'=>now(),
            'graded_by'=>$request->user()->id,
        ]);
        return back()->with('status','Submission graded.');
    }

    private function resolveStudent(Request $request, int $schoolId): Student {
        $student = Student::where('school_id',$schoolId)->where('user_id',$request->user()->id)->first();
        abort_unless($student, 403, 'No student profile linked to this account.');
        return $student;
    }
}
