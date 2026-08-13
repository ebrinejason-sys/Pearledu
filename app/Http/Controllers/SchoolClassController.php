<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolOffering;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $classes = SchoolClass::query()
            ->where('school_id', $school->id)
            ->withCount('students')
            ->orderBy('level')
            ->orderBy('name')
            ->orderBy('stream')
            ->get();

        $levels = SchoolOffering::query()
            ->where('school_id', $school->id)
            ->pluck('level')
            ->all();

        if ($levels === []) {
            $levels = ['primary', 'secondary', 'a_level'];
        }

        return view('app.classes.index', compact('school', 'classes', 'levels'));
    }

    public function store(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $levels = SchoolOffering::query()
            ->where('school_id', $school->id)
            ->pluck('level')
            ->all();
        if ($levels === []) {
            $levels = ['primary', 'secondary', 'a_level'];
        }

        $data = $request->validate([
            'level' => ['required', 'string', Rule::in($levels)],
            'name' => 'required|string|max:80',
            'stream' => 'nullable|string|max:40',
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('school_classes', 'code')->where(fn ($q) => $q->where('school_id', $school->id)),
            ],
        ]);

        $stream = filled($data['stream'] ?? null) ? trim((string) $data['stream']) : null;
        $name = trim((string) $data['name']);
        $code = filled($data['code'] ?? null)
            ? trim((string) $data['code'])
            : $this->suggestCode($school->id, $name, $stream);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'level' => $data['level'],
            'name' => $name,
            'stream' => $stream,
            'code' => $code,
        ]);

        $this->audit->record('school.class.created', $class, [
            'school_id' => $school->id,
            'stream' => $stream,
        ]);

        return back()->with('status', 'Created '.$class->displayName().'.');
    }

    public function destroy(SchoolClass $schoolClass, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $schoolClass->school_id === (int) $school->id, 404);

        if ($schoolClass->students()->exists()) {
            return back()->withErrors(['class' => 'Move or archive learners in this class before deleting it.']);
        }

        $label = $schoolClass->displayName();
        $schoolClass->delete();
        $this->audit->record('school.class.deleted', null, [
            'school_id' => $school->id,
            'name' => $label,
        ]);

        return back()->with('status', 'Removed '.$label.'.');
    }

    private function suggestCode(int $schoolId, string $name, ?string $stream): string
    {
        $base = Str::upper(Str::slug($name.' '.($stream ?? ''), ''));
        $base = $base !== '' ? substr($base, 0, 28) : 'CLASS';
        $code = $base;
        $n = 2;
        while (SchoolClass::query()->where('school_id', $schoolId)->where('code', $code)->exists()) {
            $code = $base.'-'.$n;
            $n++;
        }

        return $code;
    }
}
