<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtAttempt extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'school_id', 'exam_id', 'student_id', 'user_id',
        'score', 'max_score', 'started_at', 'submitted_at', 'status',
    ];
    protected $casts = [
        'score' => 'decimal:2', 'max_score' => 'decimal:2',
        'started_at' => 'datetime', 'submitted_at' => 'datetime',
    ];

    public function exam(): BelongsTo { return $this->belongsTo(CbtExam::class, 'exam_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function answers(): HasMany { return $this->hasMany(CbtAttemptAnswer::class, 'attempt_id'); }
}
