<?php

namespace App\Http\Controllers;

use App\Models\RoleAssignment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    public function index(TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $assignments = TeachingAssignment::query()
            ->with(['roleAssignment.user', 'subject', 'schoolClass'])
            ->orderByDesc('id')
            ->get();

        $roleAssignments = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->with(['user', 'role'])
            ->get();

        $subjects = Subject::query()->orderBy('name')->get();
        $classes = SchoolClass::query()->orderBy('name')->get();

        return view('app.teaching.index', compact('school', 'assignments', 'roleAssignments', 'subjects', 'classes'));
    }

    public function store(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'assignment_id' => 'required|integer|exists:role_assignments,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'class_id' => 'required|integer|exists:school_classes,id',
        ]);

        $ra = RoleAssignment::query()->findOrFail($data['assignment_id']);
        abort_unless($ra->school_id === $school->id, 404);

        TeachingAssignment::create([
            'school_id' => $school->id,
            'assignment_id' => $data['assignment_id'],
            'subject_id' => $data['subject_id'],
            'class_id' => $data['class_id'],
        ]);

        return back()->with('status', 'Teaching assignment saved.');
    }

    public function destroy(TeachingAssignment $assignment, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && $assignment->school_id === $school->id, 404);
        $assignment->delete();

        return back()->with('status', 'Teaching assignment removed.');
    }
}
