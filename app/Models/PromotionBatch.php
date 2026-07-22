<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionBatch extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'from_year_id', 'to_year_id', 'status', 'created_by', 'committed_at'];
    protected $casts = ['committed_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(PromotionItem::class, 'batch_id');
    }

    public function fromYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_year_id');
    }

    public function toYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_year_id');
    }
}
