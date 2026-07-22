<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtExam extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'school_id', 'subject_id', 'class_id', 'title', 'duration_minutes', 'is_published',
    ];
    protected $casts = ['is_published' => 'boolean'];

    public function questions(): HasMany
    {
        return $this->hasMany(CbtQuestion::class, 'exam_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
