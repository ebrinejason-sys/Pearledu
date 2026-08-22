<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\Guardianship;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\LearnerScope;
use App\Services\Fees\FeeInvoiceService;
use App\Services\Fees\StudentLedgerService;
use App\Services\Learners\StudentLifecycleService;
use App\Services\Students\GuardianLinkService;
use App\Services\Students\StudentAccountLinkService;
use App\Services\Tenancy\TenantContext;
use App\Support\FeeKind;
use App\Support\Gender;
use App\Support\Residency;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        private FeeInvoiceService $invoices,
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
        $user = request()->user();
        $schoolId = $this->context->schoolId();
        $canApplyFees = $this->actorCanApplyFees($user, $schoolId);

        return view('app.students.create', [
            'classes' => $this->classesForSchool(),
            'statuses' => $this->statuses(),
            'canApplyFees' => $canApplyFees,
            'applyableStructures' => $canApplyFees ? $this->applyableStructures(null, false) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->validateOptionalFirstGuardian($request);
        $data['residency'] = Residency::normalize($data['residency'] ?? null);
        $data['nationality'] = $data['nationality'] ?? 'Uganda';
        $classId = $data['class_id'] ?? null;
        unset($data['class_id'], $data['photo']);
        $student = Student::create($data);
        $this->storePhoto($request, $student);
        if ($classId) {
            $this->lifecycle->enrollStudent($student, (int) $classId);
            $this->invoices->assignDefaultStructures($student->fresh());
        }
        $this->applySelectedFeeStructures($request, $student->fresh());
        $this->maybeInviteFirstGuardian($request, $student);

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
        $canApplyFees = $this->actorCanApplyFees($user, $schoolId);
        $profileTab = (string) request()->query('tab', 'basic');
        if (! in_array($profileTab, ['basic', 'guardians', 'fees', 'login'], true)) {
            $profileTab = 'basic';
        }
        $errorBag = request()->session()->get('errors');
        if (is_object($errorBag) && method_exists($errorBag, 'has')
            && ($errorBag->has('fee_structure_id') || $errorBag->has('name') || $errorBag->has('amount'))) {
            $profileTab = 'fees';
        }
        if ($profileTab === 'fees' && ! $canViewFinance && ! $canApplyFees) {
            $profileTab = 'basic';
        }
        $statement = $canViewFinance ? $this->ledger->statement($student) : ['lines' => [], 'balance' => 0];
        $feeKinds = FeeKind::keys();
        $applyableStructures = collect();
        $invoicedStructureIds = [];
        if ($canViewFinance || $canApplyFees) {
            $classId = $student->class_id ? (int) $student->class_id : null;
            $applyableStructures = $this->applyableStructures($classId, true);
            $invoicedStructureIds = FeeInvoice::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where('status', '!=', 'void')
                ->whereNotNull('fee_structure_id')
                ->pluck('fee_structure_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return view('app.students.show', compact(
            'student', 'statement', 'canManageLearners', 'canEditProfile', 'canLinkGuardians',
            'canViewFinance', 'canApplyFees', 'feeKinds', 'applyableStructures', 'invoicedStructureIds',
            'profileTab'
        ));
    }

    public function edit(Student $student)
    {
        $this->assertTenantOwned($student);
        $this->assertCanEditProfile($student);
        $user = request()->user();
        $schoolId = $this->context->schoolId();
        $full = $user && $schoolId && $this->learners->canMutateStudent($user, $schoolId, $student);
        $canApplyFees = $full && $this->actorCanApplyFees($user, $schoolId);
        $classes = $full
            ? $this->classesForSchool()
            : $this->restreamClasses($student);
        $invoicedStructureIds = [];
        if ($canApplyFees) {
            $invoicedStructureIds = FeeInvoice::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where('status', '!=', 'void')
                ->whereNotNull('fee_structure_id')
                ->pluck('fee_structure_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return view('app.students.edit', [
            'student' => $student,
            'classes' => $classes,
            'statuses' => $this->statuses(),
            'profileOnly' => ! $full,
            'canApplyFees' => $canApplyFees,
            'applyableStructures' => $canApplyFees
                ? $this->applyableStructures($student->class_id ? (int) $student->class_id : null, true)
                : collect(),
            'invoicedStructureIds' => $invoicedStructureIds,
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
        $residency = $data['residency'] ?? $student->residency;
        unset($data['class_id'], $data['photo']);
        if (! $full) {
            unset($data['status'], $data['emis_number'], $data['schoolpay_payment_code']);
            if ($classId && ! $this->learners->canRestreamTo($user, (int) $schoolId, $student, (int) $classId)) {
                abort(403);
            }
        }
        $classChanged = $classId && (int) $student->class_id !== (int) $classId;
        $residencyChanged = Residency::normalize($residency) !== Residency::normalize($student->residency);
        $student->update($data);
        $this->storePhoto($request, $student);
        if ($classChanged) {
            try {
                $this->lifecycle->enrollStudent($student->fresh(), (int) $classId);
            } catch (ValidationException $e) {
                if ($full) {
                    throw $e;
                }
                $student->update(['class_id' => $classId]);
            }
        }
        if ($full && ($classChanged || $residencyChanged)) {
            $this->invoices->assignDefaultStructures($student->fresh());
        }
        $this->applySelectedFeeStructures($request, $student->fresh());

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
                'photo' => 'nullable|image|max:2048',
            ]);

            $link = $this->guardians->inviteNew(
                $student,
                $data['full_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['relationship'] ?? null,
                (bool) ($data['is_primary'] ?? false),
                $request->user()?->id,
                $data['nin'],
            );
            $this->storeGuardianPhoto($request, $link->guardian);

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

    public function applyFee(Request $request, Student $student)
    {
        $this->assertTenantOwned($student);
        $user = $request->user();
        $schoolId = $this->context->schoolId();
        abort_unless($user && $schoolId && $this->actorCanApplyFees($user, $schoolId), 403);

        if ($request->filled('fee_structure_id')) {
            $data = $request->validate([
                'fee_structure_id' => 'required|integer',
                'due_on' => 'nullable|date',
            ]);
            $structure = FeeStructure::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->findOrFail($data['fee_structure_id']);
            $invoice = $this->invoices->invoiceStudent($student, $structure, $data['due_on'] ?? null);

            return redirect()
                ->route('app.students.show', ['student' => $student, 'tab' => 'fees'])
                ->with('status', 'Fee applied. Invoice '.$invoice->reference.' · balance UGX '.number_format((float) $invoice->balance).'.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'kind' => ['required', Rule::in(FeeKind::keys())],
            'due_on' => 'nullable|date',
        ]);
        $invoice = $this->invoices->applyCustomFee($student, $data);

        return redirect()
            ->route('app.students.show', ['student' => $student, 'tab' => 'fees'])
            ->with('status', 'Custom fee applied to '.$student->full_name.'. Invoice '.$invoice->reference.'.');
    }

    private function maybeInviteFirstGuardian(Request $request, Student $student): void
    {
        if (trim((string) $request->input('guardian_full_name', '')) === ''
            || trim((string) $request->input('guardian_email', '')) === '') {
            return;
        }

        $data = $this->validateOptionalFirstGuardian($request);
        if ($data === null) {
            return;
        }

        $link = $this->guardians->inviteNew(
            $student,
            $data['guardian_full_name'],
            $data['guardian_email'],
            $data['guardian_phone'] ?? null,
            $data['guardian_relationship'] ?? null,
            true,
            $request->user()?->id,
            $data['guardian_nin'],
        );
        if ($request->hasFile('guardian_photo') && $link->guardian) {
            $file = $request->file('guardian_photo');
            if ($file) {
                $link->guardian->avatar_path = $file->store('avatars/'.$link->guardian->id, 'public');
                $link->guardian->save();
            }
        }
    }

    /** @return array<string, mixed>|null */
    private function validateOptionalFirstGuardian(Request $request): ?array
    {
        if (trim((string) $request->input('guardian_full_name', '')) === ''
            && trim((string) $request->input('guardian_email', '')) === '') {
            return null;
        }

        return $request->validate([
            'guardian_full_name' => 'required|string|max:120',
            'guardian_email' => 'required|email|max:190',
            'guardian_phone' => 'nullable|string|max:30',
            'guardian_nin' => 'required|string|min:10|max:20',
            'guardian_relationship' => 'nullable|string|max:60',
            'guardian_photo' => 'nullable|image|max:2048',
        ]);
    }

    private function storeGuardianPhoto(Request $request, ?User $guardian): void
    {
        if (! $guardian || ! $request->hasFile('photo')) {
            return;
        }
        $file = $request->file('photo');
        if ($guardian->avatar_path) {
            Storage::disk('public')->delete($guardian->avatar_path);
        }
        $guardian->avatar_path = $file->store('avatars/'.$guardian->id, 'public');
        $guardian->save();
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
            'date_of_birth' => 'nullable|date|before:today',
            'residency' => ['nullable', Rule::in(Residency::learnerKeys())],
            'nationality' => 'nullable|string|max:80',
            'religion' => 'nullable|string|max:80',
            'home_address' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string|max:2000',
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
        foreach (['emis_number', 'lin', 'nin', 'class_id', 'schoolpay_payment_code', 'nationality', 'religion', 'home_address', 'medical_notes', 'date_of_birth'] as $key) {
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
        if ($student->user) {
            $student->user->avatar_path = $student->photo_path;
            $student->user->save();
        }
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

    private function actorCanApplyFees(?User $user, ?int $schoolId): bool
    {
        if (! $user || ! $schoolId) {
            return false;
        }
        $perms = $user->permissionsForSchool($schoolId);

        return in_array('finance.manage', $perms, true)
            || in_array('fees.invoice.create', $perms, true);
    }

    /**
     * @return Collection<int, FeeStructure>
     */
    private function applyableStructures(?int $classId, bool $requireClassMatch): Collection
    {
        $schoolId = $this->context->schoolId();
        if (! $schoolId) {
            return collect();
        }

        return FeeStructure::query()
            ->with('schoolClass')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($q) use ($classId, $requireClassMatch) {
                $q->where('applies_to', 'learners')
                    ->orWhere(function ($class) use ($classId, $requireClassMatch) {
                        $class->where('applies_to', 'class');
                        if (! $requireClassMatch) {
                            return;
                        }
                        $class->where(function ($scope) use ($classId) {
                            $scope->whereNull('class_id');
                            if ($classId) {
                                $scope->orWhere('class_id', $classId);
                            }
                        });
                    });
            })
            ->orderBy('name')
            ->get();
    }

    private function applySelectedFeeStructures(Request $request, Student $student): void
    {
        $user = $request->user();
        $schoolId = $this->context->schoolId();
        if (! $this->actorCanApplyFees($user, $schoolId)) {
            return;
        }
        if (! $request->exists('fee_structure_ids')) {
            return;
        }

        $ids = $request->validate([
            'fee_structure_ids' => 'nullable|array',
            'fee_structure_ids.*' => 'integer',
        ])['fee_structure_ids'] ?? [];

        foreach ($ids as $id) {
            $structure = FeeStructure::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->find((int) $id);
            if (! $structure) {
                continue;
            }
            try {
                $this->invoices->invoiceStudent($student->fresh(), $structure);
            } catch (ValidationException) {
                // Skip structures that do not match class/residency.
            }
        }
    }
}
