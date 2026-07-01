<?php
namespace App\Models\Concerns;
use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new TenantScope);
        static::creating(function ($m) {
            if (empty($m->school_id)) $m->school_id = app(TenantContext::class)->schoolId();
        });
    }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
}
