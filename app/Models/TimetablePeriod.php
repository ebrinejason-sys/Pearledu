<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetablePeriod extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'name', 'starts_at', 'ends_at', 'sequence'];
}
