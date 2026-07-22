<?php
namespace App\Http\Controllers;
use App\Models\TransportRoute;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class TransportController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $routes = TransportRoute::where('school_id',$school->id)->orderBy('name')->get();
        return view('app.transport.index', compact('school','routes'));
    }
    public function storeRoute(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['name'=>'required|string|max:120','vehicle'=>'nullable|string|max:80','fee'=>'nullable|numeric|min:0']);
        TransportRoute::create($data + ['school_id'=>$school->id]);
        return back()->with('status','Route saved.');
    }
}
