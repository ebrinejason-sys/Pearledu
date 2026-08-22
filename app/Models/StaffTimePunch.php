<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $punched_at
 */
class StaffTimePunch extends Model
{
    use BelongsToSchool;

    public const IN = 'in';

    public const OUT = 'out';

    protected $fillable = [
        'school_id', 'user_id', 'recorded_by', 'direction', 'source', 'punched_at',
    ];

    protected function casts(): array
    {
        return ['punched_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
