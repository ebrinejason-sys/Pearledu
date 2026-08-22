<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'fee_structure_id', 'reference',
        'amount', 'balance', 'status', 'due_on',
    ];

    protected $casts = ['amount' => 'decimal:2', 'balance' => 'decimal:2', 'due_on' => 'date'];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<FeeStructure, $this> */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    /** @return HasMany<FeePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'invoice_id');
    }

    /** @return HasMany<FeeAdjustment, $this> */
    public function adjustments(): HasMany
    {
        return $this->hasMany(FeeAdjustment::class, 'invoice_id');
    }
}
