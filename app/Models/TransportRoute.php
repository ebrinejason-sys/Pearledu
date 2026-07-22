<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'name', 'vehicle', 'fee'];
    protected $casts = ['fee' => 'decimal:2'];
}
