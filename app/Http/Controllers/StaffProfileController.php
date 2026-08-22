<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\StaffDocument;
use App\Models\StaffSalary;
use App\Models\StaffSalaryPayment;
use App\Models\StaffTimePunch;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Authorization\InvitePolicy;
use App\Services\Hr\StaffBadgeService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StaffProfileController extends Controller
{
    public function show(Request $request, User $user, TenantContext $ctx, StaffBadgeService $badges, InvitePolicy $hierarchy): View
    {
        $school = $ctx->school();
        $actor = $request->user();
        abort_unless($school && $actor instanceof User, 404);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        $perms = $actor->permissionsForSchool($school->id);
        $roles = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['role', 'schoolClass'])
            ->get();

        $assignments = TeachingAssignment::query()
            ->with(['subject', 'schoolClass'])
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->orderBy('subject_id')
            ->get();

        return view('app.staff.show', [
            'school' => $school,
            'staff' => $user,
            'roles' => $roles,
            'assignments' => $assignments,
            'badge' => $badges->issue($school, $user),
            'documents' => StaffDocument::query()
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get(),
            'punches' => in_array('staff.attendance.view', $perms, true)
                ? StaffTimePunch::query()->where('school_id', $school->id)->where('user_id', $user->id)->orderByDesc('punched_at')->limit(20)->get()
                : collect(),
            'salary' => in_array('hr.payroll.view', $perms, true)
                ? StaffSalary::query()->where('school_id', $school->id)->where('user_id', $user->id)->first()
                : null,
            'payments' => in_array('hr.payroll.view', $perms, true)
                ? StaffSalaryPayment::query()->where('school_id', $school->id)->where('user_id', $user->id)->orderByDesc('paid_on')->limit(20)->get()
                : collect(),
            'canPrintId' => in_array('staff.id.print', $perms, true),
            'canViewPayroll' => in_array('hr.payroll.view', $perms, true),
            'canManagePayroll' => in_array('hr.payroll.manage', $perms, true),
            'canEditProfile' => $hierarchy->canEditStaffProfile($actor, $user, $school->id),
            'canAdminister' => in_array('staff.manage', $perms, true) && $hierarchy->canAdminister($actor, $user, $school->id),
        ]);
    }

    public function update(Request $request, User $user, TenantContext $ctx, InvitePolicy $hierarchy): RedirectResponse
    {
        $school = $ctx->school();
        abort_unless($school && $request->user() instanceof User, 404);
        abort_unless($hierarchy->canEditStaffProfile($request->user(), $user, $school->id), 403);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'nin' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'home_address' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $user->full_name = $data['full_name'];
        $user->phone = $data['phone'] ?? null;
        $user->nin = filled($data['nin'] ?? null) ? strtoupper((string) $data['nin']) : $user->nin;
        $user->date_of_birth = $data['date_of_birth'] ?? null;
        $user->nationality = $data['nationality'] ?? null;
        $user->home_address = $data['home_address'] ?? null;

        if ($request->hasFile('photo')) {
            if (filled($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $photo = $request->file('photo');
            abort_unless($photo !== null, 422);
            $user->avatar_path = $photo->store('staff-photos', 'public');
        }

        $user->save();

        return back()->with('status', 'Staff profile saved.');
    }

    public function storeDocument(Request $request, User $user, TenantContext $ctx, InvitePolicy $hierarchy): RedirectResponse
    {
        $school = $ctx->school();
        abort_unless($school && $request->user() instanceof User, 404);
        abort_unless($hierarchy->canEditStaffProfile($request->user(), $user, $school->id), 403);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $file = $request->file('file');
        abort_unless($file !== null, 422);
        $path = $file->store('staff-documents', 'public');

        StaffDocument::query()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return back()->with('status', 'Document saved on this staff file.');
    }

    public function destroyDocument(User $user, StaffDocument $document, TenantContext $ctx, InvitePolicy $hierarchy): RedirectResponse
    {
        $school = $ctx->school();
        $actor = request()->user();
        abort_unless($school && $actor instanceof User, 404);
        abort_unless($hierarchy->canEditStaffProfile($actor, $user, $school->id), 403);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        if ((int) $document->user_id !== (int) $user->id || (int) $document->school_id !== (int) $school->id) {
            abort(404);
        }

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return back()->with('status', 'Document removed.');
    }

    private function isSchoolStaff(int $schoolId, User $user): bool
    {
        return RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
            ->exists();
    }
}
