<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Support\FeeKind;
use App\Support\Residency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeeStructure extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'class_id', 'term_id', 'name', 'kind', 'residency',
        'applies_to', 'amount', 'currency', 'is_active',
    ];

    protected $casts = ['amount' => 'decimal:2', 'is_active' => 'boolean'];

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Term, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** @return BelongsToMany<Student, $this> */
    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'fee_structure_students')
            ->withPivot('school_id')
            ->withTimestamps();
    }

    /**
     * @param  list<int|string>  $studentIds
     */
    public function syncLearners(int $schoolId, array $studentIds): void
    {
        $ids = Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->pluck('id');

        $payload = [];
        foreach ($ids as $id) {
            $payload[(int) $id] = ['school_id' => $schoolId];
        }

        $this->learners()->sync($payload);
    }

    public function isLearnerTargeted(): bool
    {
        return $this->applies_to === 'learners';
    }

    public function kindLabel(): string
    {
        return FeeKind::label((string) ($this->kind ?: FeeKind::TUITION));
    }

    public function residencyLabel(): string
    {
        return Residency::label($this->residency ?: Residency::ANY);
    }

    public function appliesToStudent(Student $student): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $classId = $student->class_id !== null ? (int) $student->class_id : 0;
        if ($this->class_id && (int) $this->class_id !== $classId) {
            return false;
        }

        $needed = $this->residency ?: Residency::ANY;
        if ($needed !== Residency::ANY && Residency::normalize($student->residency) !== $needed) {
            return false;
        }

        if ($this->isLearnerTargeted()) {
            return $this->learners()->where('students.id', $student->id)->exists();
        }

        return true;
    }
}
