<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSlot extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'academic_year_id', 'day_of_week', 'period_id',
        'class_id', 'subject_id', 'teacher_id', 'room_id',
    ];

    /** @return BelongsTo<TimetablePeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class, 'period_id');
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
