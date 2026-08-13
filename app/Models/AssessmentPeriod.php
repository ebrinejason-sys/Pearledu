<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentPeriod extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'term_id', 'name', 'max_score', 'is_locked',
        'status', 'published_at', 'locked_at', 'grading_scheme_id',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'is_locked' => 'boolean',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

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
