<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class SchoolDomain extends Model {
    use BelongsToSchool;
    protected $fillable = ['school_id','domain','verified_at'];
    protected $casts = ['verified_at' => 'datetime'];
}
