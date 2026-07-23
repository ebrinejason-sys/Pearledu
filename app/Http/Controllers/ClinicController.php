<?php
namespace App\Http\Controllers;
use App\Models\ClinicVisit;
use App\Models\Student;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class ClinicController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $visits = ClinicVisit::where('school_id',$school->id)->orderByDesc('id')->limit(100)->get();
        $students = Student::where('school_id',$school->id)->orderBy('full_name')->get();
        return view('app.clinic.index', compact('school','visits','students'));
    }
    public function storeVisit(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['student_id'=>'required|integer','complaint'=>'nullable|string|max:255','notes'=>'nullable|string']);
        ClinicVisit::create($data + ['school_id'=>$school->id,'visited_at'=>now(),'recorded_by'=>$request->user()->id]);
        return back()->with('status','Visit recorded.');
    }
}
