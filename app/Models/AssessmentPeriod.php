<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentPeriod extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'term_id', 'name', 'max_score', 'is_locked'];
    protected $casts = ['max_score' => 'decimal:2', 'is_locked' => 'boolean'];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
