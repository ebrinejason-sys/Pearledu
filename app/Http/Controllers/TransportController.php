<?php
namespace App\Http\Controllers;
use App\Models\Student;
use App\Models\TransportAllocation;
use App\Models\TransportRoute;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class TransportController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $routes = TransportRoute::where('school_id',$school->id)->withCount(['allocations' => fn ($q) => $q->whereNull('ends_on')])->orderBy('name')->get();
        $allocations = TransportAllocation::where('school_id',$school->id)->with(['route','student'])->orderByDesc('id')->get();
        $students = Student::where('school_id',$school->id)->orderBy('full_name')->get();
        return view('app.transport.index', compact('school','routes','allocations','students'));
    }

    public function storeRoute(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['name'=>'required|string|max:120','vehicle'=>'nullable|string|max:80','fee'=>'nullable|numeric|min:0']);
        TransportRoute::create($data + ['school_id'=>$school->id]);
        return back()->with('status','Route saved.');
    }

    public function storeAllocation(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate([
            'route_id' => 'required|integer',
            'student_id' => 'required|integer',
            'starts_on' => 'nullable|date',
        ]);
        $route = TransportRoute::where('school_id', $school->id)->findOrFail($data['route_id']);
        Student::where('school_id', $school->id)->findOrFail($data['student_id']);

        TransportAllocation::where('school_id', $school->id)
            ->where('student_id', $data['student_id'])
            ->whereNull('ends_on')
            ->update(['ends_on' => now()->toDateString()]);

        TransportAllocation::create([
            'school_id' => $school->id,
            'route_id' => $route->id,
            'student_id' => $data['student_id'],
            'starts_on' => $data['starts_on'] ?? now()->toDateString(),
        ]);

        return back()->with('status', 'Student assigned to transport route.');
    }

    public function endAllocation(TransportAllocation $allocation, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$allocation->school_id === (int)$school->id, 404);
        abort_if($allocation->ends_on, 422, 'Already ended.');
        $allocation->update(['ends_on' => now()->toDateString()]);
        return back()->with('status', 'Transport assignment ended.');
    }
}
