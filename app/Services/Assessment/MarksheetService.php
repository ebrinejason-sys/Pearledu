<?php

namespace App\Services\Assessment;

use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarksheetService
{
    /**
     * @param  list<array{student_id:int, subject_id:int, score?:float|string|null, grade?:string|null, comment?:string|null}>  $rows
     * @return list<Mark>
     */
    public function saveMarks(
        int $schoolId,
        int $periodId,
        int $classId,
        array $rows,
        ?int $enteredBy = null,
    ): array {
        $saved = [];

        DB::transaction(function () use ($schoolId, $periodId, $classId, $rows, $enteredBy, &$saved) {
            foreach ($rows as $row) {
                $saved[] = Mark::query()->updateOrCreate(
                    [
                        'assessment_period_id' => $periodId,
                        'student_id' => $row['student_id'],
                        'subject_id' => $row['subject_id'],
                    ],
                    [
                        'school_id' => $schoolId,
                        'class_id' => $classId,
                        'score' => $row['score'] ?? null,
                        'grade' => $row['grade'] ?? null,
                        'comment' => $row['comment'] ?? null,
                        'entered_by' => $enteredBy,
                    ],
                );
            }
        });

        return $saved;
    }

    /**
     * Broadsheet matrix: students × subjects with scores.
     *
     * @return array{students: Collection, subjects: Collection, scores: array<int, array<int, float|null>>}
     */
    public function broadsheet(int $periodId, int $classId): array
    {
        $students = Student::query()
            ->where('class_id', $classId)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $marks = Mark::query()
            ->where('assessment_period_id', $periodId)
            ->where('class_id', $classId)
            ->get();

        $subjectIds = $marks->pluck('subject_id')->unique()->values();
        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $scores = [];
        foreach ($marks as $mark) {
            $scores[$mark->student_id][$mark->subject_id] = $mark->score !== null ? (float) $mark->score : null;
        }

        return compact('students', 'subjects', 'scores');
    }

    /**
     * Report-card aggregates: average and position in class for a period.
     *
     * @return list<array{student_id:int, full_name:string, average:float|null, position:int|null, subject_count:int}>
     */
    public function reportCards(int $periodId, int $classId): array
    {
        $students = Student::query()
            ->where('class_id', $classId)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $marks = Mark::query()
            ->where('assessment_period_id', $periodId)
            ->where('class_id', $classId)
            ->whereNotNull('score')
            ->get()
            ->groupBy('student_id');

        $rows = [];
        foreach ($students as $student) {
            $studentMarks = $marks->get($student->id, collect());
            $count = $studentMarks->count();
            $average = $count > 0
                ? round((float) $studentMarks->avg('score'), 2)
                : null;

            $rows[] = [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'average' => $average,
                'position' => null,
                'subject_count' => $count,
            ];
        }

        $ranked = collect($rows)
            ->filter(fn ($r) => $r['average'] !== null)
            ->sortByDesc('average')
            ->values();

        $position = 0;
        $lastAvg = null;
        $index = 0;
        foreach ($ranked as $row) {
            $index++;
            if ($lastAvg === null || abs($row['average'] - $lastAvg) > 0.0001) {
                $position = $index;
                $lastAvg = $row['average'];
            }
            foreach ($rows as &$r) {
                if ($r['student_id'] === $row['student_id']) {
                    $r['position'] = $position;
                }
            }
            unset($r);
        }

        usort($rows, function ($a, $b) {
            if ($a['position'] === null && $b['position'] === null) {
                return strcmp($a['full_name'], $b['full_name']);
            }
            if ($a['position'] === null) {
                return 1;
            }
            if ($b['position'] === null) {
                return -1;
            }

            return $a['position'] <=> $b['position'];
        });

        return $rows;
    }
}
