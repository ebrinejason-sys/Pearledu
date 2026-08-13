<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingSchemeBand extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'grading_scheme_id', 'min_score', 'max_score',
        'grade', 'remark', 'points', 'sort_order',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'points' => 'integer',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(GradingScheme::class, 'grading_scheme_id');
    }
}
