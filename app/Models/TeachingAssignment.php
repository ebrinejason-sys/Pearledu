<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingAssignment extends Model
{
    use BelongsToSchool;
    protected $fillable = ['assignment_id', 'school_id', 'subject_id', 'class_id'];

    public function roleAssignment(): BelongsTo
    {
        return $this->belongsTo(RoleAssignment::class, 'assignment_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
