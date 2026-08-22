<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Provisioning\WalkthroughSchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class WalkthroughSchoolController extends Controller
{
    public function create(WalkthroughSchoolService $walkthrough): View
    {
        return view('platform.schools.walkthrough', [
            'accounts' => $walkthrough->accountDirectory(),
            'school' => $walkthrough->existing(),
        ]);
    }

    public function store(Request $request, WalkthroughSchoolService $walkthrough, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'walkthrough_password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        try {
            $result = $walkthrough->seed(
                $data['walkthrough_password'],
                app()->isProduction(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['walkthrough_password' => $e->getMessage()]);
        }

        $audit->record('walkthrough.seeded', $result['school'], [
            'created' => $result['created'],
            'students' => $result['students'],
        ], $request->user());

        $lines = collect($result['accounts'])
            ->map(fn (array $row) => ($row['email'] ?? '').' — '.($row['name'] ?? ''))
            ->filter()
            ->take(8)
            ->implode(', ');

        return redirect()
            ->route('platform.schools.show', $result['school'])
            ->with('status', ($result['created'] ? 'Created' : 'Updated').' '.$result['school']->name
                .' with '.$result['students'].' learners. Sign in at /login with the password you just set. '
                .'Accounts include '.$lines.'…');
    }
}
