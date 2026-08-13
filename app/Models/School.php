<?php

namespace App\Models;

use App\Services\Provisioning\SchoolDeletionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class School extends Model
{
    /** ISO weekdays: 1 = Monday … 7 = Sunday */
    public const WEEK_DAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    protected $fillable = [
        'name', 'slug', 'emis_number', 'district', 'theme', 'motto', 'badge_text',
        'logo_path', 'address', 'status', 'created_by',
        'schoolpay_enabled', 'schoolpay_school_code', 'schoolpay_api_password',
        'emis_enabled',
        'enabled_modules', 'report_settings', 'schedule_settings', 'setup_completed_at',
        'deletion_scheduled_at', 'deletion_requested_by', 'deletion_reason',
    ];

    protected $hidden = [
        'schoolpay_api_password',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'deletion_scheduled_at' => 'datetime',
        'schoolpay_enabled' => 'boolean',
        'schoolpay_api_password' => 'encrypted',
        'emis_enabled' => 'boolean',
        'enabled_modules' => 'array',
        'report_settings' => 'array',
        'schedule_settings' => 'array',
        'setup_completed_at' => 'datetime',
    ];

    /** @return list<int> ISO weekdays 1=Mon … 7=Sun */
    public function teachingDays(): array
    {
        $days = $this->schedule_settings['teaching_days'] ?? [1, 2, 3, 4, 5];
        if (! is_array($days) || $days === []) {
            return [1, 2, 3, 4, 5];
        }

        return array_values(array_unique(array_map('intval', $days)));
    }

    public function schoolPayEnabled(): bool
    {
        return (bool) $this->schoolpay_enabled;
    }

    public function schoolPayConfigured(): bool
    {
        return $this->schoolPayEnabled()
            && filled($this->schoolpay_school_code)
            && filled($this->schoolpay_api_password);
    }

    public function emisEnabled(): bool
    {
        return (bool) $this->emis_enabled;
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(SchoolOffering::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function smsLedger(): HasMany
    {
        return $this->hasMany(SmsCreditEntry::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SchoolInvitation::class);
    }

    public function isDeletionScheduled(): bool
    {
        return $this->status === 'deletion_scheduled';
    }

    public function purgeEligibleAt(): ?Carbon
    {
        if (! $this->deletion_scheduled_at) {
            return null;
        }

        return $this->deletion_scheduled_at->copy()->addDays(
            SchoolDeletionService::RETENTION_DAYS
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
    public function subdomainUrl(): string
    {
        return 'https://'.$this->slug.'.'.config('tenancy.base_domain');
    }

    /** Shared portal URL — all schools use pearledu.*; data scopes by tenant id. */
    public function portalUrl(): string
    {
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

    public function smsBalance(): int
    {
        return (int) ($this->smsLedger()->orderByDesc('id')->value('balance_after') ?? 0);
    }

    public function provisioningState(): string
    {
        if ($this->activated_at) {
            return 'ready';
        }

        return $this->invitations()->whereNotNull('accepted_at')->exists() ? 'invite_accepted' : 'pending_invite';
    }
}
