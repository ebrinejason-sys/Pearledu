<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\Academics\TermCalendar;
use App\Services\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index(TenantContext $context, TermCalendar $calendar)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $years = AcademicYear::query()->with('terms')->orderByDesc('starts_on')->get();
        $defaultStart = now()->month >= 11
            ? now()->addYear()->startOfYear()->month(2)->day(2)->toDateString()
            : now()->startOfYear()->month(2)->day(2)->toDateString();
        $defaultEnd = Carbon::parse($defaultStart)->year.'-12-04';
        $suggestedTerms = $calendar->suggestThreeTerms(
            old('starts_on', $defaultStart),
            old('ends_on', $defaultEnd),
        );

        return view('app.years.index', compact('school', 'years', 'suggestedTerms', 'defaultStart', 'defaultEnd'));
    }

    public function store(Request $request, TenantContext $context, TermCalendar $calendar)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'is_current' => 'nullable|boolean',
            'with_terms' => 'nullable|boolean',
            'terms' => 'nullable|array|size:3',
            'terms.*.name' => 'required_with:terms|string|max:80',
            'terms.*.starts_on' => 'required_with:terms|date',
            'terms.*.ends_on' => 'required_with:terms|date|after_or_equal:terms.*.starts_on',
        ]);

        DB::transaction(function () use ($school, $data, $calendar) {
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

            if (! empty($data['with_terms']) || ! empty($data['terms'])) {
                $terms = $data['terms'] ?? $calendar->suggestThreeTerms($data['starts_on'], $data['ends_on']);
                foreach ($terms as $i => $term) {
                    Term::create([
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'name' => $term['name'] ?? ('Term '.($i + 1)),
                        'sequence' => $term['sequence'] ?? ($i + 1),
                        'starts_on' => $term['starts_on'],
                        'ends_on' => $term['ends_on'],
                    ]);
                }
            }
        });

        return back()->with('status', (! empty($data['with_terms']) || ! empty($data['terms']))
            ? 'Academic year created. Confirm Term I–III dates below if you need to adjust them.'
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
