<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'code' => [
                'required', 'string', 'max:40',
                Rule::unique('subjects', 'code')->where(fn ($q) => $q->where('school_id', $school->id)),
            ],
        ]);

        Subject::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'code' => $data['code'],
        ]);

        return back()->with('status', 'Subject created.');
    }

    public function update(Request $request, Subject $subject, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && (int) $subject->school_id === (int) $school->id, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => [
                'required', 'string', 'max:40',
                Rule::unique('subjects', 'code')
                    ->where(fn ($q) => $q->where('school_id', $school->id))
                    ->ignore($subject->id),
            ],
        ]);

        $subject->update($data);

        return back()->with('status', 'Subject updated.');
    }

    public function destroy(Subject $subject, TenantContext $context)
    {
        $school = $context->school();
        abort_unless($school && $subject->school_id === $school->id, 404);
        $subject->delete();

        return back()->with('status', 'Subject removed.');
    }
}
