<?php
namespace App\Http\Controllers;
use App\Models\LeaveRequest;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class HrController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $leaves = LeaveRequest::where('school_id',$school->id)->with('user')->orderByDesc('id')->get();
        return view('app.hr.index', compact('school','leaves'));
    }
    public function storeLeave(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['starts_on'=>'required|date','ends_on'=>'required|date|after_or_equal:starts_on','reason'=>'nullable|string|max:255']);
        LeaveRequest::create($data + ['school_id'=>$school->id,'user_id'=>$request->user()->id,'status'=>'pending']);
        return back()->with('status','Leave request submitted.');
    }

    public function decideLeave(Request $request, LeaveRequest $leave, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school && (int)$leave->school_id === (int)$school->id, 404);
        $data = $request->validate(['decision' => 'required|in:approved,rejected']);
        abort_unless($leave->status === 'pending', 422, 'Leave already decided.');
        $leave->update(['status' => $data['decision']]);
        return back()->with('status', 'Leave '.$data['decision'].'.');
    }
}
