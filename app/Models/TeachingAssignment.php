<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class TeachingAssignment extends Model {
    use BelongsToSchool;
    protected $fillable = ['assignment_id','school_id','subject_id','class_id'];
}
