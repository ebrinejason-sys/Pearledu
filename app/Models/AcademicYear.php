<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AcademicYear extends Model {
    use BelongsToSchool;
    protected $fillable = ['school_id','name','starts_on','ends_on','is_current'];
    protected $casts = ['starts_on'=>'date','ends_on'=>'date','is_current'=>'bool'];
    public function terms(): HasMany { return $this->hasMany(Term::class); }
}
