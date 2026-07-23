<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportAllocation extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'route_id', 'student_id', 'starts_on', 'ends_on'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
