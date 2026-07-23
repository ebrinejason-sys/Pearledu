<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'with_terms' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($school, $data) {
            if (! empty($data['is_current'])) {
                AcademicYear::query()->where('school_id', $school->id)->update(['is_current' => false]);
            }

            $year = AcademicYear::create([
                'school_id' => $school->id,
                'name' => $data['name'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'is_current' => (bool) ($data['is_current'] ?? false),
            ]);

            if (! empty($data['with_terms'])) {
                $start = \Carbon\Carbon::parse($data['starts_on']);
                $end = \Carbon\Carbon::parse($data['ends_on']);
                $span = max(1, $start->diffInDays($end));
                $chunk = (int) floor($span / 3);
                foreach (['Term I', 'Term II', 'Term III'] as $i => $name) {
                    $tStart = $start->copy()->addDays($i * $chunk);
                    $tEnd = $i === 2 ? $end->copy() : $start->copy()->addDays(($i + 1) * $chunk - 1);
                    Term::create([
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'name' => $name,
                        'sequence' => $i + 1,
                        'starts_on' => $tStart->toDateString(),
                        'ends_on' => $tEnd->toDateString(),
                    ]);
                }
            }
        });

        return back()->with('status', ! empty($data['with_terms'])
            ? 'Academic year created with Term I–III.'
            : 'Academic year created.');
    }

    public function setCurrent(AcademicYear $year, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && (int) $year->school_id === (int) $school->id, 404);

        AcademicYear::query()->where('school_id', $school->id)->update(['is_current' => false]);
        $year->update(['is_current' => true]);

        return back()->with('status', $year->name.' is now the current year.');
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
            'sequence' => $data['sequence'] ?? (($year->terms()->max('sequence') ?? 0) + 1),
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
        ]);

        return back()->with('status', 'Term added.');
    }

    public function updateTerm(Request $request, Term $term, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && (int) $term->school_id === (int) $school->id, 404);

        $data = $request->validate([
            'name' => 'required|string|max:80',
            'sequence' => 'nullable|integer|min:1|max:12',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
        ]);

        $term->update($data);

        return back()->with('status', 'Term updated.');
    }
}
