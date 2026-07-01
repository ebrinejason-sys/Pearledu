<?php
namespace App\Models\Concerns;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $ctx = app(TenantContext::class);
        if ($ctx->isPlatform()) return;                         // unconstrained at app layer; RLS still governs
        $builder->where($model->qualifyColumn('school_id'), $ctx->schoolId() ?? -1); // fail-closed
    }
}
