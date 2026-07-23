<?php
namespace App\Http\Controllers;
use App\Models\AdmissionApplication;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class AdmissionController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $applications = AdmissionApplication::where('school_id',$school->id)->with('requestedClass')->orderByDesc('id')->get();
        $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
        return view('app.admissions.index', compact('school','applications','classes'));
    }
    public function store(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate([
            'applicant_name'=>'required|string|max:120','guardian_name'=>'nullable|string|max:120',
            'guardian_email'=>'nullable|email','guardian_phone'=>'nullable|string|max:30',
            'requested_class_id'=>'nullable|integer','notes'=>'nullable|string',
        ]);
        AdmissionApplication::create($data + ['school_id'=>$school->id,'status'=>'pending']);
        return back()->with('status','Application recorded.');
    }
    public function decide(Request $request, AdmissionApplication $application, TenantContext $ctx) {
        abort_unless($ctx->schoolId() === $application->school_id, 404);
        $data = $request->validate(['decision'=>'required|in:accepted,rejected,enrolled']);
        $application->update(['status'=>$data['decision']]);
        if ($data['decision'] === 'enrolled') {
            Student::create([
                'school_id'=>$application->school_id,
                'full_name'=>$application->applicant_name,
                'class_id'=>$application->requested_class_id,
                'status'=>'active',
            ]);
        }
        return back()->with('status','Application updated.');
    }
}
