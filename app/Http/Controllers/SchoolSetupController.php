<?php

namespace App\Http\Controllers;

use App\Services\Schools\SchoolSetupService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class SchoolSetupController extends Controller
{
    public function index(TenantContext $ctx, SchoolSetupService $setup)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        return view('app.setup.index', [
            'school' => $school,
            'steps' => $setup->steps($school),
            'percent' => $setup->completionPercentage($school),
            'next' => $setup->nextStep($school),
            'missing' => $setup->missingRequirements($school),
        ]);
    }

    public function complete(Request $request, TenantContext $ctx, SchoolSetupService $setup)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        if ($setup->nextStep($school) && ! $request->boolean('force')) {
            return back()->withErrors(['setup' => 'Finish the remaining steps first, or confirm you want to skip.']);
        }

        $school->update(['setup_completed_at' => now()]);

        return redirect()->route('app.home')->with('status', 'Setup marked complete. You can reopen it anytime from More.');
    }
}
