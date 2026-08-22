<?php

namespace App\Http\Controllers;

use App\Models\Guardianship;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Authorization\LearnerScope;
use App\Services\Fees\StudentLedgerService;
use App\Services\Learners\StudentLifecycleService;
use App\Services\Students\GuardianLinkService;
use App\Services\Students\StudentAccountLinkService;
use App\Services\Tenancy\TenantContext;
use App\Support\Gender;
use App\Support\Residency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function __construct(
        private TenantContext $context,
        private GuardianLinkService $guardians,
        private StudentAccountLinkService $studentAccounts,
        private StudentLifecycleService $lifecycle,
        private StudentLedgerService $ledger,
        private LearnerScope $learners,
    ) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $classFilter = $request->integer('class_id') ?: null;
        $genderFilter = (string) $request->query('gender', '');
        $statusFilter = (string) $request->query('status', '');
        $ninFilter = (string) $request->query('nin', '');
        $nationalityFilter = trim((string) $request->query('nationality', ''));
        $schoolId = $this->context->schoolId();
        abort_unless($schoolId && $request->user(), 403);

        $classIds = $this->learners->viewableClassIds($request->user(), $schoolId);
        abort_if($classIds === [], 403);
        if ($classFilter && is_array($classIds) && ! in_array($classFilter, $classIds, true)) {
            abort(403);
        }

        $students = Student::query()
            ->with('schoolClass')
            ->when(is_array($classIds), fn ($query) => $query->whereIn('class_id', $classIds))
            ->when($classFilter, fn ($query) => $query->where('class_id', $classFilter))
            ->when(in_array($genderFilter, Gender::keys(), true), fn ($query) => $query->where('gender', $genderFilter))
            ->when(in_array($statusFilter, $this->statuses(), true), fn ($query) => $query->where('status', $statusFilter))
            ->when($ninFilter === 'yes', fn ($query) => $query->whereNotNull('nin')->where('nin', '!=', ''))
            ->when($ninFilter === 'no', fn ($query) => $query->where(fn ($inner) => $inner->whereNull('nin')->orWhere('nin', '')))
            ->when($nationalityFilter !== '', fn ($query) => $query->where('nationality', 'ilike', $nationalityFilter))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('full_name', 'ilike', '%'.$q.'%')
                        ->orWhere('emis_number', 'ilike', '%'.$q.'%');
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $canManageLearners = $this->learners->canMutateAnywhere($request->user(), $schoolId);
        $canEditProfile = $this->learners->canEditListed($request->user(), $schoolId);
        $classes = $this->classesForSchool();
        if (is_array($classIds)) {
            $classes = $classes->whereIn('id', $classIds)->values();
        }

        return view('app.students.index', compact(
            'students', 'q', 'canManageLearners', 'canEditProfile', 'classes',
            'classFilter', 'genderFilter', 'statusFilter', 'ninFilter', 'nationalityFilter'
        ));
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
        $data['residency'] = Residency::normalize($data['residency'] ?? null);
        $data['nationality'] = $data['nationality'] ?? 'Uganda';
        $classId = $data['class_id'] ?? null;
        unset($data['class_id'], $data['photo']);
        $student = Student::create($data);
        $this->storePhoto($request, $student);
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
        $user = request()->user();
        $schoolId = $this->context->schoolId();
        abort_unless($user && $schoolId && $this->learners->canViewStudent($user, $schoolId, $student), 403);

        $student->load(['schoolClass', 'guardianships.guardian', 'user', 'enrollments.academicYear', 'enrollments.schoolClass']);
        $canManageLearners = $this->learners->canMutateStudent($user, $schoolId, $student);
        $canEditProfile = $this->learners->canEditProfile($user, $schoolId, $student);
        $canLinkGuardians = $this->learners->canLinkGuardian($user, $schoolId, $student);
        $canViewFinance = in_array('finance.view', $user->permissionsForSchool($schoolId), true)
            || in_array('finance.manage', $user->permissionsForSchool($schoolId), true);
        $statement = $canViewFinance ? $this->ledger->statement($student) : ['lines' => [], 'balance' => 0];

        return view('app.students.show', compact(
            'student', 'statement', 'canManageLearners', 'canEditProfile', 'canLinkGuardians', 'canViewFinance'
        ));
    }

    public function edit(Student $student)
    {
        $this->assertTenantOwned($student);
        $this->assertCanEditProfile($student);
        $user = request()->user();
        $schoolId = $this->context->schoolId();
        $full = $user && $schoolId && $this->learners->canMutateStudent($user, $schoolId, $student);
        $classes = $full
            ? $this->classesForSchool()
            : $this->restreamClasses($student);

        return view('app.students.edit', [
            'student' => $student,
            'classes' => $classes,
            'statuses' => $this->statuses(),
            'profileOnly' => ! $full,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $this->assertTenantOwned($student);
        $this->assertCanEditProfile($student);
        $user = $request->user();
        $schoolId = $this->context->schoolId();
        $full = $user && $schoolId && $this->learners->canMutateStudent($user, $schoolId, $student);
        $data = $this->validated($request, $student, $full);
        $classId = $data['class_id'] ?? null;
        unset($data['class_id'], $data['photo']);
        if (! $full) {
            unset($data['status'], $data['emis_number'], $data['schoolpay_payment_code']);
            if ($classId && ! $this->learners->canRestreamTo($user, (int) $schoolId, $student, (int) $classId)) {
                abort(403);
            }
        }
        $student->update($data);
        $this->storePhoto($request, $student);
        if ($classId && (int) $student->fresh()->class_id !== (int) $classId) {
            try {
                $this->lifecycle->enrollStudent($student->fresh(), (int) $classId);
            } catch (ValidationException $e) {
                if ($full) {
                    throw $e;
                }
                $student->update(['class_id' => $classId]);
            }
        }

        return redirect()
            ->route('app.students.show', $student)
            ->with('status', 'Student record updated.');
    }

    public function destroy(Student $student)
    {
        $this->assertTenantOwned($student);
        $this->assertCanMutate($student);
        $student->delete();

        return redirect()
            ->route('app.students.index')
            ->with('status', 'Student record archived.');
    }

    public function storeGuardian(Request $request, Student $student)
    {
        $this->assertTenantOwned($student);
        $user = $request->user();
        $schoolId = $this->context->schoolId();
        abort_unless($user && $schoolId && $this->learners->canLinkGuardian($user, $schoolId, $student), 403);
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

    public function makePrimary(Student $student, Guardianship $guardianship)
    {
        $this->assertTenantOwned($student);
        $this->assertCanMutate($student);
        abort_unless($guardianship->student_id === $student->id, 404);
        $this->guardians->makePrimary($guardianship);

        return back()->with('status', 'Primary guardian updated.');
    }

    public function destroyGuardian(Student $student, Guardianship $guardianship)
    {
        $this->assertTenantOwned($student);
        $this->assertCanMutate($student);
        abort_unless($guardianship->student_id === $student->id, 404);
        $this->guardians->detach($guardianship);

        return back()->with('status', 'Guardian detached.');
    }

    public function storeAccount(Request $request, Student $student)
    {
        $this->assertTenantOwned($student);
        $this->assertCanMutate($student);
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
        $this->assertCanMutate($student);
        $this->studentAccounts->unlink($student);

        return back()->with('status', 'Student login unlinked from this learner.');
    }

    private function assertTenantOwned(Student $student): void
    {
        $schoolId = $this->context->schoolId();
        abort_unless($schoolId !== null && (int) $student->school_id === (int) $schoolId, 404);
    }

    private function assertCanMutate(Student $student): void
    {
        $user = request()->user();
        $schoolId = $this->context->schoolId();
        abort_unless($user && $schoolId && $this->learners->canMutateStudent($user, $schoolId, $student), 403);
    }

    private function assertCanEditProfile(Student $student): void
    {
        $user = request()->user();
        $schoolId = $this->context->schoolId();
        abort_unless($user && $schoolId && $this->learners->canEditProfile($user, $schoolId, $student), 403);
    }

    private function restreamClasses(Student $student)
    {
        $current = $student->schoolClass;
        if (! $current) {
            return collect();
        }

        return collect([$current])->concat($current->siblingStreams())->unique('id')->values();
    }

    private function validated(Request $request, ?Student $student = null, bool $full = true): array
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
            'status' => [$full ? 'required' : 'nullable', Rule::in($this->statuses())],
            'gender' => ['nullable', Rule::in(Gender::keys())],
            'residency' => ['nullable', Rule::in(Residency::learnerKeys())],
            'nationality' => 'nullable|string|max:80',
            'photo' => 'nullable|image|max:2048',
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
        foreach (['emis_number', 'lin', 'nin', 'class_id', 'schoolpay_payment_code', 'nationality'] as $key) {
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
