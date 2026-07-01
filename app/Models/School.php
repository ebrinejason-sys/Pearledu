<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class School extends Model
{
    protected $fillable = ['name','slug','emis_number','district','theme','status','created_by'];

    public function offerings(): HasMany { return $this->hasMany(SchoolOffering::class); }
    public function classes(): HasMany { return $this->hasMany(SchoolClass::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function smsLedger(): HasMany { return $this->hasMany(SmsCreditEntry::class); }

    public function subdomainUrl(): string {
        return 'https://'.$this->slug.'.'.config('tenancy.base_domain');
    }

    public function smsBalance(): int {
        return (int) ($this->smsLedger()->orderByDesc('id')->value('balance_after') ?? 0);
    }
}
