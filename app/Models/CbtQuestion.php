<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtQuestion extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'exam_id', 'prompt', 'choices', 'correct_key', 'points'];
    protected $casts = ['choices' => 'array', 'points' => 'decimal:2'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(CbtExam::class, 'exam_id');
    }
}
