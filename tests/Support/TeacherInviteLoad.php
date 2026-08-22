<?php

namespace Tests\Support;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;

/**
 * Teaching load required whenever a Teacher (subject_teacher) invite is sent.
 */
class TeacherInviteLoad
{
    /**
     * @return array{
     *   year: AcademicYear,
     *   class: SchoolClass,
     *   subject: Subject,
     *   teaching_assignments: list<array{subject_id:int, class_ids: list<int>, periods_per_week?: int}>
     * }
     */
    public static function ensure(School $school): array
    {
        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->first()
            ?? AcademicYear::create([
                'school_id' => $school->id,
                'name' => 'Invite '.$school->id,
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'is_current' => true,
            ]);

        $class = SchoolClass::query()->where('school_id', $school->id)->orderBy('id')->first()
            ?? SchoolClass::create([
                'school_id' => $school->id,
                'level' => 'primary',
                'name' => 'P5 Invite',
                'code' => 'INV-'.$school->id,
            ]);

        $subject = Subject::query()->where('school_id', $school->id)->orderBy('id')->first()
            ?? Subject::create([
                'school_id' => $school->id,
                'name' => 'English',
                'code' => 'ENG-INV-'.$school->id,
            ]);

        return [
            'year' => $year,
            'class' => $class,
            'subject' => $subject,
            'teaching_assignments' => [
                [
                    'subject_id' => (int) $subject->id,
                    'class_ids' => [(int) $class->id],
                    'periods_per_week' => 3,
                ],
            ],
        ];
    }
}
