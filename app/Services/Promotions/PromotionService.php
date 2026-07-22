<?php

namespace App\Services\Promotions;

use App\Models\Enrollment;
use App\Models\PromotionBatch;
use App\Models\PromotionItem;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    /**
     * @param  array{
     *   school_id:int,
     *   from_year_id:int,
     *   to_year_id:int,
     *   created_by?:int|null,
     *   items: list<array{student_id:int, from_class_id:int, to_class_id?:int|null, outcome:string}>
     * }  $data
     */
    public function createBatch(array $data): PromotionBatch
    {
        return DB::transaction(function () use ($data) {
            $batch = PromotionBatch::create([
                'school_id' => $data['school_id'],
                'from_year_id' => $data['from_year_id'],
                'to_year_id' => $data['to_year_id'],
                'status' => 'draft',
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PromotionItem::create([
                    'school_id' => $data['school_id'],
                    'batch_id' => $batch->id,
                    'student_id' => $item['student_id'],
                    'from_class_id' => $item['from_class_id'],
                    'to_class_id' => $item['to_class_id'] ?? null,
                    'outcome' => $item['outcome'],
                ]);
            }

            return $batch->load('items');
        });
    }

    public function commit(PromotionBatch $batch): PromotionBatch
    {
        if ($batch->status === 'committed') {
            throw ValidationException::withMessages(['batch' => 'This promotion batch is already committed.']);
        }

        return DB::transaction(function () use ($batch) {
            $batch->load('items');

            foreach ($batch->items as $item) {
                $old = Enrollment::query()
                    ->where('school_id', $batch->school_id)
                    ->where('student_id', $item->student_id)
                    ->where('academic_year_id', $batch->from_year_id)
                    ->where('status', 'active')
                    ->first();

                if ($old) {
                    $old->update(['status' => 'completed']);
                }

                $student = Student::query()->findOrFail($item->student_id);

                if ($item->outcome === 'promote' || $item->outcome === 'repeat') {
                    if (! $item->to_class_id) {
                        throw ValidationException::withMessages([
                            'items' => 'Promote/repeat outcomes require a destination class.',
                        ]);
                    }

                    Enrollment::create([
                        'school_id' => $batch->school_id,
                        'student_id' => $item->student_id,
                        'class_id' => $item->to_class_id,
                        'academic_year_id' => $batch->to_year_id,
                        'status' => 'active',
                    ]);

                    $student->update(['class_id' => $item->to_class_id]);
                } elseif ($item->outcome === 'graduate') {
                    $student->update(['status' => 'graduated', 'class_id' => null]);
                } elseif ($item->outcome === 'transfer') {
                    $student->update(['status' => 'transferred']);
                }
            }

            $batch->update([
                'status' => 'committed',
                'committed_at' => now(),
            ]);

            return $batch->fresh('items');
        });
    }
}
