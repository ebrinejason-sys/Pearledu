<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'name', 'vehicle', 'fee'];
    protected $casts = ['fee' => 'decimal:2'];

    public function allocations(): HasMany
    {
        return $this->hasMany(TransportAllocation::class, 'route_id');
    }
}
