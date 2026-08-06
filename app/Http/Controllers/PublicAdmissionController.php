<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\SchoolClass;
use App\Services\Security\TurnstileVerifier;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Public apply form on a school tenant host (no login). */
class PublicAdmissionController extends Controller
{
    public function create(TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        return view('public.apply', [
            'school' => $school,
            'classes' => SchoolClass::where('school_id', $school->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, TenantContext $ctx, TurnstileVerifier $turnstile)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'applicant_name' => 'required|string|max:120',
            'guardian_name' => 'nullable|string|max:120',
            'guardian_email' => 'nullable|email',
            'guardian_phone' => 'nullable|string|max:30',
            'requested_class_id' => [
                'nullable',
                'integer',
                Rule::exists('school_classes', 'id')->where(fn ($q) => $q->where('school_id', $school->id)),
            ],
            'notes' => 'nullable|string',
            'website' => 'nullable|max:0', // honeypot: must be empty
        ]);
        $turnstile->assertValid($request);

        unset($data['website']);

        AdmissionApplication::create($data + [
            'school_id' => $school->id,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Application submitted. The school will contact you.');
    }
}
