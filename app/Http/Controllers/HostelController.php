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
        $rooms = HostelRoom::where('school_id',$school->id)->withCount(['allocations' => fn ($q) => $q->whereNull('ends_on')])->orderBy('name')->get();
        $allocations = HostelAllocation::where('school_id',$school->id)->with(['room','student'])->orderByDesc('id')->get();
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
        $room = HostelRoom::where('school_id', $school->id)->findOrFail($data['room_id']);
        $active = HostelAllocation::where('room_id', $room->id)->whereNull('ends_on')->count();
        abort_if($active >= (int) $room->capacity, 422, 'Room is at capacity.');
        HostelAllocation::create($data + ['school_id'=>$school->id,'starts_on'=>$data['starts_on'] ?? now()->toDateString()]);
        return back()->with('status','Student allocated.');
    }

    public function vacate(HostelAllocation $allocation, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school && (int)$allocation->school_id === (int)$school->id, 404);
        abort_if($allocation->ends_on, 422, 'Already vacated.');
        $allocation->update(['ends_on' => now()->toDateString()]);
        return back()->with('status','Student vacated from room.');
    }
}
