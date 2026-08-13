<?php

namespace App\Services\Assessment;

use App\Models\AssessmentPeriod;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarksheetService
{
    public function __construct(
        private AssessmentPeriodWorkflow $workflow,
        private GradingSchemeService $grading,
    ) {}

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
        $period = AssessmentPeriod::query()->findOrFail($periodId);
        if (! $this->workflow->canEnterMarks($period)) {
            throw ValidationException::withMessages([
                'period_id' => 'Mark entry is closed for this period. An administrator must reopen it.',
            ]);
        }

        $scheme = $period->grading_scheme_id
            ? $period->gradingScheme()->with('bands')->first()
            : $this->grading->defaultForSchool($schoolId);

        $saved = [];

        DB::transaction(function () use ($schoolId, $periodId, $classId, $rows, $enteredBy, $scheme, &$saved) {
            foreach ($rows as $row) {
                $score = isset($row['score']) && $row['score'] !== '' && $row['score'] !== null
                    ? (float) $row['score']
                    : null;
                $graded = $this->grading->gradeFor($score, $scheme, $schoolId);

                $saved[] = Mark::query()->updateOrCreate(
                    [
                        'assessment_period_id' => $periodId,
                        'student_id' => $row['student_id'],
                        'subject_id' => $row['subject_id'],
                    ],
                    [
                        'school_id' => $schoolId,
                        'class_id' => $classId,
                        'score' => $score,
                        'grade' => $graded['grade'] ?? ($row['grade'] ?? null),
                        'points' => $graded['points'] ?? null,
                        'remark' => $graded['remark'] ?? null,
                        'comment' => $row['comment'] ?? null,
                        'entered_by' => $enteredBy,
                    ],
                );
            }
        });

        return $saved;
    }

    /**
     * @return array{students: Collection, subjects: Collection, scores: array<int, array<int, float|null>>}
     */
    public function broadsheet(int $periodId, int $classId): array
    {
        $students = $this->studentsInClass($classId);
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
     * @return list<array{student_id:int, full_name:string, average:float|null, total:float|null, position:int|null, subject_count:int, subjects:list<array>}>
     */
    public function reportCards(int $periodId, int $classId, array $settings = []): array
    {
        $showPosition = (bool) ($settings['show_position'] ?? true);
        $students = $this->studentsInClass($classId);

        $marks = Mark::query()
            ->where('assessment_period_id', $periodId)
            ->where('class_id', $classId)
            ->whereNotNull('score')
            ->with('subject')
            ->get()
            ->groupBy('student_id');

        $rows = [];
        foreach ($students as $student) {
            $studentMarks = $marks->get($student->id, collect());
            $count = $studentMarks->count();
            $total = $count > 0 ? round((float) $studentMarks->sum('score'), 2) : null;
            $average = $count > 0 ? round((float) $studentMarks->avg('score'), 2) : null;

            $rows[] = [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'average' => $average,
                'total' => $total,
                'position' => null,
                'subject_count' => $count,
                'subjects' => $studentMarks->map(fn ($m) => [
                    'name' => $m->subject?->name ?? 'Subject',
                    'code' => $m->subject?->code,
                    'score' => $m->score,
                    'grade' => $m->grade,
                    'remark' => $m->remark,
                    'points' => $m->points,
                    'comment' => $m->comment,
                ])->values()->all(),
            ];
        }

        if ($showPosition) {
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
        }

        return $rows;
    }

    private function studentsInClass(int $classId): Collection
    {
        return Student::query()
            ->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)
                    ->orWhereHas('enrollments', fn ($e) => $e->where('class_id', $classId)->where('status', 'active'));
            })
            ->orderBy('full_name')
            ->get(['id', 'full_name']);
    }
}
