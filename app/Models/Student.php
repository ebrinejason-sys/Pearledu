<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id', 'user_id', 'full_name', 'gender', 'date_of_birth', 'residency', 'nationality',
        'religion', 'home_address', 'medical_notes',
        'emis_number', 'schoolpay_payment_code',
        'lin', 'nin', 'photo_path', 'class_id', 'status',
    ];

    protected $casts = [
        'lin' => 'encrypted',
        'nin' => 'encrypted',
        'date_of_birth' => 'date',
    ];

    /** @return HasMany<Guardianship, $this> */
    public function guardianships(): HasMany
    {
        return $this->hasMany(Guardianship::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function currentEnrollment(): ?Enrollment
    {
        $yearId = AcademicYear::query()
            ->where('school_id', $this->school_id)
            ->where('is_current', true)
            ->value('id');

        $active = $this->enrollments()->where('status', 'active')->with('schoolClass');
        if ($yearId) {
            $match = (clone $active)->where('academic_year_id', $yearId)->first();
            if ($match instanceof Enrollment) {
                return $match;
            }
        }

        $fallback = $active->orderByDesc('id')->first();

        return $fallback instanceof Enrollment ? $fallback : null;
    }

    public function currentClass(): ?SchoolClass
    {
        $fromEnrollment = $this->currentEnrollment()?->schoolClass;
        if ($fromEnrollment instanceof SchoolClass) {
            return $fromEnrollment;
        }

        $class = $this->schoolClass;

        return $class instanceof SchoolClass ? $class : null;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function getNinAttribute($v): ?string
    {
        $p = $v !== null ? $this->castAttribute('nin', $v) : null;
        if ($p !== null) {
            app(AuditLogger::class)->record('sensitive.read', $this, ['field' => 'nin']);
        }

        return $p;
    }

    public function getLinAttribute($v): ?string
    {
        $p = $v !== null ? $this->castAttribute('lin', $v) : null;
        if ($p !== null) {
            app(AuditLogger::class)->record('sensitive.read', $this, ['field' => 'lin']);
        }

        return $p;
    }

    public function photoUrl(): ?string
    {
        if ($this->photo_path) {
            return asset('storage/'.$this->photo_path);
        }

        $user = $this->user;

        return $user instanceof User ? $user->avatarUrl() : null;
    }

    /** Presence only — does not decrypt or audit. */
    public function hasLinOnFile(): bool
    {
        return filled($this->attributes['lin'] ?? null);
    }

    /** Presence only — does not decrypt or audit. */
    public function hasNinOnFile(): bool
    {
        return filled($this->attributes['nin'] ?? null);
    }

    public function sexLetter(): string
    {
        return match ($this->gender) {
            'male' => 'M',
            'female' => 'F',
            default => '—',
        };
    }

    public function ageYears(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
