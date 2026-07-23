<?php
namespace App\Http\Controllers;
use App\Models\HelpdeskTicket;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class HelpdeskController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $tickets = HelpdeskTicket::where('school_id',$school->id)->with('user')->orderByDesc('id')->get();
        return view('app.helpdesk.index', compact('school','tickets'));
    }
    public function store(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['subject'=>'required|string|max:160','body'=>'required|string']);
        HelpdeskTicket::create($data + ['school_id'=>$school->id,'user_id'=>$request->user()->id,'status'=>'open']);
        return back()->with('status','Ticket opened.');
    }

    public function close(HelpdeskTicket $ticket, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school && (int)$ticket->school_id === (int)$school->id, 404);
        abort_if($ticket->status === 'closed', 422, 'Already closed.');
        $ticket->update(['status' => 'closed']);
        return back()->with('status', 'Ticket closed.');
    }
}
