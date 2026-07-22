<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index(TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $years = AcademicYear::query()->with('terms')->orderByDesc('starts_on')->get();

        return view('app.years.index', compact('school', 'years'));
    }

    public function store(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'is_current' => 'nullable|boolean',
        ]);

        if (! empty($data['is_current'])) {
            AcademicYear::query()->where('school_id', $school->id)->update(['is_current' => false]);
        }

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'is_current' => (bool) ($data['is_current'] ?? false),
        ]);

        return back()->with('status', 'Academic year created.');
    }

    public function storeTerm(Request $request, AcademicYear $year, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && $year->school_id === $school->id, 404);

        $data = $request->validate([
            'name' => 'required|string|max:80',
            'sequence' => 'nullable|integer|min:1|max:12',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
        ]);

        Term::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => $data['name'],
            'sequence' => $data['sequence'] ?? 1,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
        ]);

        return back()->with('status', 'Term added.');
    }
}
