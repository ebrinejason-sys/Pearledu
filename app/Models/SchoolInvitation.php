<?php

namespace App\Models;

use App\Models\Concerns\ScopedByTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolInvitation extends Model
{
    use ScopedByTenant;

    protected $fillable = ['school_id', 'user_id', 'email', 'phone', 'role_key', 'token_hash', 'expires_at', 'accepted_at', 'invited_by', 'batch_id'];

    protected $casts = ['expires_at' => 'datetime', 'accepted_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isAccepted(): bool
    {
        return ! is_null($this->accepted_at);
    }
}
