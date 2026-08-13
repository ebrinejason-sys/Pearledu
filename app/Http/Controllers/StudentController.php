<?php

namespace App\Http\Controllers;

use App\Models\Guardianship;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Fees\StudentLedgerService;
use App\Services\Learners\StudentLifecycleService;
use App\Services\Students\GuardianLinkService;
use App\Services\Students\StudentAccountLinkService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function __construct(
        private TenantContext $context,
        private GuardianLinkService $guardians,
        private StudentAccountLinkService $studentAccounts,
        private StudentLifecycleService $lifecycle,
        private StudentLedgerService $ledger,
    ) {}

    public function index(Request $request)
    {
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

        return view('app.students.index', compact('students', 'q'));
    }

    public function create()
    {
        return view('app.students.create', [
            'classes' => $this->classesForSchool(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $classId = $data['class_id'] ?? null;
        unset($data['class_id']);
        $student = Student::create($data);
        if ($classId) {
            $this->lifecycle->enrollStudent($student, (int) $classId);
        }

        return redirect()
            ->route('app.students.show', $student)
            ->with('status', 'Student record created.');
    }

    public function show(Student $student)
    {
        $this->assertTenantOwned($student);
        $student->load(['schoolClass', 'guardianships.guardian', 'user', 'enrollments.academicYear', 'enrollments.schoolClass']);
        $statement = $this->ledger->statement($student);

        return view('app.students.show', compact('student', 'statement'));
    }

    public function edit(Student $student)
    {
        $this->assertTenantOwned($student);

        return view('app.students.edit', [
            'student' => $student,
            'classes' => $this->classesForSchool(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $this->assertTenantOwned($student);
        $data = $this->validated($request, $student);
        $classId = $data['class_id'] ?? null;
        unset($data['class_id']);
        $student->update($data);
        if ($classId && (int) $student->fresh()->class_id !== (int) $classId) {
            $this->lifecycle->enrollStudent($student->fresh(), (int) $classId);
        }

        return redirect()
            ->route('app.students.show', $student)
            ->with('status', 'Student record updated.');
    }

    public function destroy(Student $student)
    {
        $this->assertTenantOwned($student);
        $student->delete();

        return redirect()
            ->route('app.students.index')
            ->with('status', 'Student record archived.');
    }

    public function storeGuardian(Request $request, Student $student)
    {
        $this->assertTenantOwned($student);
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

    public function makePrimary(Student $student, Guardianship $guardianship)
    {
        $this->assertTenantOwned($student);
        abort_unless($guardianship->student_id === $student->id, 404);
        $this->guardians->makePrimary($guardianship);

        return back()->with('status', 'Primary guardian updated.');
    }

    public function destroyGuardian(Student $student, Guardianship $guardianship)
    {
        $this->assertTenantOwned($student);
        abort_unless($guardianship->student_id === $student->id, 404);
        $this->guardians->detach($guardianship);

        return back()->with('status', 'Guardian detached.');
    }

    public function storeAccount(Request $request, Student $student)
    {
        $this->assertTenantOwned($student);
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

    public function destroyAccount(Student $student)
    {
        $this->assertTenantOwned($student);
        $this->studentAccounts->unlink($student);

        return back()->with('status', 'Student login unlinked from this learner.');
    }

    private function assertTenantOwned(Student $student): void
    {
        $schoolId = $this->context->schoolId();
        abort_unless($schoolId !== null && (int) $student->school_id === (int) $schoolId, 404);
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $schoolId = $this->context->schoolId();

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
            'schoolpay_payment_code' => [
                'nullable',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('students', 'schoolpay_payment_code')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at'))
                    ->ignore($student?->id),
            ],
        ]);

        // Empty strings → null so unique(emis) and encryption stay clean
        foreach (['emis_number', 'lin', 'nin', 'class_id', 'schoolpay_payment_code'] as $key) {
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
