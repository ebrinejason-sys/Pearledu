<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SmsSetting extends Model {
    protected $fillable = ['provider','sender_id','segment_credits','is_enabled'];
    protected $casts = ['is_enabled' => 'boolean', 'segment_credits' => 'integer'];
    public static function current(): self { return static::query()->firstOrCreate([]); }
}
