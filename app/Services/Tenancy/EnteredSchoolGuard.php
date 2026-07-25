<?php

namespace App\Services\Tenancy;

use App\Models\Guardianship;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

/** Assert route-bound tenant records belong to the platform-entered school. */
class EnteredSchoolGuard
{
    public function enteredSchoolId(Request $request): int
    {
        $id = (int) ($request->attributes->get('entered_school_id')
            ?? $request->session()->get('platform.entered_school_id'));
        abort_unless($id > 0, 404);

        return $id;
    }

    public function school(Request $request): School
    {
        return School::query()->findOrFail($this->enteredSchoolId($request));
    }

    public function assertStudent(Request $request, Student $student): void
    {
        abort_unless((int) $student->school_id === $this->enteredSchoolId($request), 404);
    }

    public function assertClass(Request $request, SchoolClass $schoolClass): void
    {
        abort_unless((int) $schoolClass->school_id === $this->enteredSchoolId($request), 404);
    }

    public function assertGuardianship(Request $request, Student $student, Guardianship $guardianship): void
    {
        $this->assertStudent($request, $student);
        abort_unless((int) $guardianship->student_id === (int) $student->id, 404);
    }
}
