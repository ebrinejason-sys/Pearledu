<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\People\GenderStatsService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassOverviewController extends Controller
{
    public function index(Request $request, TenantContext $ctx, GenderStatsService $gender): View
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $classes = SchoolClass::query()
            ->where('school_id', $school->id)
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->map(function (SchoolClass $class) use ($school, $gender) {
                $learners = Student::query()
                    ->where('school_id', $school->id)
                    ->where('class_id', $class->id)
                    ->where('status', 'active');
                $mean = Mark::query()
                    ->where('school_id', $school->id)
                    ->where('class_id', $class->id)
                    ->whereHas('period', fn ($q) => $q->whereIn('status', ['published', 'locked']))
                    ->avg('score');

                return [
                    'class' => $class,
                    'students' => (clone $learners)->count(),
                    'gender' => $gender->countStudents($school, $class->id),
                    'mean' => $mean !== null ? round((float) $mean, 1) : null,
                ];
            });

        return view('app.classes.overview', [
            'school' => $school,
            'rows' => $classes,
            'gender' => $gender->forSchool($school),
        ]);
    }
}
