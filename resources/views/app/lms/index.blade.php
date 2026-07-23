@extends('layouts.app')
@section('title','LMS · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Learning</p><h2 class="page-header__title">LMS</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Material</h3>
      <form method="post" action="{{ route('app.lms.materials.store') }}">@csrf
        <label>Title</label><input name="title" required>
        <label>Body</label><textarea name="body" rows="3"></textarea>
        <label>URL</label><input name="url" type="url">
        <label>Subject</label>
        <select name="subject_id"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
        <label>Class</label>
        <select name="class_id"><option value="">—</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <p style="margin-top:14px"><button class="btn" type="submit">Add material</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Assignment</h3>
      <form method="post" action="{{ route('app.lms.assignments.store') }}">@csrf
        <label>Title</label><input name="title" required>
        <label>Instructions</label><textarea name="instructions" rows="3"></textarea>
        <label>Due</label><input type="datetime-local" name="due_at">
        <label>Subject</label>
        <select name="subject_id"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
        <label>Class</label>
        <select name="class_id"><option value="">—</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <p style="margin-top:14px"><button class="btn" type="submit">Add assignment</button></p>
      </form>
    </div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Materials</h3>
    <table>
      <thead><tr><th>Title</th><th>Subject</th><th>Class</th></tr></thead>
      <tbody>
      @forelse($materials as $m)
        <tr><td>{{ $m->title }}</td><td>{{ $m->subject?->name ?: '—' }}</td><td>{{ $m->schoolClass?->name ?: '—' }}</td></tr>
      @empty
        <tr><td colspan="3" style="color:var(--muted)">None.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Assignments</h3>
    <table>
      <thead><tr><th>Title</th><th>Due</th><th>Subject</th><th>Class</th></tr></thead>
      <tbody>
      @forelse($assignments as $a)
        <tr>
          <td>{{ $a->title }}</td>
          <td>{{ $a->due_at?->format('Y-m-d H:i') ?: '—' }}</td>
          <td>{{ $a->subject?->name ?: '—' }}</td>
          <td>{{ $a->schoolClass?->name ?: '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">None.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Submissions to grade</h3>
    <table>
      <thead><tr><th>Assignment</th><th>Student</th><th>Submitted</th><th>Score</th><th>Grade</th></tr></thead>
      <tbody>
      @forelse($submissions as $sub)
        <tr>
          <td>{{ $sub->assignment?->title ?: '—' }}</td>
          <td>{{ $sub->student?->full_name ?: '—' }}</td>
          <td>{{ $sub->submitted_at?->format('Y-m-d H:i') ?: '—' }}</td>
          <td>{{ $sub->score !== null ? $sub->score : '—' }}</td>
          <td>
            <form method="post" action="{{ route('app.lms.submissions.grade', $sub) }}" style="display:flex;gap:6px;align-items:end;flex-wrap:wrap">@csrf
              <div>
                <label>Score</label>
                <input type="number" name="score" step="0.01" min="0" max="100" value="{{ $sub->score }}" required style="width:90px">
              </div>
              <div>
                <label>Feedback</label>
                <input name="feedback" value="{{ $sub->feedback }}" style="min-width:160px">
              </div>
              <button class="btn" type="submit">Save</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No submissions yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
