<?php

namespace App\Http\Controllers;

use App\Services\Schools\SchoolModuleRegistry;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolSettingsController extends Controller
{
    public function edit(TenantContext $ctx, SchoolModuleRegistry $modules)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        return view('app.settings.school', [
            'school' => $school,
            'themes' => config('themes.themes', []),
            'schoolPayCallbackUrl' => route('webhooks.schoolpay.callback', $school),
            'schoolPayNotifyUrl' => route('webhooks.schoolpay.notify', $school),
            'moduleCatalog' => config('modules.optional', []),
            'moduleSnapshot' => $modules->snapshot($school),
            'reportSettings' => $school->report_settings ?? [
                'show_position' => true,
                'show_total' => true,
                'show_average' => true,
                'require_class_teacher_comment' => false,
            ],
        ]);
    }

    public function update(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'name' => 'required|string|max:160',
            'motto' => 'nullable|string|max:200',
            'badge_text' => 'nullable|string|max:12',
            'address' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:120',
            'emis_number' => 'nullable|string|max:60',
            'theme' => 'required|string|in:'.implode(',', array_keys(config('themes.themes', []))),
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
            'emis_enabled' => 'sometimes|boolean',
            'schoolpay_enabled' => 'sometimes|boolean',
            'schoolpay_school_code' => 'nullable|string|max:32',
            'schoolpay_api_password' => 'nullable|string|max:200',
            'modules' => 'nullable|array',
            'modules.*' => 'sometimes|boolean',
            'report_show_position' => 'sometimes|boolean',
            'report_show_total' => 'sometimes|boolean',
            'report_show_average' => 'sometimes|boolean',
            'report_require_class_teacher_comment' => 'sometimes|boolean',
        ]);

        if (! empty($data['remove_logo']) && $school->logo_path) {
            Storage::disk('public')->delete($school->logo_path);
            $school->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($school->logo_path) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $school->logo_path = $request->file('logo')->store('school-logos/'.$school->id, 'public');
        }

        $school->fill([
            'name' => $data['name'],
            'motto' => $data['motto'] ?? null,
            'badge_text' => $data['badge_text'] ?? null,
            'address' => $data['address'] ?? null,
            'district' => $data['district'] ?? null,
            'emis_number' => $data['emis_number'] ?? null,
            'theme' => $data['theme'],
            'emis_enabled' => (bool) ($data['emis_enabled'] ?? false),
            'schoolpay_enabled' => (bool) ($data['schoolpay_enabled'] ?? false),
            'schoolpay_school_code' => filled($data['schoolpay_school_code'] ?? null)
                ? trim((string) $data['schoolpay_school_code'])
                : null,
            'report_settings' => [
                'show_position' => (bool) ($data['report_show_position'] ?? false),
                'show_total' => (bool) ($data['report_show_total'] ?? false),
                'show_average' => (bool) ($data['report_show_average'] ?? false),
                'require_class_teacher_comment' => (bool) ($data['report_require_class_teacher_comment'] ?? false),
            ],
        ]);

        $optional = array_keys(config('modules.optional', []));
        $modules = is_array($school->enabled_modules) ? $school->enabled_modules : [];
        foreach ($optional as $key) {
            if (in_array($key, ['emis', 'schoolpay'], true)) {
                continue;
            }
            $modules[$key] = (bool) ($data['modules'][$key] ?? false);
        }
        $school->enabled_modules = $modules;

        // Only overwrite the encrypted password when a new value is submitted.
        if (filled($data['schoolpay_api_password'] ?? null)) {
            $school->schoolpay_api_password = trim((string) $data['schoolpay_api_password']);
        }

        $school->save();

        return back()->with('status', 'School identity and optional features saved.');
    }
}
