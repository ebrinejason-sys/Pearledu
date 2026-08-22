<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\EnteredSchoolGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceSettingsController extends Controller
{
    public function __construct(private EnteredSchoolGuard $entered) {}

    public function edit(Request $request): View
    {
        $school = $this->entered->school($request);

        return view('platform.workspace.settings', [
            'school' => $school,
            'schoolPayCallbackUrl' => route('webhooks.schoolpay.callback', $school),
            'schoolPayNotifyUrl' => route('webhooks.schoolpay.notify', $school),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $school = $this->entered->school($request);

        $data = $request->validate([
            'emis_number' => 'nullable|string|max:60',
            'emis_enabled' => 'sometimes|boolean',
            'schoolpay_enabled' => 'sometimes|boolean',
            'schoolpay_school_code' => 'nullable|string|max:32',
            'schoolpay_api_password' => 'nullable|string|max:200',
        ]);

        $school->fill([
            'emis_number' => filled($data['emis_number'] ?? null) ? trim((string) $data['emis_number']) : null,
            'emis_enabled' => (bool) ($data['emis_enabled'] ?? false),
            'schoolpay_enabled' => (bool) ($data['schoolpay_enabled'] ?? false),
            'schoolpay_school_code' => filled($data['schoolpay_school_code'] ?? null)
                ? trim((string) $data['schoolpay_school_code'])
                : null,
        ]);

        if (filled($data['schoolpay_api_password'] ?? null)) {
            $school->schoolpay_api_password = trim((string) $data['schoolpay_api_password']);
        }

        $school->save();

        $audit->record('school.workspace.integrations', $school, [
            'emis_enabled' => $school->emis_enabled,
            'schoolpay_enabled' => $school->schoolpay_enabled,
        ], $request->user());

        return back()->with('status', 'EMIS and SchoolPay settings saved for '.$school->name.'.');
    }
}
