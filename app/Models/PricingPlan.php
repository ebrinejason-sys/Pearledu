<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** PearlEdu landing-page pricing tier. Platform-managed, publicly readable. */
class PricingPlan extends Model
{
    protected $fillable = [
        'name','tagline','price','currency','billing_period',
        'features','is_highlighted','is_active','sort_order',
    ];

    protected function casts(): array {
        return [
            'features' => 'array',
            'is_highlighted' => 'boolean',
            'is_active' => 'boolean',
            'price' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true); }
}
