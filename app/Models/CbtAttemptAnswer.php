<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtAttemptAnswer extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'school_id', 'attempt_id', 'question_id', 'chosen_key', 'is_correct', 'points_awarded',
    ];
    protected $casts = ['is_correct' => 'boolean', 'points_awarded' => 'decimal:2'];

    public function attempt(): BelongsTo { return $this->belongsTo(CbtAttempt::class, 'attempt_id'); }
    public function question(): BelongsTo { return $this->belongsTo(CbtQuestion::class, 'question_id'); }
}
