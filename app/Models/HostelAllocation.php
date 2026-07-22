<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAllocation extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'room_id', 'student_id', 'starts_on', 'ends_on'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
