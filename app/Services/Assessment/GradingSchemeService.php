<?php

namespace App\Services\Assessment;

use App\Models\GradingScheme;
use App\Models\GradingSchemeBand;
use Illuminate\Support\Facades\DB;

class GradingSchemeService
{
    /**
     * UNEB O-level style divisions. Schools can edit bands later.
     *
     * @return list<array{min:float,max:float,grade:string,remark:string,points:int}>
     */
    public function unebOlevelBands(): array
    {
        return [
            ['min' => 80, 'max' => 100, 'grade' => 'D1', 'remark' => 'Excellent', 'points' => 1],
            ['min' => 75, 'max' => 79, 'grade' => 'D2', 'remark' => 'Very good', 'points' => 2],
            ['min' => 70, 'max' => 74, 'grade' => 'C3', 'remark' => 'Good', 'points' => 3],
            ['min' => 65, 'max' => 69, 'grade' => 'C4', 'remark' => 'Credit', 'points' => 4],
            ['min' => 60, 'max' => 64, 'grade' => 'C5', 'remark' => 'Credit', 'points' => 5],
            ['min' => 55, 'max' => 59, 'grade' => 'C6', 'remark' => 'Credit', 'points' => 6],
            ['min' => 50, 'max' => 54, 'grade' => 'P7', 'remark' => 'Pass', 'points' => 7],
            ['min' => 40, 'max' => 49, 'grade' => 'P8', 'remark' => 'Pass', 'points' => 8],
            ['min' => 0, 'max' => 39, 'grade' => 'F9', 'remark' => 'Fail', 'points' => 9],
        ];
    }

    public function defaultForSchool(int $schoolId): GradingScheme
    {
        $existing = GradingScheme::query()
            ->where('school_id', $schoolId)
            ->where('is_default', true)
            ->with('bands')
            ->first();

        return $existing ?? $this->seedDefault($schoolId);
    }

    public function seedDefault(int $schoolId): GradingScheme
    {
        return DB::transaction(function () use ($schoolId) {
            GradingScheme::query()->where('school_id', $schoolId)->update(['is_default' => false]);

            $scheme = GradingScheme::create([
                'school_id' => $schoolId,
                'name' => 'UNEB-style',
                'kind' => 'uneb_olevel',
                'is_default' => true,
            ]);

            foreach ($this->unebOlevelBands() as $i => $band) {
                GradingSchemeBand::create([
                    'school_id' => $schoolId,
                    'grading_scheme_id' => $scheme->id,
                    'min_score' => $band['min'],
                    'max_score' => $band['max'],
                    'grade' => $band['grade'],
                    'remark' => $band['remark'],
                    'points' => $band['points'],
                    'sort_order' => $i,
                ]);
            }

            return $scheme->load('bands');
        });
    }

    /**
     * @return array{grade:string,remark:?string,points:?int}|null
     */
    public function gradeFor(?float $score, ?GradingScheme $scheme = null, ?int $schoolId = null): ?array
    {
        if ($score === null) {
            return null;
        }

        if (! $scheme && $schoolId) {
            $scheme = $this->defaultForSchool($schoolId);
        }

        if (! $scheme) {
            return null;
        }

        $scheme->loadMissing('bands');

        foreach ($scheme->bands as $band) {
            if ($score + 0.0001 >= (float) $band->min_score && $score - 0.0001 <= (float) $band->max_score) {
                return [
                    'grade' => $band->grade,
                    'remark' => $band->remark,
                    'points' => $band->points,
                ];
            }
        }

        return null;
    }
}
