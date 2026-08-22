<?php

namespace App\Services\Provisioning;

use App\Models\AcademicYear;
use App\Models\AssessmentPeriod;
use App\Models\FeeStructure;
use App\Models\Guardianship;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\Fees\FeeInvoiceService;
use App\Services\Learners\StudentLifecycleService;
use App\Services\Sms\SmsCreditService;
use App\Services\Tenancy\TenantContext;
use App\Support\Gender;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Opt-in walkthrough tenant: Baby–P7, ~100 learners, named staff.
 * Uses SchoolProvisioner + role_assignments + enrollments.
 * Production requires an explicit --force from the artisan command.
 */
class WalkthroughSchoolService
{
    public const SCHOOL_NAME = 'St. Kizito Demonstration Primary';

    public const EMIS_NUMBER = '1999001';

    public const DISTRICT = 'Kampala';

    /** @var list<string> */
    public const ROSTER_CODES = ['BABY', 'MID', 'TOP', 'P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];

    public const STUDENTS_PER_CLASS = 10;

    public function __construct(
        private SchoolProvisioner $provisioner,
        private TenantContext $context,
        private StudentLifecycleService $lifecycle,
        private FeeInvoiceService $billing,
        private SmsCreditService $sms,
    ) {}

    /**
     * @return array{
     *     school: School,
     *     created: bool,
     *     students: int,
     *     per_class: array<string, int>,
     *     accounts: list<array{role: string, email: string, name: string}>
     * }
     */
    public function seed(string $password, bool $allowProduction = false): array
    {
        if (app()->isProduction() && ! $allowProduction) {
            throw new RuntimeException('Walkthrough school seeding on a live server requires --force.');
        }

        if (strlen($password) < 10) {
            throw new RuntimeException('Walkthrough password must be at least 10 characters.');
        }

        if (! Role::query()->where('key', Role::SCHOOL_ADMIN)->exists()) {
            throw new RuntimeException('Role catalog is missing. Run php artisan db:seed first.');
        }

        return DB::transaction(function () use ($password) {
            $this->context->forPlatform();

            $existing = School::query()->where('emis_number', self::EMIS_NUMBER)->first();
            $created = $existing === null;

            if ($existing) {
                $school = $existing;
            } else {
                $operator = User::query()->where('is_platform', true)->first();
                $result = $this->provisioner->onboard(
                    school: [
                        'name' => self::SCHOOL_NAME,
                        'district' => self::DISTRICT,
                        'emis_number' => self::EMIS_NUMBER,
                        'theme' => 'pearledu',
                    ],
                    levels: ['preprimary', 'primary'],
                    admin: ['full_name' => 'Grace Nakato', 'email' => 'admin@stkizito.test'],
                    operatorId: $operator?->id,
                );
                $school = $result['school'];
            }

            $school->forceFill([
                'status' => 'active',
                'activated_at' => $school->activated_at ?? now(),
                'district' => self::DISTRICT,
            ])->save();

            $accounts = $this->ensureStaff($school, $password);
            $year = $this->ensureYearAndTerms($school);
            $this->context->forSchool($school->id);
            $subjects = $this->ensureSubjects($school);
            $this->ensureTeachingAssignments($school, $year, $subjects, $accounts);
            $perClass = $this->ensureLearners($school, $year);
            $this->ensurePortalUsers($school, $password, $accounts);
            $this->ensureFeesAndAssessment($school, $year);
            $this->ensureSmsCredit($school);

            $school->forceFill(['setup_completed_at' => $school->setup_completed_at ?? now()])->save();

            $studentCount = Student::query()->where('school_id', $school->id)->count();

            return [
                'school' => $school->fresh(),
                'created' => $created,
                'students' => $studentCount,
                'per_class' => $perClass,
                'accounts' => $accounts,
            ];
        });
    }

    /**
     * Named logins this seed creates or refreshes (same password for all).
     *
     * @return list<array{role: string, name: string, email: string}>
     */
    public function accountDirectory(): array
    {
        $rows = [];
        foreach ($this->leaderProfiles() as $role => [$name, $email]) {
            $rows[] = ['role' => $role, 'name' => $name, 'email' => $email];
        }
        foreach ($this->classTeacherProfiles() as [$name, $email]) {
            $rows[] = ['role' => Role::CLASS_TEACHER, 'name' => $name, 'email' => $email];
        }
        foreach ($this->subjectTeacherProfiles() as $role => [$name, $email]) {
            $rows[] = ['role' => $role, 'name' => $name, 'email' => $email];
        }
        $rows[] = ['role' => Role::PARENT, 'name' => 'Patricia Parent', 'email' => 'parent@stkizito.test'];
        $rows[] = ['role' => Role::STUDENT, 'name' => 'P4 learner', 'email' => 'learner.p4@stkizito.test'];

        return $rows;
    }

    public function existing(): ?School
    {
        return School::query()->where('emis_number', self::EMIS_NUMBER)->first();
    }

    /**
     * @return list<array{role: string, email: string, name: string}>
     */
    private function ensureStaff(School $school, string $password): array
    {
        $this->context->forPlatform();

        $accounts = [];
        foreach ($this->leaderProfiles() as $roleKey => [$name, $email]) {
            $user = $this->upsertSchoolUser($name, $email, $password);
            $this->assignRole($school, $user, $roleKey);
            $accounts[] = ['role' => $roleKey, 'email' => $email, 'name' => $name, 'user' => $user];
        }

        foreach ($this->classTeacherProfiles() as $code => [$name, $email]) {
            $user = $this->upsertSchoolUser($name, $email, $password);
            $class = SchoolClass::query()
                ->where('school_id', $school->id)
                ->where('code', $code)
                ->firstOrFail();
            $this->assignRole($school, $user, Role::CLASS_TEACHER, $class->id);
            $accounts[] = ['role' => Role::CLASS_TEACHER.':'.$code, 'email' => $email, 'name' => $name, 'user' => $user, 'class_code' => $code];
        }

        foreach ($this->subjectTeacherProfiles() as $key => [$name, $email]) {
            $user = $this->upsertSchoolUser($name, $email, $password);
            $this->assignRole($school, $user, Role::SUBJECT_TEACHER);
            $accounts[] = ['role' => $key, 'email' => $email, 'name' => $name, 'user' => $user];
        }

        return $accounts;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function leaderProfiles(): array
    {
        return [
            Role::SCHOOL_ADMIN => ['Grace Nakato', 'admin@stkizito.test'],
            Role::DIRECTOR => ['Daniel Director', 'director@stkizito.test'],
            Role::HEAD_TEACHER => ['Helen Head', 'head@stkizito.test'],
            Role::DEPUTY_HEAD_TEACHER => ['Diana Deputy', 'deputy@stkizito.test'],
            Role::DIRECTOR_OF_STUDIES => ['Doris Studies', 'dos@stkizito.test'],
            Role::BURSAR => ['Bernard Bursar', 'bursar@stkizito.test'],
            Role::SECRETARY => ['Sarah Secretary', 'secretary@stkizito.test'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function classTeacherProfiles(): array
    {
        return [
            'BABY' => ['Carol Baby', 'ct.baby@stkizito.test'],
            'MID' => ['Mary Middle', 'ct.middle@stkizito.test'],
            'TOP' => ['Tina Top', 'ct.top@stkizito.test'],
            'P1' => ['Paul One', 'ct.p1@stkizito.test'],
            'P2' => ['Patricia Two', 'ct.p2@stkizito.test'],
            'P3' => ['Peter Three', 'ct.p3@stkizito.test'],
            'P4' => ['Phoebe Four', 'ct.p4@stkizito.test'],
            'P5' => ['Philip Five', 'ct.p5@stkizito.test'],
            'P6' => ['Priscilla Six', 'ct.p6@stkizito.test'],
            'P7' => ['Patrick Seven', 'ct.p7@stkizito.test'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function subjectTeacherProfiles(): array
    {
        return [
            'english' => ['Esther English', 'english@stkizito.test'],
            'maths' => ['Moses Maths', 'maths@stkizito.test'],
        ];
    }

    private function upsertSchoolUser(string $name, string $email, string $password): User
    {
        $user = User::query()->whereRaw('lower(email) = lower(?)', [$email])->first();
        if (! $user) {
            $user = new User;
        }

        $user->forceFill([
            'full_name' => $name,
            'email' => $email,
            'status' => 'active',
            'password' => $password,
            'gender' => $this->genderForName($name),
            'nin' => $user->getAttributes()['nin'] ?? $this->demoNin($email),
        ])->save();

        return $user;
    }

    private function assignRole(School $school, User $user, string $roleKey, ?int $classId = null): void
    {
        $roleId = Role::query()->where('key', $roleKey)->value('id');
        if (! $roleId) {
            throw new RuntimeException("Missing role [{$roleKey}].");
        }

        $assignment = RoleAssignment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'school_id' => $school->id,
            ],
            [
                'is_active' => true,
                'class_id' => $classId,
            ],
        );

        $assignment->forceFill([
            'is_active' => true,
            'ends_on' => null,
            'class_id' => $classId ?? $assignment->class_id,
        ])->save();
    }

    private function genderForName(string $name): string
    {
        $first = strtolower(strtok($name, ' ') ?: '');
        $female = ['grace', 'helen', 'diana', 'doris', 'sarah', 'carol', 'mary', 'tina', 'patricia', 'phoebe', 'priscilla', 'esther'];

        return in_array($first, $female, true) ? Gender::FEMALE : Gender::MALE;
    }

    private function demoNin(string $email): string
    {
        return 'CM'.strtoupper(substr(hash('sha256', $email), 0, 12));
    }

    private function ensureYearAndTerms(School $school): AcademicYear
    {
        $this->context->forSchool($school->id);

        $year = AcademicYear::query()->firstOrCreate(
            ['school_id' => $school->id, 'name' => '2026'],
            [
                'starts_on' => '2026-02-02',
                'ends_on' => '2026-11-27',
                'is_current' => true,
            ],
        );
        $year->forceFill(['is_current' => true])->save();
        AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('id', '!=', $year->id)
            ->update(['is_current' => false]);

        $terms = [
            [1, 'Term I', '2026-02-02', '2026-05-08'],
            [2, 'Term II', '2026-05-25', '2026-08-21'],
            [3, 'Term III', '2026-09-07', '2026-11-27'],
        ];
        foreach ($terms as [$sequence, $name, $start, $end]) {
            Term::query()->firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'sequence' => $sequence,
                ],
                [
                    'name' => $name,
                    'starts_on' => $start,
                    'ends_on' => $end,
                ],
            );
        }

        return $year->load('terms');
    }

    /**
     * @return array{english: Subject, maths: Subject, literacy: Subject, numeracy: Subject}
     */
    private function ensureSubjects(School $school): array
    {
        $make = function (string $name, string $code) use ($school): Subject {
            return Subject::query()->firstOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name],
            );
        };

        return [
            'english' => $make('English', 'ENG'),
            'maths' => $make('Mathematics', 'MATH'),
            'literacy' => $make('Literacy', 'LIT'),
            'numeracy' => $make('Numeracy', 'NUM'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $accounts
     * @param  array{english: Subject, maths: Subject, literacy: Subject, numeracy: Subject}  $subjects
     */
    private function ensureTeachingAssignments(School $school, AcademicYear $year, array $subjects, array $accounts): void
    {
        $english = $this->accountUser($accounts, 'english');
        $maths = $this->accountUser($accounts, 'maths');

        $preprimary = ['BABY', 'MID', 'TOP'];
        $primary = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];

        foreach (SchoolClass::query()->where('school_id', $school->id)->whereIn('code', $preprimary)->get() as $class) {
            $this->assignTeaching($school, $year, $english, $subjects['literacy'], $class);
            $this->assignTeaching($school, $year, $maths, $subjects['numeracy'], $class);
        }

        foreach (SchoolClass::query()->where('school_id', $school->id)->whereIn('code', $primary)->get() as $class) {
            $this->assignTeaching($school, $year, $english, $subjects['english'], $class);
            $this->assignTeaching($school, $year, $maths, $subjects['maths'], $class);
        }
    }

    private function assignTeaching(
        School $school,
        AcademicYear $year,
        User $teacher,
        Subject $subject,
        SchoolClass $class,
    ): void {
        $assignment = TeachingAssignment::query()->firstOrCreate(
            [
                'school_id' => $school->id,
                'user_id' => $teacher->id,
                'academic_year_id' => $year->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
            ],
            [
                'term_id' => null,
                'status' => 'active',
                'periods_per_week' => 5,
            ],
        );
        // Year-long so mark entry still works between terms.
        $assignment->forceFill([
            'term_id' => null,
            'status' => 'active',
        ])->save();
    }

    private function firstTerm(AcademicYear $year): ?Term
    {
        return Term::query()
            ->where('academic_year_id', $year->id)
            ->where('sequence', 1)
            ->first();
    }

    /**
     * @param  list<array<string, mixed>>  $accounts
     */
    private function accountUser(array $accounts, string $role): User
    {
        foreach ($accounts as $row) {
            if (($row['role'] ?? '') === $role) {
                return $row['user'];
            }
        }

        throw new RuntimeException("Walkthrough account [{$role}] is missing.");
    }

    /**
     * @return array<string, int>
     */
    private function ensureLearners(School $school, AcademicYear $year): array
    {
        $given = ['Aisha', 'Akello', 'Amon', 'Annet', 'Brian', 'Doreen', 'Faith', 'Isaac', 'Joan', 'Peter'];
        $family = ['Aine', 'Atim', 'Byaruhanga', 'Kato', 'Mbabazi', 'Namutebi', 'Ochieng', 'Okello', 'Tumusiime', 'Wasswa'];

        $perClass = [];
        $index = 0;
        foreach (self::ROSTER_CODES as $code) {
            $class = SchoolClass::query()
                ->where('school_id', $school->id)
                ->where('code', $code)
                ->firstOrFail();

            for ($n = 0; $n < self::STUDENTS_PER_CLASS; $n++) {
                $emis = 'WK'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
                $name = $given[$index % 10].' '.$family[intdiv($index, 10) % 10];
                $student = Student::query()->firstOrCreate(
                    ['school_id' => $school->id, 'emis_number' => $emis],
                    [
                        'full_name' => $name,
                        'class_id' => $class->id,
                        'status' => 'active',
                        'gender' => $index % 2 === 0 ? Gender::FEMALE : Gender::MALE,
                    ],
                );
                if (! $student->gender) {
                    $student->forceFill([
                        'gender' => $index % 2 === 0 ? Gender::FEMALE : Gender::MALE,
                    ])->save();
                }
                $this->lifecycle->enrollStudent($student, $class->id, $year->id);
                $index++;
            }

            $perClass[$code] = Student::query()
                ->where('school_id', $school->id)
                ->where('class_id', $class->id)
                ->count();
        }

        return $perClass;
    }

    /**
     * @param  list<array<string, mixed>>  $accounts
     */
    private function ensurePortalUsers(School $school, string $password, array &$accounts): void
    {
        $p4 = SchoolClass::query()->where('school_id', $school->id)->where('code', 'P4')->firstOrFail();
        $p1 = SchoolClass::query()->where('school_id', $school->id)->where('code', 'P1')->firstOrFail();
        $p4Student = Student::query()->where('school_id', $school->id)->where('class_id', $p4->id)->orderBy('id')->firstOrFail();
        $p1Student = Student::query()->where('school_id', $school->id)->where('class_id', $p1->id)->orderBy('id')->firstOrFail();

        $learner = $this->upsertSchoolUser($p4Student->full_name, 'learner.p4@stkizito.test', $password);
        $this->assignRole($school, $learner, Role::STUDENT);
        $p4Student->forceFill(['user_id' => $learner->id])->save();
        $accounts[] = ['role' => Role::STUDENT, 'email' => 'learner.p4@stkizito.test', 'name' => $p4Student->full_name, 'user' => $learner];

        $parent = $this->upsertSchoolUser('Patricia Parent', 'parent@stkizito.test', $password);
        $this->assignRole($school, $parent, Role::PARENT);
        foreach ([$p4Student, $p1Student] as $i => $child) {
            Guardianship::query()->firstOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $child->id,
                    'guardian_user_id' => $parent->id,
                ],
                [
                    'relationship' => 'mother',
                    'is_primary' => $i === 0,
                ],
            );
        }
        $accounts[] = ['role' => Role::PARENT, 'email' => 'parent@stkizito.test', 'name' => 'Patricia Parent', 'user' => $parent];
    }

    private function ensureFeesAndAssessment(School $school, AcademicYear $year): void
    {
        $term = $this->firstTerm($year);
        FeeStructure::query()->firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Term I tuition'],
            [
                'class_id' => null,
                'term_id' => $term?->id,
                'amount' => 350000,
                'currency' => 'UGX',
                'is_active' => true,
            ],
        );

        foreach (Student::query()->where('school_id', $school->id)->cursor() as $student) {
            $this->billing->assignDefaultStructures($student, $student->class_id, $term?->id);
        }

        AssessmentPeriod::query()->firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Term I beginning of term'],
            [
                'term_id' => $term?->id,
                'max_score' => 100,
                'status' => 'mark_entry_open',
                'is_locked' => false,
            ],
        );
    }

    private function ensureSmsCredit(School $school): void
    {
        $this->context->forPlatform();
        if ($this->sms->balance($school->id) > 0) {
            $this->context->forSchool($school->id);

            return;
        }

        $operator = User::query()->where('is_platform', true)->first();
        $this->sms->topUp($school, 500, $operator?->id, 'walkthrough-allocation');
        $this->context->forSchool($school->id);
    }
}
