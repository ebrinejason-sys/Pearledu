<?php

namespace App\Services\Academics;

use App\Models\AcademicYear;
use App\Models\AssessmentPeriod;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\Authorization\AssessmentScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Collection;

class CurrentAcademicContext
{
    public function __construct(
        private TenantContext $tenant,
        private AssessmentScope $assessmentScope,
    ) {}

    public function timezone(): string
    {
        return (string) config('app.timezone', 'Africa/Kampala');
    }

    public function today(): string
    {
        return now($this->timezone())->toDateString();
    }

    public function year(): ?AcademicYear
    {
        $schoolId = $this->tenant->schoolId();
        if (! $schoolId) {
            return null;
        }

        $current = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->first();
        if ($current) {
            return $current;
        }

        $today = $this->today();

        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->orderByDesc('starts_on')
            ->first()
            ?? AcademicYear::query()
                ->where('school_id', $schoolId)
                ->orderByDesc('starts_on')
                ->first();
    }

    public function term(?AcademicYear $year = null): ?Term
    {
        $year ??= $this->year();
        if (! $year) {
            return null;
        }

        $today = $this->today();
        $inWindow = $year->terms()
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->orderBy('sequence')
            ->first();

        return $inWindow ?? $year->terms()->orderBy('sequence')->first();
    }

    public function assessmentPeriod(?Term $term = null): ?AssessmentPeriod
    {
        $term ??= $this->term();
        $schoolId = $this->tenant->schoolId();
        if (! $schoolId) {
            return null;
        }

        $open = AssessmentPeriod::query()
            ->where('school_id', $schoolId)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->whereIn('status', ['mark_entry_open', 'draft', 'review'])
            ->orderByDesc('id')
            ->first();

        if ($open) {
            return $open;
        }

        return AssessmentPeriod::query()
            ->where('school_id', $schoolId)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->orderByDesc('id')
            ->first();
    }

    /** @return Collection<int, SchoolClass> */
    public function classesFor(User $user): Collection
    {
        $schoolId = $this->tenant->schoolId();
        abort_unless($schoolId, 404);

        $ids = $this->assessmentScope->viewableClassIds($user, $schoolId);
        $query = SchoolClass::query()->where('school_id', $schoolId)->orderBy('name');
        if ($ids !== null) {
            $query->whereIn('id', $ids ?: [0]);
        }

        return $query->get();
    }

    /** @return Collection<int, Subject> */
    public function subjectsFor(User $user, ?int $classId = null): Collection
    {
        $schoolId = $this->tenant->schoolId();
        abort_unless($schoolId, 404);

        $ids = $classId
            ? $this->assessmentScope->enterableSubjectIds($user, $schoolId, $classId)
            : null;

        $query = Subject::query()->where('school_id', $schoolId)->orderBy('name');
        if ($ids !== null) {
            $query->whereIn('id', $ids ?: [0]);
        }

        return $query->get();
    }
}
