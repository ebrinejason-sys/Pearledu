<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class SmsCreditEntry extends Model {
    use BelongsToSchool;
    public $timestamps = false;
    protected $table = 'sms_credit_ledger';
    protected $fillable = ['school_id','delta','balance_after','reason','reference','actor_id','created_at'];
    protected $casts = ['delta'=>'integer','balance_after'=>'integer','created_at'=>'datetime'];
}
