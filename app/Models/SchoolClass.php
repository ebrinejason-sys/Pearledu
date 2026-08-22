<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use BelongsToSchool;

    protected $table = 'school_classes';

    protected $fillable = ['school_id', 'level', 'name', 'stream', 'code'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Parallel streams of the same class name and level (e.g. P.5 East / P.5 West).
     *
     * @return Collection<int, self>
     */
    public function siblingStreams()
    {
        return static::query()
            ->where('school_id', $this->school_id)
            ->where('name', $this->name)
            ->where('level', $this->level)
            ->where('id', '!=', $this->id)
            ->orderBy('stream')
            ->get();
    }

    /** Human label: "S.1 East" when a stream is set, otherwise the class name. */
    public function displayName(): string
    {
        $stream = trim((string) ($this->stream ?? ''));

        return $stream !== '' ? $this->name.' '.$stream : $this->name;
    }
}
