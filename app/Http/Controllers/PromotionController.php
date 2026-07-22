<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PromotionBatch;
use App\Models\SchoolClass;
use App\Services\Promotions\PromotionService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function index(TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $batches = PromotionBatch::query()->with(['fromYear', 'toYear', 'items'])->orderByDesc('id')->get();
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $classes = SchoolClass::query()->orderBy('name')->get();

        return view('app.promotions.index', compact('school', 'batches', 'years', 'classes'));
    }

    public function store(Request $request, TenantContext $context, PromotionService $promotions)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'from_year_id' => 'required|integer|exists:academic_years,id',
            'to_year_id' => 'required|integer|exists:academic_years,id|different:from_year_id',
            'from_class_id' => 'required|integer|exists:school_classes,id',
            'to_class_id' => 'nullable|integer|exists:school_classes,id',
            'outcome' => 'required|in:promote,repeat,graduate,transfer',
        ]);

        $enrollments = Enrollment::query()
            ->where('academic_year_id', $data['from_year_id'])
            ->where('class_id', $data['from_class_id'])
            ->where('status', 'active')
            ->get();

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'from_class_id' => 'No active enrollments found for that class and year.',
            ]);
        }

        $items = $enrollments->map(fn ($e) => [
            'student_id' => $e->student_id,
            'from_class_id' => $e->class_id,
            'to_class_id' => in_array($data['outcome'], ['promote', 'repeat'], true)
                ? ($data['to_class_id'] ?? null)
                : null,
            'outcome' => $data['outcome'],
        ])->all();

        $promotions->createBatch([
            'school_id' => $school->id,
            'from_year_id' => $data['from_year_id'],
            'to_year_id' => $data['to_year_id'],
            'created_by' => $request->user()?->id,
            'items' => $items,
        ]);

        return back()->with('status', 'Promotion batch drafted.');
    }

    public function commit(PromotionBatch $batch, TenantContext $context, PromotionService $promotions)
    {
        $school = $context->school();
        abort_unless($school && $batch->school_id === $school->id, 404);

        $promotions->commit($batch);

        return back()->with('status', 'Promotion batch committed.');
    }
}
