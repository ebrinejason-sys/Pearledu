<?php

namespace App\Models;

use App\Models\Concerns\ScopedByTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use ScopedByTenant;

    public $timestamps = false;

    protected $fillable = ['school_id', 'actor_id', 'action', 'entity_type', 'entity_id', 'metadata', 'ip_address', 'created_at'];

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
