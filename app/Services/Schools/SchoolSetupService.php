<?php

namespace App\Services\Schools;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;

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
                'route' => 'app.settings.school',
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
}
