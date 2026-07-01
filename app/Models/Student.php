<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Student extends Model {
    use BelongsToSchool, SoftDeletes;
    protected $fillable = ['school_id','user_id','full_name','emis_number','lin','nin','class_id','status'];
    protected $casts = ['lin' => 'encrypted', 'nin' => 'encrypted'];   // DPPA: encrypted at rest

    public function guardianships(): HasMany { return $this->hasMany(Guardianship::class); }

    public function getNinAttribute($v): ?string {
        $p = $v !== null ? $this->castAttribute('nin', $v) : null;
        if ($p !== null) app(AuditLogger::class)->record('sensitive.read', $this, ['field'=>'nin']);
        return $p;
    }
    public function getLinAttribute($v): ?string {
        $p = $v !== null ? $this->castAttribute('lin', $v) : null;
        if ($p !== null) app(AuditLogger::class)->record('sensitive.read', $this, ['field'=>'lin']);
        return $p;
    }
}
