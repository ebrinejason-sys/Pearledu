<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, TenantContext $context)
    {
        $context->forPlatform();

        $data = $request->validate([
            'action' => ['nullable', 'string', 'max:120'],
            'school_id' => ['nullable', 'integer'],
            'actor_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = AuditLog::query()
            ->with(['actor:id,full_name,email', 'school:id,name'])
            ->orderByDesc('id');

        $query
            ->when($data['action'] ?? null, fn ($q, $value) => $q->where('action', $value))
            ->when($data['school_id'] ?? null, fn ($q, $value) => $q->where('school_id', $value))
            ->when($data['actor_id'] ?? null, fn ($q, $value) => $q->where('actor_id', $value))
            ->when($data['from'] ?? null, fn ($q, $value) => $q->where('created_at', '>=', $value.' 00:00:00'))
            ->when($data['to'] ?? null, fn ($q, $value) => $q->where('created_at', '<=', $value.' 23:59:59'));

        $logs = $query->paginate(50)->withQueryString();
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $schools = School::query()->orderBy('name')->get(['id', 'name']);
        $actors = User::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('actor_id')->select('actor_id'))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email']);

        return view('platform.audit.index', compact('logs', 'actions', 'schools', 'actors', 'data'));
    }
}
