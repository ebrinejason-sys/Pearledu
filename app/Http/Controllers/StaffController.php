<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\SchoolClass;
use App\Models\SchoolInvitation;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Authorization\InvitePolicy;
use App\Services\Hr\StaffBadgeService;
use App\Services\Hr\StaffPayrollService;
use App\Services\Provisioning\StaffInvitationService;
use App\Services\Provisioning\StaffRoleService;
use App\Services\Tenancy\TenantContext;
use App\Support\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StaffController extends Controller
{
    public function index(Request $request, TenantContext $context, InvitePolicy $policy)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $roleKeys = $policy->rolesInvitableBy($request->user(), $school->id, false);
        $roles = Role::query()->whereIn('key', $roleKeys)->orderBy('label')->get();
        $classes = SchoolClass::query()->where('school_id', $school->id)->orderBy('name')->orderBy('stream')->get();
        $subjects = Subject::query()->where('school_id', $school->id)->orderBy('name')->get();

        $grouped = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->with(['user', 'role', 'schoolClass'])
            ->get()
            ->groupBy('user_id');

        $members = collect();
        foreach ($grouped as $assignments) {
            $first = $assignments->first();
            if (! $first instanceof RoleAssignment || $first->user === null) {
                continue;
            }
            $memberRoles = [];
            $memberRoleKeys = [];
            $homeroomClassId = null;
            foreach ($assignments as $assignment) {
                if (! $assignment instanceof RoleAssignment) {
                    continue;
                }
                $class = $assignment->schoolClass;
                $memberRoles[] = [
                    'label' => $assignment->role?->label,
                    'class' => $class instanceof SchoolClass ? $class->displayName() : null,
                    'key' => $assignment->role?->key,
                ];
                if ($assignment->role?->key) {
                    $memberRoleKeys[] = $assignment->role->key;
                }
                if ($assignment->role?->key === 'class_teacher') {
                    $homeroomClassId = $assignment->class_id;
                }
            }
            $members->push([
                'user' => $first->user,
                'roles' => collect($memberRoles)->unique(fn ($r) => $r['label'].'|'.$r['class'])->values()->all(),
                'role_keys' => array_values(array_unique($memberRoleKeys)),
                'homeroom_class_id' => $homeroomClassId,
            ]);
        }
        $members = $members->sortBy(fn ($m) => $m['user']->full_name ?? '')->values();

        $teachingByUser = TeachingAssignment::query()
            ->where('school_id', $school->id)
            ->forCurrentYear($school->id)
            ->effective()
            ->with(['subject', 'schoolClass'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (TeachingAssignment $row) => (int) $row->user_id);

        $members = $members->map(function (array $member) use ($teachingByUser) {
            $userId = (int) $member['user']->id;
            $member['teaching_load'] = $teachingByUser->get($userId, collect())->map(fn (TeachingAssignment $row) => [
                'subject' => $row->subject?->name,
                'class' => $row->schoolClass instanceof SchoolClass ? $row->schoolClass->displayName() : null,
                'periods' => (int) ($row->periods_per_week ?: 3),
            ])->values()->all();

            return $member;
        });

        $openInvites = SchoolInvitation::query()
            ->where('school_id', $school->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('user')
            ->orderByDesc('id')
            ->get();

        $canManageStaff = in_array('staff.manage', $request->user()->permissionsForSchool($school->id), true);
        $perms = $request->user()->permissionsForSchool($school->id);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $members = $members->map(function (array $member) use ($policy, $actor, $school, $canManageStaff) {
            $person = $member['user'];
            $member['can_administer'] = $canManageStaff && $person instanceof User
                && $policy->canAdminister($actor, $person, $school->id);
            $member['can_edit_file'] = $person instanceof User
                && $policy->canEditStaffProfile($actor, $person, $school->id);

            return $member;
        });

        return view('app.staff.index', [
            'school' => $school,
            'members' => $members,
            'openInvites' => $openInvites,
            'roles' => $roles,
            'classes' => $classes,
            'canManageStaff' => $canManageStaff,
            'canPrintId' => in_array('staff.id.print', $perms, true),
            'canViewClock' => in_array('staff.attendance.view', $perms, true) || in_array('staff.attendance.mark', $perms, true),
            'canSetSalary' => in_array('hr.payroll.manage', $perms, true),
            'subjects' => $subjects,
        ]);
    }

    public function store(
        Request $request,
        TenantContext $context,
        StaffInvitationService $inviter,
        InvitePolicy $policy,
        StaffBadgeService $badges,
        StaffPayrollService $payroll,
    ) {
        $school = $context->school();
        abort_unless($school, 404);
        $allowed = $policy->rolesInvitableBy($request->user(), $school->id, false);

        $needsIdentity = collect($request->input('role_keys', []))->intersect(array_merge(Role::STAFF, [Role::PARENT]))->isNotEmpty();
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'nullable|email|max:190|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'gender' => [$needsIdentity ? 'required' : 'nullable', Rule::in(Gender::keys())],
            'nin' => [$needsIdentity ? 'required' : 'nullable', 'string', 'min:10', 'max:20'],
            'date_of_birth' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:80',
            'home_address' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:4096',
            'staff_kind' => ['required', Rule::in(['teaching', 'non_teaching'])],
            'role_keys' => 'required|array|min:1',
            'role_keys.*' => ['string', Rule::in($allowed)],
            'class_id' => 'nullable|integer|exists:school_classes,id',
            'teaching_assignments' => 'nullable|array',
            'teaching_assignments.*.subject_id' => 'nullable|integer',
            'teaching_assignments.*.class_ids' => 'nullable|array',
            'teaching_assignments.*.class_ids.*' => 'integer',
            'teaching_assignments.*.periods_per_week' => 'nullable|integer|min:1|max:20',
            'salary_amount' => 'nullable|integer|min:0',
            'salary_notes' => 'nullable|string|max:255',
        ]);

        $teachingRoles = array_values(array_intersect($data['role_keys'], [Role::SUBJECT_TEACHER, Role::CLASS_TEACHER]));
        if ($data['staff_kind'] === 'teaching' && $teachingRoles === []) {
            throw ValidationException::withMessages([
                'role_keys' => 'Teaching staff must be a Teacher and/or Class Teacher. Other duties can be added on top.',
            ]);
        }
        if ($data['staff_kind'] === 'non_teaching' && $teachingRoles !== []) {
            throw ValidationException::withMessages([
                'staff_kind' => 'Uncheck Teacher / Class Teacher, or mark this person as teaching staff.',
            ]);
        }

        if (in_array('subject_teacher', $data['role_keys'], true)) {
            $data['teaching_assignments'] = $data['teaching_assignments'] ?? [];
        }

        try {
            $result = $inviter->invite($school, $data, $request->user(), false);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'email' => 'Invitation could not be completed: '.$e->getMessage(),
            ]);
        }

        $delivery = $result['delivery'] ?? ['email' => false, 'sms' => false, 'warnings' => []];
        $via = collect([
            ! empty($delivery['email']) ? 'email' : null,
            ! empty($delivery['sms']) ? 'SMS' : null,
        ])->filter()->implode(' and ');

        $status = $via !== ''
            ? 'Invitation sent via '.$via.' to '.$result['user']->full_name.'.'
            : 'Invitation created for '.$result['user']->full_name.'.';

        if (! empty($delivery['warnings'])) {
            $status .= ' Note: '.implode(' ', $delivery['warnings']);
        }

        $staff = $result['user'];
        $badges->issue($school, $staff);

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo) {
                if (filled($staff->avatar_path)) {
                    Storage::disk('public')->delete($staff->avatar_path);
                }
                $staff->avatar_path = $photo->store('staff-photos', 'public');
                $staff->save();
            }
        }

        $actor = $request->user();
        $canSetSalary = $actor instanceof User
            && in_array('hr.payroll.manage', $actor->permissionsForSchool($school->id), true);
        if ($canSetSalary && filled($data['salary_amount'] ?? null)) {
            $payroll->setSalary($school, $staff, [
                'amount' => (int) $data['salary_amount'],
                'effective_on' => now()->toDateString(),
                'notes' => $data['salary_notes'] ?? null,
            ], $actor);
        }

        return back()->with('status', $status);
    }

    public function updateRoles(Request $request, User $user, TenantContext $context, StaffRoleService $roles, InvitePolicy $policy)
    {
        $school = $context->school();
        abort_unless($school, 404);
        abort_unless($request->user() instanceof User, 403);
        abort_unless($policy->canAdminister($request->user(), $user, $school->id), 403);
        $allowed = $policy->rolesInvitableBy($request->user(), $school->id, false);

        $data = $request->validate([
            'role_keys' => 'required|array|min:1',
            'role_keys.*' => ['string', Rule::in(array_values(array_unique(array_merge(
                $allowed,
                $user->activeAssignments()->where('school_id', $school->id)->with('role')->get()->pluck('role.key')->filter()->all()
            ))))],
            'class_id' => 'nullable|integer|exists:school_classes,id',
            'teaching_assignments' => 'nullable|array',
            'teaching_assignments.*.subject_id' => 'nullable|integer',
            'teaching_assignments.*.class_ids' => 'nullable|array',
            'teaching_assignments.*.class_ids.*' => 'integer',
            'teaching_assignments.*.periods_per_week' => 'nullable|integer|min:1|max:20',
        ]);

        $classId = ! empty($data['class_id']) ? (int) $data['class_id'] : null;
        try {
            $roles->sync(
                $school,
                $user,
                $data['role_keys'],
                $request->user(),
                false,
                $classId,
                $data['teaching_assignments'] ?? [],
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return back()->with('status', 'Roles updated for '.$user->full_name.'.');
    }

    public function revoke(Request $request, User $user, TenantContext $context, StaffRoleService $roles, InvitePolicy $policy)
    {
        $school = $context->school();
        abort_unless($school, 404);
        abort_unless($policy->canAdminister($request->user(), $user, $school->id), 403);
        $roles->revoke($school, $user, $request->user());

        return back()->with('status', 'School access revoked for '.$user->full_name.'.');
    }
}
