<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Guardianship;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Students\GuardianLinkService;
use App\Services\Tenancy\EnteredSchoolGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function __construct(
        private GuardianLinkService $guardians,
        private AuditLogger $audit,
        private EnteredSchoolGuard $entered,
    ) {}

    public function index(Request $request)
    {
        $school = $this->entered->school($request);
        $q = trim((string) $request->query('q', ''));

        $students = Student::query()
            ->with('schoolClass')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('full_name', 'ilike', '%'.$q.'%')
                        ->orWhere('emis_number', 'ilike', '%'.$q.'%');
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('platform.students.index', compact('school', 'students', 'q'));
    }

    public function create(Request $request)
    {
        return view('platform.students.create', [
            'school' => $this->entered->school($request),
            'classes' => $this->classesForSchool(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $school = $this->entered->school($request);
        $student = Student::create($this->validated($request) + ['school_id' => $school->id]);
        $this->audit->record('platform.student.created', $student, ['school_id' => $school->id]);

        return redirect()
            ->route('platform.students.show', $student)
            ->with('status', 'Student record created.');
    }

    public function show(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);
        $student->load(['schoolClass', 'guardianships.guardian']);

        return view('platform.students.show', [
            'school' => $this->entered->school($request),
            'student' => $student,
        ]);
    }

    public function edit(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);

        return view('platform.students.edit', [
            'school' => $this->entered->school($request),
            'student' => $student,
            'classes' => $this->classesForSchool(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);
        $student->update($this->validated($request, $student));
        $this->audit->record('platform.student.updated', $student);

        return redirect()
            ->route('platform.students.show', $student)
            ->with('status', 'Student record updated.');
    }

    public function destroy(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);
        $student->delete();
        $this->audit->record('platform.student.archived', $student);

        return redirect()
            ->route('platform.students.index')
            ->with('status', 'Student record archived.');
    }

    public function storeGuardian(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);
        $mode = $request->input('mode', 'attach');

        if ($mode === 'invite') {
            $data = $request->validate([
                'full_name' => 'required|string|max:120',
                'email' => 'required|email|max:190',
                'phone' => 'nullable|string|max:30',
                'relationship' => 'nullable|string|max:60',
                'is_primary' => 'sometimes|boolean',
            ]);

            $this->guardians->inviteNew(
                $student,
                $data['full_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['relationship'] ?? null,
                (bool) ($data['is_primary'] ?? false),
                $request->user()?->id,
            );

            return back()->with('status', 'Guardian invited and linked.');
        }

        $data = $request->validate([
            'email' => 'required|email|max:190',
            'relationship' => 'nullable|string|max:60',
            'is_primary' => 'sometimes|boolean',
        ]);

        $this->guardians->attachExisting(
            $student,
            $data['email'],
            $data['relationship'] ?? null,
            (bool) ($data['is_primary'] ?? false),
            $request->user()?->id,
        );

        return back()->with('status', 'Guardian linked.');
    }

    public function makePrimary(Request $request, Student $student, Guardianship $guardianship)
    {
        $this->entered->assertGuardianship($request, $student, $guardianship);
        $this->guardians->makePrimary($guardianship);

        return back()->with('status', 'Primary guardian updated.');
    }

    public function destroyGuardian(Request $request, Student $student, Guardianship $guardianship)
    {
        $this->entered->assertGuardianship($request, $student, $guardianship);
        $this->guardians->detach($guardianship);

        return back()->with('status', 'Guardian detached.');
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $schoolId = $this->entered->enteredSchoolId($request);

        $data = $request->validate([
            'full_name' => 'required|string|max:160',
            'emis_number' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('students', 'emis_number')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at'))
                    ->ignore($student?->id),
            ],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('school_classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'status' => ['required', Rule::in($this->statuses())],
            'lin' => 'nullable|string|max:120',
            'nin' => 'nullable|string|max:120',
        ]);

        foreach (['emis_number', 'lin', 'nin', 'class_id'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    /** @return list<string> */
    private function statuses(): array
    {
        return ['active', 'inactive', 'transferred', 'graduated'];
    }

    private function classesForSchool()
    {
        return SchoolClass::query()->orderBy('level')->orderBy('name')->get();
    }
}
