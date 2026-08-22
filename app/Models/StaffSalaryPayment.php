<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSalaryPayment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'recorded_by', 'amount', 'currency', 'paid_on', 'method', 'reference', 'notes',
    ];

    protected function casts(): array
    {
        return ['paid_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
