<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class SchoolClass extends Model {
    use BelongsToSchool;
    protected $table = 'school_classes';
    protected $fillable = ['school_id','level','name','code'];
}
