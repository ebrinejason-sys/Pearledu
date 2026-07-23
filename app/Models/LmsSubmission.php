<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsSubmission extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'school_id', 'assignment_id', 'student_id', 'user_id',
        'body', 'url', 'score', 'feedback', 'submitted_at', 'graded_at', 'graded_by',
    ];
    protected $casts = [
        'score' => 'decimal:2',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function assignment(): BelongsTo { return $this->belongsTo(LmsAssignment::class, 'assignment_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
