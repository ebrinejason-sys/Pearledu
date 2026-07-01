<?php
namespace App\Http\Controllers;
use App\Services\Platform\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function destroy(ImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->stop();

        return redirect()
            ->route('platform.dashboard')
            ->with('status', 'Imitation ended. You are signed in as yourself again.');
    }
}
