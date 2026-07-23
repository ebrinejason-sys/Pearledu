<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'name', 'capacity'];
}
