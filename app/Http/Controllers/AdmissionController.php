<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\SchoolClass;
use App\Services\Learners\StudentLifecycleService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function index(TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $applications = AdmissionApplication::where('school_id', $school->id)
            ->with(['requestedClass', 'student'])
            ->orderByDesc('id')
            ->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('name')->get();

        return view('app.admissions.index', compact('school', 'applications', 'classes'));
    }

    public function store(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'applicant_name' => 'required|string|max:120',
            'guardian_name' => 'nullable|string|max:120',
            'guardian_email' => 'nullable|email',
            'guardian_phone' => 'nullable|string|max:30',
            'requested_class_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);
        AdmissionApplication::create($data + ['school_id' => $school->id, 'status' => 'pending']);

        return back()->with('status', 'Application recorded.');
    }

    public function decide(Request $request, AdmissionApplication $application, TenantContext $ctx, StudentLifecycleService $lifecycle)
    {
        abort_unless($ctx->schoolId() === $application->school_id, 404);
        $data = $request->validate([
            'decision' => 'required|in:accepted,rejected,enrolled',
            'class_id' => 'nullable|integer',
        ]);

        if ($data['decision'] !== 'enrolled') {
            $application->update(['status' => $data['decision']]);

            return back()->with('status', 'Application updated.');
        }

        $result = $lifecycle->admitFromApplication(
            $application,
            isset($data['class_id']) ? (int) $data['class_id'] : null,
            $request->user()?->id,
        );

        $message = 'Admitted '.$result['student']->full_name.'.';
        if ($result['invoices']['created'] > 0) {
            $message .= ' '.$result['invoices']['created'].' fee invoice(s) created.';
        }
        if ($result['warnings'] !== []) {
            $message .= ' '.implode(' ', $result['warnings']);
        }

        return back()->with('status', $message);
    }
}
