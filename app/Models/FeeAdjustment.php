<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeAdjustment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'invoice_id', 'type', 'amount', 'reason', 'created_by',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }
}
