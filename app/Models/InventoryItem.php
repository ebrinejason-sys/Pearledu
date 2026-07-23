<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'name', 'sku', 'quantity', 'location'];
}
