<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $subjects = Subject::query()->orderBy('name')->get();

        return view('app.subjects.index', compact('school', 'subjects'));
    }

    public function store(Request $request, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:40',
        ]);

        Subject::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'code' => $data['code'],
        ]);

        return back()->with('status', 'Subject created.');
    }

    public function destroy(Subject $subject, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && $subject->school_id === $school->id, 404);
        $subject->delete();

        return back()->with('status', 'Subject removed.');
    }
}
