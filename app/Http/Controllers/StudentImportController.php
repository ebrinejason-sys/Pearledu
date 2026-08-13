<?php

namespace App\Http\Controllers;

use App\Services\Academics\CurrentAcademicContext;
use App\Services\Learners\StudentImportService;
use App\Services\Learners\StudentLifecycleService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class StudentImportController extends Controller
{
    public function create(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        if ($request->boolean('reset')) {
            session()->forget(['student_import.headers', 'student_import.rows', 'student_import.mapping', 'student_import.preview']);
        }

        return view('app.students.import', [
            'school' => $school,
            'step' => session('student_import.headers') ? 'map' : 'upload',
            'headers' => session('student_import.headers', []),
            'mapping' => session('student_import.mapping', []),
            'preview' => session('student_import.preview'),
        ]);
    }

    public function storeFile(Request $request, TenantContext $ctx, StudentImportService $importer)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $request->validate(['file' => 'required|file|max:4096']);

        $parsed = $importer->parse($request->file('file'));
        $mapping = $importer->suggestedMapping($parsed['headers']);

        session([
            'student_import.headers' => $parsed['headers'],
            'student_import.rows' => $parsed['rows'],
            'student_import.mapping' => $mapping,
            'student_import.preview' => null,
        ]);

        return redirect()->route('app.students.import');
    }

    public function preview(Request $request, TenantContext $ctx, StudentImportService $importer)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        abort_unless(session('student_import.rows'), 404);

        $mapping = $request->validate([
            'mapping' => 'required|array',
            'mapping.full_name' => 'required|integer',
            'mapping.class' => 'required|integer',
            'mapping.parent_name' => 'nullable|integer',
            'mapping.parent_phone' => 'nullable|integer',
            'mapping.parent_email' => 'nullable|integer',
            'mapping.lin' => 'nullable|integer',
            'mapping.emis_number' => 'nullable|integer',
        ])['mapping'];

        $preview = $importer->preview($school, session('student_import.rows'), $mapping);
        session([
            'student_import.mapping' => $mapping,
            'student_import.preview' => $preview,
        ]);

        return redirect()->route('app.students.import');
    }

    public function commit(TenantContext $ctx, StudentImportService $importer, StudentLifecycleService $lifecycle, CurrentAcademicContext $academic)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $preview = session('student_import.preview');
        abort_unless(is_array($preview), 404);

        $result = $importer->import($school, $preview['ok'] ?? [], $lifecycle, $academic);
        session()->forget(['student_import.headers', 'student_import.rows', 'student_import.mapping', 'student_import.preview']);

        $message = "Imported {$result['created']} learner(s). Skipped {$result['skipped']} duplicate(s).";
        if ($result['errors'] !== []) {
            $message .= ' '.count($result['errors']).' row(s) failed.';
        }

        return redirect()->route('app.students.index')->with('status', $message);
    }
}
