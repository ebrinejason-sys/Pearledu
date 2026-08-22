<?php

namespace App\Services\Schools;

use App\Models\AcademicYear;
use App\Models\AssessmentPeriod;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SchoolSetupService
{
    /**
     * @return list<array{key:string,label:string,done:bool,route:?string}>
     */
    public function steps(School $school): array
    {
        $staffCount = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->toBase()
            ->distinct()
            ->count('user_id');

        return [
            [
                'key' => 'details',
                'label' => 'School details',
                'done' => filled($school->name) && filled($school->district),
                'route' => 'app.settings.school',
            ],
            [
                'key' => 'year',
                'label' => 'Academic year & terms',
                'done' => AcademicYear::query()->where('school_id', $school->id)->whereHas('terms')->exists(),
                'route' => 'app.years.index',
            ],
            [
                'key' => 'classes',
                'label' => 'Classes & streams',
                'done' => $school->classes()->exists(),
                'route' => 'app.classes.index',
            ],
            [
                'key' => 'subjects',
                'label' => 'Subjects',
                'done' => Subject::query()->where('school_id', $school->id)->exists(),
                'route' => 'app.subjects.index',
            ],
            [
                'key' => 'import',
                'label' => 'Import learners',
                'done' => Student::query()->where('school_id', $school->id)->exists(),
                'route' => 'app.students.import',
            ],
            [
                'key' => 'staff',
                'label' => 'Invite staff',
                'done' => $staffCount > 1,
                'route' => 'app.staff.index',
            ],
            [
                'key' => 'teaching',
                'label' => 'Assign teachers to classes',
                'done' => TeachingAssignment::query()->where('school_id', $school->id)->exists(),
                'route' => 'app.teaching.index',
            ],
            [
                'key' => 'assessment',
                'label' => 'Create an assessment period',
                'done' => AssessmentPeriod::query()->where('school_id', $school->id)->exists(),
                'route' => 'app.assessment.index',
            ],
            [
                'key' => 'fees',
                'label' => 'Configure fees',
                'done' => FeeStructure::query()->where('school_id', $school->id)->exists(),
                'route' => 'app.fees.index',
            ],
        ];
    }

    public function completionPercentage(School $school): int
    {
        $steps = $this->steps($school);
        $done = count(array_filter($steps, fn ($s) => $s['done']));

        return (int) round(($done / max(1, count($steps))) * 100);
    }

    /** @return array{key:string,label:string,done:bool,route:?string}|null */
    public function nextStep(School $school): ?array
    {
        foreach ($this->steps($school) as $step) {
            if (! $step['done']) {
                return $step;
            }
        }

        return null;
    }

    /** @return list<string> */
    public function missingRequirements(School $school): array
    {
        return array_values(array_map(
            fn ($s) => $s['label'],
            array_filter($this->steps($school), fn ($s) => ! $s['done']),
        ));
    }

    public function isComplete(School $school): bool
    {
        return $this->nextStep($school) === null || $school->setup_completed_at !== null;
    }

    /**
     * School-admin integrity console. Completeness + hygiene — not daily ops.
     *
     * @return array{
     *   percent: int,
     *   checks: list<array{key:string,label:string,done:bool,url:?string}>,
     *   tiles: list<array{key:string,label:string,count:int,tone:string,url:?string,hint:string}>
     * }
     */
    public function hygiene(School $school): array
    {
        $schoolId = (int) $school->id;
        $today = now(config('app.timezone'))->toDateString();

        $currentYear = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->first();
        $currentTerm = Term::query()
            ->where('school_id', $schoolId)
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->exists();

        $leadership = RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('key', [
                Role::DIRECTOR_OF_STUDIES,
                Role::BURSAR,
                Role::HEAD_TEACHER,
            ]))
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->filter()
            ->unique()
            ->all();

        $checks = [
            [
                'key' => 'year_term',
                'label' => 'Current academic year and term',
                'done' => $currentYear !== null && $currentTerm,
                'url' => Route::has('app.years.index') ? route('app.years.index') : null,
            ],
            [
                'key' => 'classes',
                'label' => 'Classes and streams',
                'done' => $school->classes()->exists(),
                'url' => Route::has('app.classes.index') ? route('app.classes.index') : null,
            ],
            [
                'key' => 'subjects',
                'label' => 'Subjects',
                'done' => Subject::query()->where('school_id', $schoolId)->exists(),
                'url' => Route::has('app.subjects.index') ? route('app.subjects.index') : null,
            ],
            [
                'key' => 'fees',
                'label' => 'Fee structures',
                'done' => FeeStructure::query()->where('school_id', $schoolId)->exists(),
                'url' => Route::has('app.fees.index') ? route('app.fees.index') : null,
            ],
            [
                'key' => 'head',
                'label' => 'Head Teacher assigned',
                'done' => in_array(Role::HEAD_TEACHER, $leadership, true),
                'url' => Route::has('app.staff.index') ? route('app.staff.index') : null,
            ],
            [
                'key' => 'dos',
                'label' => 'Director of Studies assigned',
                'done' => in_array(Role::DIRECTOR_OF_STUDIES, $leadership, true),
                'url' => Route::has('app.staff.index') ? route('app.staff.index') : null,
            ],
            [
                'key' => 'bursar',
                'label' => 'Bursar assigned',
                'done' => in_array(Role::BURSAR, $leadership, true),
                'url' => Route::has('app.staff.index') ? route('app.staff.index') : null,
            ],
        ];

        if ($school->schoolPayEnabled()) {
            $checks[] = [
                'key' => 'schoolpay',
                'label' => 'SchoolPay credentials',
                'done' => $school->schoolPayConfigured(),
                'url' => Route::has('app.settings.school') ? route('app.settings.school') : null,
            ];
        }

        $modules = is_array($school->enabled_modules) ? $school->enabled_modules : [];
        if (($modules['sms'] ?? false) === true) {
            $checks[] = [
                'key' => 'sms',
                'label' => 'SMS credits available',
                'done' => $school->smsBalance() > 0,
                'url' => Route::has('app.sms') ? route('app.sms') : null,
            ];
        }

        $done = count(array_filter($checks, fn ($c) => $c['done']));
        $percent = (int) round(($done / max(1, count($checks))) * 100);

        $classTeacherIds = RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereNotNull('class_id')
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $classesWithoutTeacher = SchoolClass::query()
            ->where('school_id', $schoolId)
            ->when($classTeacherIds !== [], fn ($q) => $q->whereNotIn('id', $classTeacherIds))
            ->count();

        $yearId = $currentYear?->id;
        $teacherIds = RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('key', Role::SUBJECT_TEACHER))
            ->pluck('user_id')
            ->unique();

        $loadedIds = $yearId
            ? TeachingAssignment::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $yearId)
                ->effective()
                ->pluck('user_id')
                ->unique()
            : collect();

        $teachersWithoutLoad = $teacherIds->diff($loadedIds)->count();

        $invitedStaff = User::query()
            ->where('status', 'invited')
            ->whereHas('roleAssignments', fn ($q) => $q->where('school_id', $schoolId)->whereHas('role', fn ($rq) => $rq->whereIn('key', Role::STAFF)))
            ->count();

        $duplicateNames = (int) DB::query()
            ->fromSub(
                Student::query()
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->selectRaw('lower(full_name) as n')
                    ->groupByRaw('lower(full_name)')
                    ->havingRaw('count(*) > 1')
                    ->toBase(),
                'dupes',
            )
            ->count();

        $missingPhotos = Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereNull('photo_path')
            ->count();

        $tiles = [
            [
                'key' => 'invited',
                'label' => 'Invited, not activated',
                'count' => $invitedStaff,
                'tone' => $invitedStaff > 0 ? 'warning' : 'success',
                'url' => Route::has('app.staff.index') ? route('app.staff.index') : null,
                'hint' => 'Staff still on invite — they cannot sign in.',
            ],
            [
                'key' => 'no_class_teacher',
                'label' => 'Classes without a class teacher',
                'count' => $classesWithoutTeacher,
                'tone' => $classesWithoutTeacher > 0 ? 'danger' : 'success',
                'url' => Route::has('app.staff.index') ? route('app.staff.index') : null,
                'hint' => 'Homeroom needs a Class Teacher assignment with a class.',
            ],
            [
                'key' => 'no_load',
                'label' => 'Teachers with no current-year load',
                'count' => $teachersWithoutLoad,
                'tone' => $teachersWithoutLoad > 0 ? 'warning' : 'success',
                'url' => Route::has('app.teaching.index') ? route('app.teaching.index') : null,
                'hint' => 'Subject + class rows the timetable already uses.',
            ],
            [
                'key' => 'duplicates',
                'label' => 'Duplicate-looking learners',
                'count' => $duplicateNames,
                'tone' => $duplicateNames > 0 ? 'warning' : 'success',
                'url' => Route::has('app.students.index') ? route('app.students.index') : null,
                'hint' => 'Same full name listed more than once. Review, do not auto-merge.',
            ],
            [
                'key' => 'photos',
                'label' => 'Learners missing a photo',
                'count' => $missingPhotos,
                'tone' => $missingPhotos > 0 ? 'warning' : 'success',
                'url' => Route::has('app.students.index') ? route('app.students.index') : null,
                'hint' => 'Empty grey boxes on registers are a data-quality bug.',
            ],
        ];

        return [
            'percent' => $percent,
            'checks' => $checks,
            'tiles' => $tiles,
        ];
    }
}
