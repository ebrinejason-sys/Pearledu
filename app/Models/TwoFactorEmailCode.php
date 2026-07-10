<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorEmailCode extends Model
{
    protected $fillable = ['user_id', 'code_hash', 'expires_at', 'used_at', 'ip_address'];

    protected function casts(): array {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isValid(): bool {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }
}
