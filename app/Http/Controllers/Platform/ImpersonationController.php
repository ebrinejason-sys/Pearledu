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
        $impersonation->start($request->user(), $user, $school);

        return redirect()
            ->route('app.home')
            ->with('status', "Now viewing as {$user->full_name} at {$school->name}.");
    }
}
