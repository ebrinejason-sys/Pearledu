<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\EnteredSchoolGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function __construct(
        private AuditLogger $audit,
        private EnteredSchoolGuard $entered,
    ) {}

    public function index(Request $request)
    {
        $school = $this->entered->school($request);
        $classes = SchoolClass::query()
            ->withCount('students')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $levels = $school->offerings()->pluck('level')->all();

        return view('platform.classes.index', compact('school', 'classes', 'levels'));
    }

    public function store(Request $request)
    {
        $school = $this->entered->school($request);
        $levels = $school->offerings()->pluck('level')->all();

        $data = $request->validate([
            'level' => ['required', 'string', Rule::in($levels ?: ['primary', 'secondary', 'a_level'])],
            'name' => 'required|string|max:80',
            'stream' => 'nullable|string|max:40',
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('school_classes', 'code')->where(fn ($q) => $q->where('school_id', $school->id)),
            ],
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'level' => $data['level'],
            'name' => trim($data['name']),
            'stream' => filled($data['stream'] ?? null) ? trim((string) $data['stream']) : null,
            'code' => $data['code'],
        ]);
        $this->audit->record('platform.class.created', $class, ['school_id' => $school->id]);

        return back()->with('status', 'Class “'.$class->displayName().'” created.');
    }

    public function destroy(Request $request, SchoolClass $schoolClass)
    {
        $this->entered->assertClass($request, $schoolClass);

        if ($schoolClass->students()->exists()) {
            return back()->withErrors(['class' => 'Reassign or archive students in this class before deleting it.']);
        }

        $schoolClass->delete();
        $this->audit->record('platform.class.deleted', $schoolClass, [
            'school_id' => $this->entered->enteredSchoolId($request),
        ]);

        return back()->with('status', 'Class removed.');
    }
}
