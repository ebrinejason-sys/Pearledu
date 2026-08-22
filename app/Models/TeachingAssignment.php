<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TeachingAssignment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'user_id',
        'academic_year_id',
        'term_id',
        'subject_id',
        'class_id',
        'starts_on',
        'ends_on',
        'status',
        'periods_per_week',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'periods_per_week' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** Active and within optional effective dates. */
    public function scopeEffective(Builder $query, Carbon|string|null $on = null): Builder
    {
        $on = Carbon::parse($on ?? now())->toDateString();

        return $query->where('status', 'active')
            ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $on))
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $on));
    }

    /** Limit to the school's current academic year (empty if none marked current). */
    public function scopeForCurrentYear(Builder $query, int $schoolId): Builder
    {
        $yearId = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->value('id');

        if (! $yearId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('academic_year_id', $yearId);
    }
}
