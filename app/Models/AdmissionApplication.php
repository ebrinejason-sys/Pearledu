<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionApplication extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'school_id', 'applicant_name', 'guardian_name', 'guardian_email',
        'guardian_phone', 'requested_class_id', 'status', 'notes',
    ];

    public function requestedClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'requested_class_id');
    }
}
