<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'academic_year_id', 'name', 'sequence', 'starts_on', 'ends_on'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
