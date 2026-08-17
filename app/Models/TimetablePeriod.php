<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetablePeriod extends Model
{
    use BelongsToSchool;

    public const KINDS = [
        'class' => 'Class period',
        'breakfast' => 'Breakfast',
        'break' => 'Break',
        'lunch' => 'Lunch',
        'supper' => 'Supper / evening meal',
        'evening' => 'Evening / prep',
        'sports' => 'Sports / games',
        'assembly' => 'Assembly',
        'other' => 'Other fixed block',
    ];

    protected $fillable = ['school_id', 'name', 'kind', 'starts_at', 'ends_at', 'sequence'];

    public function isLessonPeriod(): bool
    {
        return ($this->kind ?: 'class') === 'class';
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst((string) $this->kind);
    }
}
