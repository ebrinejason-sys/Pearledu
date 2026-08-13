@extends('layouts.app')
@section('title','Marks · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Assessment</p>
      <h2 class="page-header__title">Enter marks</h2>
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
    <form method="post" action="{{ route('app.assessment.marks.store') }}">
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
      <p style="margin-top:14px">
        @if($canEnterMarks ?? true)
          <button class="btn" type="submit">Save marks</button>
        @else
          <span style="color:var(--muted)">Mark entry is {{ str_replace('_', ' ', $period?->status ?? 'closed') }}. An administrator must reopen this period to edit scores.</span>
        @endif
      </p>
    </form>
  </div>
  @endif
@endsection
