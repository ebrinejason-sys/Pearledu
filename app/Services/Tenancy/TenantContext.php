<?php
namespace App\Services\Tenancy;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/** Single source of truth for request scope; mirrors it into Postgres for RLS. */
class TenantContext
{
    /** Session key for the school the user is working in (shared-host tenancy). */
    public const SESSION_SCHOOL_ID = 'tenant.school_id';

    private ?int $schoolId = null;
    private bool $isPlatform = false;

    public function forSchool(int $id): void { $this->schoolId = $id; $this->isPlatform = false; $this->apply(); }
    public function forPlatform(): void { $this->isPlatform = true; $this->apply(); }
    public function forPlatformInSchool(int $id): void { $this->schoolId = $id; $this->isPlatform = false; $this->apply(); }
    public function clear(): void { $this->schoolId = null; $this->isPlatform = false; $this->apply(); }

    public function schoolId(): ?int { return $this->schoolId; }
    public function isPlatform(): bool { return $this->isPlatform; }
    public function hasSchool(): bool { return $this->schoolId !== null; }
    public function school(): ?School { return $this->schoolId ? School::find($this->schoolId) : null; }

    public function apply(): void {
        DB::statement("select set_config('app.is_platform', ?, false)", [$this->isPlatform ? 'on' : 'off']);
        DB::statement("select set_config('app.current_school_id', ?, false)", [(string) ($this->schoolId ?? '')]);
    }
}
