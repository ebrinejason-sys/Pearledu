<?php

namespace App\Services\Learners;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Academics\CurrentAcademicContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentImportService
{
    /**
     * @return array{headers: list<string>, rows: list<array<int, string>>}
     */
    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Upload a CSV file (Excel: File → Save As → CSV UTF-8).',
            ]);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'Could not read the uploaded file.']);
        }

        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_map(fn ($v) => trim((string) $v), $row);
            if (count($rows) >= 2000) {
                break;
            }
        }
        fclose($handle);

        if ($headers === [] || $rows === []) {
            throw ValidationException::withMessages(['file' => 'The CSV must include a header row and at least one learner.']);
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, int|null>
     */
    public function suggestedMapping(array $headers): array
    {
        $normalized = array_map(fn ($h) => strtolower(preg_replace('/[^a-z0-9]+/i', '', $h) ?? ''), $headers);
        $find = function (array $needles) use ($normalized): ?int {
            foreach ($normalized as $i => $h) {
                foreach ($needles as $needle) {
                    if ($h === $needle || str_contains($h, $needle)) {
                        return $i;
                    }
                }
            }

            return null;
        };

        return [
            'full_name' => $find(['fullname', 'name', 'learner', 'studentname']),
            'class' => $find(['class', 'classname', 'stream']),
            'parent_name' => $find(['parentname', 'guardianname', 'guardian']),
            'parent_phone' => $find(['parentphone', 'guardianphone', 'phone']),
            'parent_email' => $find(['parentemail', 'guardianemail', 'email']),
            'lin' => $find(['lin']),
            'emis_number' => $find(['emis', 'emisnumber']),
        ];
    }

    /**
     * @param  list<array<int, string>>  $rows
     * @param  array<string, int|string|null>  $mapping
     * @return array{ok: list<array>, errors: list<array{row:int, message:string}>}
     */
    public function preview(School $school, array $rows, array $mapping): array
    {
        $ok = [];
        $errors = [];
        $seen = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $name = $this->cell($row, $mapping['full_name'] ?? null);
            $className = $this->cell($row, $mapping['class'] ?? null);
            if ($name === '') {
                $errors[] = ['row' => $line, 'message' => 'Missing name'];

                continue;
            }
            if ($className === '') {
                $errors[] = ['row' => $line, 'message' => 'Missing class'];

                continue;
            }

            $key = strtolower($name).'|'.strtolower($className);
            if (isset($seen[$key])) {
                $errors[] = ['row' => $line, 'message' => 'Duplicate of row '.$seen[$key].' in this file'];

                continue;
            }
            $seen[$key] = $line;

            $class = SchoolClass::query()
                ->where('school_id', $school->id)
                ->where(function ($q) use ($className) {
                    $q->whereRaw('lower(name) = ?', [strtolower($className)])
                        ->orWhereRaw('lower(code) = ?', [strtolower($className)]);
                })
                ->first();

            $emis = $this->cell($row, $mapping['emis_number'] ?? null);
            $duplicate = Student::query()
                ->where('school_id', $school->id)
                ->where(function ($q) use ($name, $emis) {
                    $q->whereRaw('lower(full_name) = ?', [strtolower($name)]);
                    if ($emis !== '') {
                        $q->orWhere('emis_number', $emis);
                    }
                })
                ->exists();

            $ok[] = [
                'row' => $line,
                'full_name' => $name,
                'class_name' => $className,
                'class_id' => $class?->id,
                'will_create_class' => $class === null,
                'parent_name' => $this->cell($row, $mapping['parent_name'] ?? null),
                'parent_phone' => $this->cell($row, $mapping['parent_phone'] ?? null),
                'parent_email' => $this->cell($row, $mapping['parent_email'] ?? null),
                'lin' => $this->cell($row, $mapping['lin'] ?? null),
                'emis_number' => $emis,
                'duplicate' => $duplicate,
            ];
        }

        return compact('ok', 'errors');
    }

    /**
     * @param  list<array<string, mixed>>  $okRows
     * @return array{created:int, skipped:int, errors:list<string>}
     */
    public function import(School $school, array $okRows, StudentLifecycleService $lifecycle, CurrentAcademicContext $academic): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($school, $okRows, $lifecycle, $academic, &$created, &$skipped, &$errors) {
            foreach ($okRows as $row) {
                if (! empty($row['duplicate'])) {
                    $skipped++;

                    continue;
                }

                $class = $row['class_id']
                    ? SchoolClass::query()->where('school_id', $school->id)->find($row['class_id'])
                    : null;

                if (! $class) {
                    $class = SchoolClass::firstOrCreate(
                        [
                            'school_id' => $school->id,
                            'code' => strtoupper(substr(preg_replace('/\s+/', '', (string) $row['class_name']), 0, 20)),
                        ],
                        [
                            'name' => $row['class_name'],
                            'level' => 'primary',
                        ],
                    );
                }

                try {
                    $student = Student::create([
                        'school_id' => $school->id,
                        'full_name' => $row['full_name'],
                        'class_id' => $class->id,
                        'status' => 'active',
                        'lin' => $row['lin'] ?: null,
                        'emis_number' => $row['emis_number'] ?: null,
                    ]);

                    if ($academic->year()) {
                        $lifecycle->enrollStudent($student, (int) $class->id);
                    }

                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = ($row['full_name'] ?? 'Row').': '.$e->getMessage();
                }
            }
        });

        return compact('created', 'skipped', 'errors');
    }

    /** @param array<int, string> $row */
    private function cell(array $row, int|string|null $index): string
    {
        if ($index === null || $index === '') {
            return '';
        }

        return trim((string) ($row[(int) $index] ?? ''));
    }
}
