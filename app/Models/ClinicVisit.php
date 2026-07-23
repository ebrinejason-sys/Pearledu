<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicVisit extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'student_id', 'visited_at', 'complaint', 'notes', 'recorded_by'];
    protected $casts = ['visited_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
