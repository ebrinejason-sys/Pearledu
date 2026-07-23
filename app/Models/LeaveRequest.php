<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'user_id', 'starts_on', 'ends_on', 'reason', 'status'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
