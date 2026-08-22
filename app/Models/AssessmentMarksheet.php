<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentMarksheet extends Model
{
    use BelongsToSchool;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    protected $fillable = [
        'school_id',
        'assessment_period_id',
        'class_id',
        'subject_id',
        'status',
        'submitted_by',
        'submitted_at',
        'verified_by',
        'verified_at',
        'upload_revoked_at',
        'upload_revoked_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'upload_revoked_at' => 'datetime',
    ];

    public function uploadRevoked(): bool
    {
        return $this->upload_revoked_at !== null;
    }

    /** @return BelongsTo<AssessmentPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class, 'assessment_period_id');
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
}
