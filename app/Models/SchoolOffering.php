<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class SchoolOffering extends Model {
    use BelongsToSchool;
    protected $fillable = ['school_id','level'];
    public const LEVELS = ['preprimary','primary','lower_secondary','upper_secondary'];
}
