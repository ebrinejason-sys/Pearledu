<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Guardianship;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Students\GuardianLinkService;
use App\Services\Students\StudentAccountLinkService;
use App\Services\Tenancy\EnteredSchoolGuard;
use App\Support\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function __construct(
        private GuardianLinkService $guardians,
        private StudentAccountLinkService $studentAccounts,
        private AuditLogger $audit,
        private EnteredSchoolGuard $entered,
    ) {}

    public function index(Request $request)
    {
        $school = $this->entered->school($request);
        $q = trim((string) $request->query('q', ''));
        $classFilter = $request->integer('class_id') ?: null;
        $genderFilter = (string) $request->query('gender', '');

        $students = Student::query()
            ->with('schoolClass')
            ->when($classFilter, fn ($query) => $query->where('class_id', $classFilter))
            ->when(in_array($genderFilter, Gender::keys(), true), fn ($query) => $query->where('gender', $genderFilter))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('full_name', 'ilike', '%'.$q.'%')
                        ->orWhere('emis_number', 'ilike', '%'.$q.'%');
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $classes = $this->classesForSchool();

        return view('platform.students.index', compact('school', 'students', 'q', 'classes', 'classFilter', 'genderFilter'));
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
        $data = $this->validated($request);
        unset($data['photo']);
        $student = Student::create($data + ['school_id' => $school->id]);
        $this->storePhoto($request, $student);
        $this->audit->record('platform.student.created', $student, ['school_id' => $school->id]);

        return redirect()
            ->route('platform.students.show', $student)
            ->with('status', 'Student record created.');
    }

    public function show(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);
        $student->load(['schoolClass', 'guardianships.guardian', 'user']);

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
        $data = $this->validated($request, $student);
        unset($data['photo']);
        $student->update($data);
        $this->storePhoto($request, $student);
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
                'nin' => 'required|string|min:10|max:20',
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
                $data['nin'],
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

    public function storeAccount(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);
        $mode = $request->input('mode', 'attach');

        if ($mode === 'invite') {
            $data = $request->validate([
                'full_name' => 'required|string|max:120',
                'email' => 'required|email|max:190',
                'phone' => 'nullable|string|max:30',
            ]);

            $this->studentAccounts->inviteNew(
                $student,
                $data['full_name'],
                $data['email'],
                $data['phone'] ?? null,
                $request->user()?->id,
            );

            return back()->with('status', 'Student login invited and linked.');
        }

        $data = $request->validate([
            'email' => 'required|email|max:190',
        ]);

        $this->studentAccounts->attachExisting(
            $student,
            $data['email'],
            $request->user()?->id,
        );

        return back()->with('status', 'Student login linked.');
    }

    public function destroyAccount(Request $request, Student $student)
    {
        $this->entered->assertStudent($request, $student);
        $this->studentAccounts->unlink($student);

        return back()->with('status', 'Student login unlinked from this learner.');
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
            'gender' => ['nullable', Rule::in(Gender::keys())],
            'photo' => 'nullable|image|max:2048',
            'lin' => 'nullable|string|max:120',
            'nin' => 'nullable|string|max:120',
        ]);

        foreach (['emis_number', 'lin', 'nin', 'class_id'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }
        if ($student) {
            foreach (['nin', 'lin'] as $sensitive) {
                if (! filled($data[$sensitive] ?? null)) {
                    unset($data[$sensitive]);
                }
            }
        }

        return $data;
    }

    private function storePhoto(Request $request, Student $student): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }
        $student->photo_path = $request->file('photo')->store('students/'.$student->id, 'public');
        $student->save();
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
