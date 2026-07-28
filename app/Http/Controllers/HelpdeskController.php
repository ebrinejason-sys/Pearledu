<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskTicket;
use App\Services\Authorization\HelpdeskScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class HelpdeskController extends Controller
{
    public function __construct(private HelpdeskScope $scope) {}

    public function index(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canAccessIndex($user, $school->id), 403);

        $canManage = $this->scope->canManage($user, $school->id);
        $canCreate = $this->scope->canCreate($user, $school->id);

        $tickets = HelpdeskTicket::query()
            ->where('school_id', $school->id)
            ->when(! $canManage, fn ($q) => $q->where('user_id', $user->id))
            ->with('user')
            ->orderByDesc('id')
            ->get();

        return view('app.helpdesk.index', compact('school', 'tickets', 'canManage', 'canCreate'));
    }

    public function store(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canCreate($user, $school->id), 403);

        $data = $request->validate([
            'subject' => 'required|string|max:160',
            'body' => 'required|string',
        ]);

        HelpdeskTicket::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => 'open',
        ]);

        return back()->with('status', 'Ticket opened.');
    }

    public function close(HelpdeskTicket $ticket, Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $ticket->school_id === (int) $school->id, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canClose($user, $school->id, $ticket), 403);
        abort_if($ticket->status === 'closed', 422, 'Already closed.');

        $ticket->update(['status' => 'closed']);

        return back()->with('status', 'Ticket closed.');
    }
}
