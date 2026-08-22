<?php

namespace Tests\Unit;

use App\Services\Academics\CurrentAcademicContext;
use App\Services\Academics\TeachingLoadService;
use Tests\TestCase;

class TeachingLoadServiceTest extends TestCase
{
    public function test_pairs_expand_class_ids_and_clamp_periods(): void
    {
        $service = new TeachingLoadService($this->createMock(CurrentAcademicContext::class));

        $pairs = $service->pairsFromRows([
            [
                'subject_id' => 10,
                'class_ids' => [1, 2, '2', 0],
                'periods_per_week' => 99,
            ],
            [
                'subject_id' => 11,
                'class_id' => 3,
                'periods_per_week' => 0,
            ],
            ['subject_id' => 0, 'class_ids' => [4]],
        ]);

        $this->assertSame([
            ['subject_id' => 10, 'class_id' => 1, 'periods_per_week' => 20],
            ['subject_id' => 10, 'class_id' => 2, 'periods_per_week' => 20],
            ['subject_id' => 11, 'class_id' => 3, 'periods_per_week' => 3],
        ], $pairs);
    }
}
