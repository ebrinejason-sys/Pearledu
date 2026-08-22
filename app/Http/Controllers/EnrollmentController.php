<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Learners\StudentLifecycleService;
use App\Services\Tenancy\TenantContext;
use App\Support\Residency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $students = Student::query()->orderBy('full_name')->get(['id', 'full_name', 'class_id', 'residency']);
        $classes = SchoolClass::query()->orderBy('name')->get();
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $feeCatalogue = FeeStructure::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->where('applies_to', 'class')
            ->orderBy('name')
            ->get()
            ->map(fn (FeeStructure $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'amount' => (float) $s->amount,
                'class_id' => $s->class_id ? (int) $s->class_id : null,
                'residency' => $s->residency ?: 'any',
                'kind' => $s->kindLabel(),
            ]);

        return view('app.enrollments.index', compact(
            'school', 'enrollments', 'students', 'classes', 'years', 'feeCatalogue'
        ));
    }

    public function store(Request $request, TenantContext $context, StudentLifecycleService $lifecycle)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'class_id' => 'required|integer|exists:school_classes,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'residency' => ['required', Rule::in(Residency::learnerKeys())],
            'status' => 'nullable|in:active,completed,transferred,graduated,repeated',
        ]);

        $student = Student::query()->where('school_id', $school->id)->findOrFail($data['student_id']);
        $lifecycle->enrollStudent($student, (int) $data['class_id'], (int) $data['academic_year_id'], $data['residency']);

        return back()->with('status', 'Enrollment saved. Matching class and residence fee types were billed.');
    }
}
