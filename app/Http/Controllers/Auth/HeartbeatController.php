<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\IdleSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Extends the idle window after confirmed user activity in the browser. */
class HeartbeatController extends Controller
{
    public function store(Request $request, IdleSessionService $idle): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $idle->touch($user, true);

        return response()->json([
            'ok' => true,
            'lifetime_seconds' => $idle->lifetimeMinutes() * 60,
            'warning_seconds' => $idle->warningMinutes() * 60,
            'remaining_seconds' => $idle->remainingSeconds($user),
        ]);
    }
}
