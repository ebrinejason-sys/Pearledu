<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $school = $this->school($request);
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
        $school = $this->school($request);
        $levels = $school->offerings()->pluck('level')->all();

        $data = $request->validate([
            'level' => ['required', 'string', Rule::in($levels ?: ['primary', 'secondary', 'a_level'])],
            'name' => 'required|string|max:80',
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('school_classes', 'code')->where(fn ($q) => $q->where('school_id', $school->id)),
            ],
        ]);

        $class = SchoolClass::create($data);
        $this->audit->record('platform.class.created', $class, ['school_id' => $school->id]);

        return back()->with('status', 'Class “'.$class->name.'” created.');
    }

    public function destroy(Request $request, SchoolClass $schoolClass)
    {
        $school = $this->school($request);
        abort_unless((int) $schoolClass->school_id === (int) $school->id, 404);

        if ($schoolClass->students()->exists()) {
            return back()->withErrors(['class' => 'Reassign or archive students in this class before deleting it.']);
        }

        $schoolClass->delete();
        $this->audit->record('platform.class.deleted', $schoolClass, ['school_id' => $school->id]);

        return back()->with('status', 'Class removed.');
    }

    private function school(Request $request): School
    {
        return School::findOrFail($request->session()->get('platform.entered_school_id'));
    }
}
