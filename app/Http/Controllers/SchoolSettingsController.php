<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolSettingsController extends Controller
{
    public function edit(TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        return view('app.settings.school', compact('school'));
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
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
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
        ])->save();

        return back()->with('status', 'School identity saved. Badge and logo will appear on report cards.');
    }
}
