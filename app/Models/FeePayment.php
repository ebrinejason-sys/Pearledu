<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'invoice_id', 'amount', 'method', 'provider_ref',
        'external_reference', 'schoolpay_reference', 'provider_txn_id', 'channel_name',
        'status', 'reverses_payment_id', 'recorded_by', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
