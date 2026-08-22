<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureStudent extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'fee_structure_id', 'student_id'];

    /** @return BelongsTo<FeeStructure, $this> */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
