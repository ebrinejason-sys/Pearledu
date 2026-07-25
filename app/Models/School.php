<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class School extends Model
{
    protected $fillable = [
        'name', 'slug', 'emis_number', 'district', 'theme', 'motto', 'badge_text',
        'logo_path', 'address', 'status', 'created_by',
        'deletion_scheduled_at', 'deletion_requested_by', 'deletion_reason',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'deletion_scheduled_at' => 'datetime',
    ];

    public function offerings(): HasMany { return $this->hasMany(SchoolOffering::class); }
    public function classes(): HasMany { return $this->hasMany(SchoolClass::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function smsLedger(): HasMany { return $this->hasMany(SmsCreditEntry::class); }
    public function invitations(): HasMany { return $this->hasMany(SchoolInvitation::class); }

    public function isDeletionScheduled(): bool
    {
        return $this->status === 'deletion_scheduled';
    }

    public function purgeEligibleAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->deletion_scheduled_at) {
            return null;
        }

        return $this->deletion_scheduled_at->copy()->addDays(
            \App\Services\Provisioning\SchoolDeletionService::RETENTION_DAYS
        );
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function badgeLabel(): string
    {
        return $this->badge_text ?: strtoupper(substr($this->name, 0, 3));
    }

    /** Optional legacy subdomain URL (not required for login or day-to-day use). */
    public function subdomainUrl(): string {
        return 'https://'.$this->slug.'.'.config('tenancy.base_domain');
    }

    /** Shared portal URL — all schools use pearledu.*; data scopes by tenant id. */
    public function portalUrl(): string {
        $host = config('tenancy.pearledu_landing_host');

        return 'https://'.$host;
    }

    /**
     * Tenant id for this school.
     * Onboard creates the school row; every user/role/student row links via school_id (= this id).
     * RLS + app scopes enforce that only that school’s members (or platform operators) see its data.
     */
    public function tenantId(): int
    {
        return (int) $this->id;
    }

    public function smsBalance(): int {
        return (int) ($this->smsLedger()->orderByDesc('id')->value('balance_after') ?? 0);
    }

    public function provisioningState(): string {
        if ($this->activated_at) return 'ready';
        return $this->invitations()->whereNotNull('accepted_at')->exists() ? 'invite_accepted' : 'pending_invite';
    }
}
