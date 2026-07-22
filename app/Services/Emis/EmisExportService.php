<?php

namespace App\Services\Emis;

use App\Models\Student;

class EmisExportService
{
    /**
     * @return list<array{id:int, name:string, class:string|null, emis_number:string|null}>
     */
    public function studentsCsvRows(int $schoolId): array
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->with('schoolClass')
            ->orderBy('full_name')
            ->get()
            ->map(fn (Student $s) => [
                'id' => $s->id,
                'name' => $s->full_name,
                'class' => $s->schoolClass?->name,
                'emis_number' => $s->emis_number,
            ])
            ->all();
    }

    public function studentsCsvString(int $schoolId): string
    {
        $rows = $this->studentsCsvRows($schoolId);
        $lines = ['id,name,class,emis_number'];
        foreach ($rows as $row) {
            $lines[] = implode(',', [
                $row['id'],
                $this->escape($row['name']),
                $this->escape($row['class'] ?? ''),
                $this->escape($row['emis_number'] ?? ''),
            ]);
        }

        return implode("\n", $lines)."\n";
    }

    private function escape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
