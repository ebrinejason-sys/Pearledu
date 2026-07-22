<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsAssignment extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'school_id', 'subject_id', 'class_id', 'title', 'instructions', 'due_at', 'created_by',
    ];
    protected $casts = ['due_at' => 'datetime'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
