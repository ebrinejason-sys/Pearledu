<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSalary extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'user_id', 'amount', 'currency', 'effective_on', 'notes'];

    protected function casts(): array
    {
        return ['effective_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
