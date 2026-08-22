<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskTicket;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\HelpdeskScope;
use App\Services\Portal\PortalService;
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
            ->when(! $canManage, fn ($q) => $q->where(function ($inner) use ($user) {
                $inner->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
            }))
            ->with('user')
            ->orderByDesc('id')
            ->get();

        return view('app.helpdesk.index', compact('school', 'tickets', 'canManage', 'canCreate'));
    }

    public function store(Request $request, TenantContext $ctx, PortalService $portal)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $user = $request->user();
        abort_unless($user && $this->scope->canCreate($user, $school->id), 403);

        $data = $request->validate([
            'subject' => 'required|string|max:160',
            'body' => 'required|string',
            'student_id' => 'nullable|integer',
            'category' => 'nullable|in:class_teacher,escalate',
        ]);

        $assignedTo = null;
        $category = $data['category'] ?? null;
        if ($category === 'class_teacher' || $this->isParentMessenger($user, (int) $school->id)) {
            $assignedTo = $this->homeroomTeacherId(
                $user,
                (int) $school->id,
                isset($data['student_id']) ? (int) $data['student_id'] : null,
                $portal,
            );
            $category = 'class_teacher';
        }

        HelpdeskTicket::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'assigned_to' => $assignedTo,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => 'open',
            'category' => $category,
        ]);

        return back()->with('status', $assignedTo
            ? 'Message sent to the class teacher.'
            : 'Ticket opened.');
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

    private function isParentMessenger(User $user, int $schoolId): bool
    {
        $perms = $user->permissionsForSchool($schoolId);

        return in_array('fees.pay', $perms, true) || in_array('child.results.view', $perms, true);
    }

    private function homeroomTeacherId(User $user, int $schoolId, ?int $studentId, PortalService $portal): ?int
    {
        $student = null;
        if ($studentId) {
            try {
                $student = $portal->resolveStudent($user, $studentId);
            } catch (\Throwable) {
                $student = null;
            }
        }
        if (! $student instanceof Student) {
            $student = $portal->learnersFor($user)->first();
        }
        if (! $student instanceof Student || ! $student->class_id) {
            return null;
        }

        $id = RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('class_id', $student->class_id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->value('user_id');

        return $id ? (int) $id : null;
    }
}
