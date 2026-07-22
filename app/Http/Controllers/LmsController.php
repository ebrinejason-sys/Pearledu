<?php
namespace App\Http\Controllers;
use App\Models\LmsAssignment;
use App\Models\LmsMaterial;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class LmsController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $materials = LmsMaterial::where('school_id',$school->id)->orderByDesc('id')->get();
        $assignments = LmsAssignment::where('school_id',$school->id)->orderByDesc('id')->get();
        $subjects = Subject::where('school_id',$school->id)->orderBy('name')->get();
        $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
        return view('app.lms.index', compact('school','materials','assignments','subjects','classes'));
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
}
