<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\Platform\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function store(Request $request, School $school, User $user, ImpersonationService $impersonation): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:500'],
            'ticket_id' => ['nullable', 'integer', 'min:1'],
            'elevated_write' => ['sometimes', 'boolean'],
        ]);

        $impersonation->start($request->user(), $user, $school, [
            'reason' => $data['reason'],
            'ticket_id' => $data['ticket_id'] ?? null,
            'elevated_write' => (bool) ($data['elevated_write'] ?? false),
        ]);

        $mode = ! empty($data['elevated_write']) ? 'elevated write' : 'read-only';

        return redirect()
            ->route('app.home')
            ->with('status', "Now viewing as {$user->full_name} at {$school->name} ({$mode}).");
    }
}
