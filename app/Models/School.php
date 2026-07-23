<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class School extends Model
{
    protected $fillable = ['name','slug','emis_number','district','theme','motto','badge_text','logo_path','address','status','created_by'];
    protected $casts = ['activated_at' => 'datetime'];

    public function offerings(): HasMany { return $this->hasMany(SchoolOffering::class); }
    public function classes(): HasMany { return $this->hasMany(SchoolClass::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function smsLedger(): HasMany { return $this->hasMany(SmsCreditEntry::class); }
    public function invitations(): HasMany { return $this->hasMany(SchoolInvitation::class); }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function badgeLabel(): string
    {
        return $this->badge_text ?: strtoupper(substr($this->name, 0, 3));
    }

    public function subdomainUrl(): string {
        return 'https://'.$this->slug.'.'.config('tenancy.base_domain');
    }

    public function smsBalance(): int {
        return (int) ($this->smsLedger()->orderByDesc('id')->value('balance_after') ?? 0);
    }

    public function provisioningState(): string {
        if ($this->activated_at) return 'ready';
        return $this->invitations()->whereNotNull('accepted_at')->exists() ? 'invite_accepted' : 'pending_invite';
    }
}
