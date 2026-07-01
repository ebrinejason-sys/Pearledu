<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SchoolInvitation extends Model {
    protected $fillable = ['school_id','user_id','email','phone','role_key','token_hash','expires_at','accepted_at','invited_by'];
    protected $casts = ['expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    public function isExpired(): bool { return $this->expires_at->isPast(); }
    public function isAccepted(): bool { return ! is_null($this->accepted_at); }
}
