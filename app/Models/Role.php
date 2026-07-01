<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Role extends Model {
    public $timestamps = false;
    protected $fillable = ['key','scope','label'];
    public const SCHOOL = ['school_admin','director','head_teacher','bursar','class_teacher','subject_teacher','parent','student'];
}
