@extends('layouts.app')
@section('title','Marks · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Assessment</p>
      <h1 class="page-header__title">Enter marks</h1>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Save as draft, submit to the Director of Studies, then wait for verification before results are published.</p>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  @unless($hasAssignments)
  <div class="card" style="margin-bottom:16px;color:var(--muted)">
    No teaching assignments are linked to your account. Ask an administrator to assign you a class and subject before entering marks.
  </div>
  @endunless

  <div class="card">
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div>
        <label>Period</label>
        <select name="period_id" onchange="this.form.submit()">
          @foreach($periods as $p)<option value="{{ $p->id }}" @selected($periodId===$p->id)>{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div>
        <label>Class</label>
        <select name="class_id" onchange="this.form.submit()" @disabled(!$hasAssignments)>
          @forelse($classes as $c)
            <option value="{{ $c->id }}" @selected($classId===$c->id)>{{ $c->name }}</option>
          @empty
            <option value="">No assigned classes</option>
          @endforelse
        </select>
      </div>
      <div>
        <label>Subject</label>
        <select name="subject_id" onchange="this.form.submit()" @disabled($subjects->isEmpty())>
          @forelse($subjects as $s)
            <option value="{{ $s->id }}" @selected($subjectId===$s->id)>{{ $s->name }}</option>
          @empty
            <option value="">No assigned subjects</option>
          @endforelse
        </select>
      </div>
    </form>
  </div>

  @if($students->isNotEmpty() && $periodId && $subjectId)
  <div class="card">
    @if(!empty($marksheet))
      <p style="margin:0 0 12px">
        Marksheet status:
        <span class="pill">{{ $marksheet->status }}</span>
        @if($marksheet->submitted_at)
          <span style="color:var(--muted);font-size:13px">Submitted {{ $marksheet->submitted_at->diffForHumans() }}</span>
        @endif
      </p>
    @endif
    <form method="post" action="{{ route('app.assessment.marks.store') }}" data-offline-queue="marks">
      @csrf
      <input type="hidden" name="period_id" value="{{ $periodId }}">
      <input type="hidden" name="class_id" value="{{ $classId }}">
      <input type="hidden" name="subject_id" value="{{ $subjectId }}">
      <table>
        <thead><tr><th>Student</th><th>Score</th><th>Grade</th><th>Comment</th></tr></thead>
        <tbody>
        @foreach($students as $i => $student)
          @php($m = $existing->get($student->id))
          <tr>
            <td>{{ $student->full_name }}<input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $student->id }}"></td>
            <td><input type="number" step="0.01" name="rows[{{ $i }}][score]" value="{{ $m?->score }}" @disabled(!($canEnterMarks ?? true))></td>
            <td>{{ $m?->grade ?: '—' }}@if($m?->remark)<div style="font-size:12px;color:var(--muted)">{{ $m->remark }}</div>@endif</td>
            <td><input name="rows[{{ $i }}][comment]" value="{{ $m?->comment }}" @disabled(!($canEnterMarks ?? true))></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <p style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        @if($canEnterMarks ?? true)
          <button class="btn" type="submit">Save draft marks</button>
        @else
          <span style="color:var(--muted)">Mark entry is {{ str_replace('_', ' ', $period?->status ?? 'closed') }}{{ !empty($marksheet) ? ' · marksheet '.$marksheet->status : '' }}.</span>
        @endif
      </p>
    </form>
    @if(($canSubmitMarksheet ?? false) || ($canVerifyMarksheet ?? false) || ($canReturnMarksheet ?? false))
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
        @if($canSubmitMarksheet ?? false)
          <form method="post" action="{{ route('app.assessment.marksheets.submit') }}">
            @csrf
            <input type="hidden" name="period_id" value="{{ $periodId }}">
            <input type="hidden" name="class_id" value="{{ $classId }}">
            <input type="hidden" name="subject_id" value="{{ $subjectId }}">
            <button class="btn accent" type="submit">Submit for verification</button>
          </form>
        @endif
        @if($canVerifyMarksheet ?? false)
          <form method="post" action="{{ route('app.assessment.marksheets.verify') }}">
            @csrf
            <input type="hidden" name="period_id" value="{{ $periodId }}">
            <input type="hidden" name="class_id" value="{{ $classId }}">
            <input type="hidden" name="subject_id" value="{{ $subjectId }}">
            <button class="btn" type="submit">Verify marksheet</button>
          </form>
        @endif
        @if($canReturnMarksheet ?? false)
          <form method="post" action="{{ route('app.assessment.marksheets.return') }}">
            @csrf
            <input type="hidden" name="period_id" value="{{ $periodId }}">
            <input type="hidden" name="class_id" value="{{ $classId }}">
            <input type="hidden" name="subject_id" value="{{ $subjectId }}">
            <button class="btn ghost" type="submit">Return to draft</button>
          </form>
        @endif
      </div>
    @endif
  </div>
  @endif
@endsection
