<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskTicket;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
    ) {}

    public function index(Request $request)
    {
        $this->context->forPlatform();

        $filter = $request->string('filter', 'open')->toString();
        $query = HelpdeskTicket::query()->with(['user', 'school', 'assignee'])->orderByDesc('id');

        match ($filter) {
            'closed' => $query->where('status', 'closed'),
            'mine' => $query->where('assigned_to', $request->user()->id)->where('status', '!=', 'closed'),
            'unassigned' => $query->whereNull('assigned_to')->where('status', '!=', 'closed'),
            'all' => null,
            default => $query->where('status', '!=', 'closed'),
        };

        $tickets = $query->paginate(30)->withQueryString();
        $agents = User::query()
            ->where('is_platform', true)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email']);

        $counts = [
            'open' => HelpdeskTicket::where('status', '!=', 'closed')->count(),
            'unassigned' => HelpdeskTicket::whereNull('assigned_to')->where('status', '!=', 'closed')->count(),
            'mine' => HelpdeskTicket::where('assigned_to', $request->user()->id)->where('status', '!=', 'closed')->count(),
            'closed' => HelpdeskTicket::where('status', 'closed')->count(),
        ];

        return view('platform.support.index', compact('tickets', 'agents', 'filter', 'counts'));
    }

    public function show(HelpdeskTicket $ticket)
    {
        $this->context->forPlatform();
        $ticket->load(['user', 'school', 'assignee']);
        $agents = User::query()
            ->where('is_platform', true)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email']);

        return view('platform.support.show', compact('ticket', 'agents'));
    }

    public function update(Request $request, HelpdeskTicket $ticket)
    {
        abort_unless(
            $request->user()->hasPlatformPermission('platform.support.manage'),
            403
        );
        $this->context->forPlatform();

        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category' => ['nullable', 'string', 'max:40'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_platform', true)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['status'] === 'closed' && $ticket->status !== 'closed') {
            $data['resolved_at'] = now();
        }
        if ($data['status'] !== 'closed') {
            $data['resolved_at'] = null;
        }

        $ticket->update($data);
        $this->audit->record('support.ticket.updated', $ticket, $data);

        return back()->with('status', 'Ticket updated.');
    }

    public function assign(Request $request, HelpdeskTicket $ticket)
    {
        abort_unless(
            $request->user()->hasPlatformPermission('platform.support.manage'),
            403
        );
        $this->context->forPlatform();

        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_platform', true)],
        ]);

        $ticket->update([
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => $ticket->status === 'closed' ? 'closed' : ($ticket->status === 'open' ? 'in_progress' : $ticket->status),
        ]);

        $this->audit->record('support.ticket.assigned', $ticket, $data);

        return back()->with('status', 'Assignment saved.');
    }
}
