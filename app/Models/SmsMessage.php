<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class SmsMessage extends Model {
    use BelongsToSchool;
    protected $fillable = ['school_id','to_phone','body','segments','cost_credits','category','status','provider_ref','error','created_by'];
    protected $casts = ['segments'=>'integer','cost_credits'=>'integer'];
}
