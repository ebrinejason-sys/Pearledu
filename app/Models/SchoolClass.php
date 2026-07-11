<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model {
    use BelongsToSchool;
    protected $table = 'school_classes';
    protected $fillable = ['school_id','level','name','code'];

    public function students(): HasMany { return $this->hasMany(Student::class, 'class_id'); }
}
