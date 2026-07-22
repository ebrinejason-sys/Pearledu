<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $enrollments = Enrollment::query()
            ->with(['student', 'schoolClass', 'academicYear'])
            ->orderByDesc('id')
            ->paginate(30);

        $students = Student::query()->orderBy('full_name')->get(['id', 'full_name']);
        $classes = SchoolClass::query()->orderBy('name')->get();
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();

        return view('app.enrollments.index', compact('school', 'enrollments', 'students', 'classes', 'years'));
    }

    public function store(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'class_id' => 'required|integer|exists:school_classes,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'status' => 'nullable|in:active,completed,transferred,graduated,repeated',
        ]);

        Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $data['student_id'],
            'class_id' => $data['class_id'],
            'academic_year_id' => $data['academic_year_id'],
            'status' => $data['status'] ?? 'active',
        ]);

        Student::query()->where('id', $data['student_id'])->update(['class_id' => $data['class_id']]);

        return back()->with('status', 'Enrollment saved.');
    }
}
