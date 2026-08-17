<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Attendance\AttendanceService;
use App\Services\Authorization\AttendanceScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request, TenantContext $context, AttendanceScope $scope)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $user = $request->user();
        abort_unless($user, 403);

        $allowedIds = $scope->viewableClassIds($user, $school->id);
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->when(is_array($allowedIds), fn ($q) => $q->whereIn('id', $allowedIds ?: [0]))
            ->get();

        $classId = (int) $request->query('class_id', $classes->first()?->id ?? 0);
        if ($classId && ! $scope->canViewClass($user, $school->id, $classId)) {
            abort(403);
        }

        $date = $request->query('date', now()->toDateString());
        $canMark = $classId && $scope->canMarkClass($user, $school->id, $classId);

        $students = $classId
            ? Student::query()->where('class_id', $classId)->orderBy('full_name')->get()
            : collect();

        $existing = AttendanceRecord::query()
            ->where('class_id', $classId)
            ->whereDate('attended_on', $date)
            ->get()
            ->keyBy('student_id');

        return view('app.attendance.index', compact(
            'school', 'classes', 'classId', 'date', 'students', 'existing', 'canMark'
        ));
    }

    public function store(Request $request, TenantContext $context, AttendanceService $service, AttendanceScope $scope)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $user = $request->user();
        abort_unless($user, 403);

        $data = $request->validate([
            'class_id' => 'required|integer|exists:school_classes,id',
            'attended_on' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|integer|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
            'records.*.reason' => 'nullable|string|max:190',
            'notify_absent' => 'nullable|boolean',
        ]);

        abort_unless($scope->canMarkClass($user, $school->id, (int) $data['class_id']), 403);

        $service->bulkUpsert(
            $school->id,
            (int) $data['class_id'],
            $data['attended_on'],
            $data['records'],
            $user->id,
            (bool) ($data['notify_absent'] ?? true),
        );

        return back()->with('status', 'Attendance saved.');
    }
}
