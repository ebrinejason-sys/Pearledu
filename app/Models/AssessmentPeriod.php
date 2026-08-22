<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Support\AssessmentSet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class AssessmentPeriod extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'term_id', 'name', 'kind', 'max_score', 'entry_deadline', 'is_locked',
        'status', 'published_at', 'locked_at', 'grading_scheme_id',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'is_locked' => 'boolean',
        'entry_deadline' => 'date',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function kindLabel(): string
    {
        return AssessmentSet::label($this->kind, $this->name);
    }

    public function kindShort(): string
    {
        return AssessmentSet::short($this->kind, $this->name);
    }

    public function entryDeadlinePassed(?Carbon $on = null): bool
    {
        if (! $this->entry_deadline) {
            return false;
        }

        $on ??= now(config('app.timezone'));

        return $on->toDateString() > $this->entry_deadline->toDateString();
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    public function gradingScheme(): BelongsTo
    {
        return $this->belongsTo(GradingScheme::class, 'grading_scheme_id');
    }
}
