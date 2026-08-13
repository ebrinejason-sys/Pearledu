<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScheme extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'kind', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function bands(): HasMany
    {
        return $this->hasMany(GradingSchemeBand::class)->orderBy('sort_order');
    }
}
