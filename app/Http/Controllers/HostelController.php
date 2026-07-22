<?php
namespace App\Http\Controllers;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class HostelController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $rooms = HostelRoom::where('school_id',$school->id)->orderBy('name')->get();
        $allocations = HostelAllocation::where('school_id',$school->id)->orderByDesc('id')->get();
        $students = Student::where('school_id',$school->id)->orderBy('full_name')->get();
        return view('app.hostel.index', compact('school','rooms','allocations','students'));
    }
    public function storeRoom(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['name'=>'required|string|max:80','capacity'=>'nullable|integer|min:1']);
        HostelRoom::create($data + ['school_id'=>$school->id,'capacity'=>$data['capacity'] ?? 4]);
        return back()->with('status','Room added.');
    }
    public function storeAllocation(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['room_id'=>'required|integer','student_id'=>'required|integer','starts_on'=>'nullable|date']);
        HostelAllocation::create($data + ['school_id'=>$school->id,'starts_on'=>$data['starts_on'] ?? now()->toDateString()]);
        return back()->with('status','Student allocated.');
    }
}
