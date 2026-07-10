<?php
namespace App\Models;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Global identity. Schools + roles come from role_assignments (one person, many roles). */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['full_name','email','phone','password','status','is_platform','preferred_theme','last_login_at'];
    protected $hidden = ['password','remember_token','two_factor_secret'];

    protected function casts(): array {
        return ['password' => 'hashed', 'is_platform' => 'boolean',
                'two_factor_secret' => 'encrypted', 'two_factor_confirmed_at' => 'datetime',
                'last_login_at' => 'datetime'];
    }

    public function roleAssignments(): HasMany { return $this->hasMany(RoleAssignment::class); }

    public function activeAssignments() {
        return $this->roleAssignments()->where('is_active', true)
            ->whereDate('starts_on', '<=', now())
            ->where(fn($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()));
    }

    public function guardianships(): HasMany { return $this->hasMany(Guardianship::class, 'guardian_user_id'); }

    public function isPlatformOperator(): bool { return $this->is_platform === true; }
    public function hasTwoFactorEnabled(): bool { return ! is_null($this->two_factor_confirmed_at); }

    public function hasRoleInSchool(string $roleKey, int $schoolId): bool {
        return $this->activeAssignments()->where('school_id', $schoolId)
            ->whereHas('role', fn($q) => $q->where('key', $roleKey))->exists();
    }

    public function permissionsForSchool(int $schoolId): array {
        return app(PermissionResolver::class)->resolve($this, $schoolId);
    }

    public function schoolsForUser() {
        return School::whereIn('id', $this->activeAssignments()->whereNotNull('school_id')->pluck('school_id'))->get();
    }
}
