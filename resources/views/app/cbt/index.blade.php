@extends('layouts.app')
@section('title','CBT · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Learning</p><h2 class="page-header__title">CBT exams</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">New exam</h3>
      <form method="post" action="{{ route('app.cbt.exams.store') }}">@csrf
        <label>Title</label><input name="title" required>
        <label>Duration (minutes)</label><input type="number" name="duration_minutes" value="30">
        <label>Subject</label>
        <select name="subject_id"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
        <label>Class</label>
        <select name="class_id"><option value="">—</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <p style="margin-top:14px"><button class="btn" type="submit">Create exam</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Add question</h3>
      <form method="post" action="{{ route('app.cbt.questions.store') }}">@csrf
        <label>Exam</label>
        <select name="exam_id" required>@foreach($exams as $e)<option value="{{ $e->id }}">{{ $e->title }}</option>@endforeach</select>
        <label>Prompt</label><textarea name="prompt" rows="3" required></textarea>
        <label>Choices (one per line)</label><textarea name="choices" rows="4" required placeholder="A) …&#10;B) …"></textarea>
        <label>Correct key</label><input name="correct_key" placeholder="A" required>
        <label>Points</label><input type="number" step="0.01" name="points" value="1">
        <p style="margin-top:14px"><button class="btn" type="submit">Add question</button></p>
      </form>
    </div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Exams</h3>
    <table>
      <thead><tr><th>Title</th><th>Duration</th><th>Questions</th><th>Published</th></tr></thead>
      <tbody>
      @forelse($exams as $e)
        <tr><td>{{ $e->title }}</td><td>{{ $e->duration_minutes }}m</td><td>{{ $e->questions_count }}</td><td>{{ $e->is_published ? 'Yes' : 'No' }}</td></tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">None.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
