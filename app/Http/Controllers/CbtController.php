<?php
namespace App\Http\Controllers;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class CbtController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $exams = CbtExam::where('school_id',$school->id)->orderByDesc('id')->get();
        $subjects = Subject::where('school_id',$school->id)->orderBy('name')->get();
        $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
        return view('app.cbt.index', compact('school','exams','subjects','classes'));
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
}
