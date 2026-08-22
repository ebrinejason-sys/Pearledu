<?php

namespace App\Models;

use App\Services\Authorization\PermissionResolver;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Global identity. Schools + roles come from role_assignments (one person, many roles).
 *
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['full_name', 'email', 'phone', 'password', 'status', 'preferred_theme', 'avatar_path', 'last_login_at', 'last_seen_at'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_platform' => 'boolean',
            'two_factor_secret' => 'encrypted', 'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->roleAssignments()->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()));
    }

    public function guardianships(): HasMany
    {
        return $this->hasMany(Guardianship::class, 'guardian_user_id');
    }

    public function isPlatformOperator(): bool
    {
        return $this->is_platform === true;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function hasRoleInSchool(string $roleKey, int $schoolId): bool
    {
        return $this->activeAssignments()->where('school_id', $schoolId)
            ->whereHas('role', fn ($q) => $q->where('key', $roleKey))->exists();
    }

    public function permissionsForSchool(int $schoolId): array
    {
        return app(PermissionResolver::class)->resolve($this, $schoolId);
    }

    /** Active platform role key (null when misconfigured — never auto-healed). */
    public function platformRoleKey(): ?string
    {
        if (! $this->is_platform) {
            return null;
        }

        // Platform role rows have school_id NULL; school-pinned RLS/Eloquent scopes hide them.
        $ctx = app(TenantContext::class);
        $previousSchoolId = $ctx->schoolId();
        $previousPlatform = $ctx->isPlatform();
        if (! $previousPlatform) {
            $ctx->forPlatform();
        }

        try {
            return $this->activeAssignments()
                ->whereNull('school_id')
                ->whereHas('role', fn ($q) => $q->where('scope', 'platform'))
                ->with('role')
                ->first()
                ?->role
                ?->key;
        } finally {
            if (! $previousPlatform) {
                if ($previousSchoolId !== null) {
                    $ctx->forSchool($previousSchoolId);
                } else {
                    $ctx->clear();
                }
            }
        }
    }

    /** @return list<string> */
    public function platformPermissions(): array
    {
        return app(PermissionResolver::class)->resolvePlatform($this);
    }

    public function hasPlatformPermission(string $permission): bool
    {
        $perms = $this->platformPermissions();
        if (in_array('*', $perms, true)) {
            return true;
        }

        return in_array($permission, $perms, true);
    }

    public function schoolsForUser()
    {
        return School::whereIn('id', $this->activeAssignments()->whereNotNull('school_id')->pluck('school_id'))->get();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function avatarInitial(): string
    {
        return strtoupper(substr($this->full_name ?: '?', 0, 1));
    }

    /**
     * First active school membership on an active tenant
     * (suspended/archived schools are not usable at /login → /home).
     */
    public function primarySchool(): ?School
    {
        $schoolId = $this->activeAssignments()
            ->whereNotNull('school_id')
            ->whereHas('school', fn ($q) => $q->where('status', 'active'))
            ->value('school_id');

        return $schoolId ? School::find($schoolId) : null;
    }

    /** Absolute app home for this user (same host; school from session/membership). */
    public function appHomeUrl(): string
    {
        if ($this->isPlatformOperator()) {
            return route('platform.dashboard');
        }

        return route('app.home');
    }
}
