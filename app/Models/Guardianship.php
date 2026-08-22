<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Guardianship extends Model {
    use BelongsToSchool;
    protected $fillable = ['student_id','guardian_user_id','school_id','relationship','is_primary'];
    protected $casts = ['is_primary' => 'boolean'];
    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }
}
