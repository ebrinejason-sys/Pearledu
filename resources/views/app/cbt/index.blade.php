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
        <label>Duration (minutes)</label><input type="number" name="duration_minutes" value="30" min="5">
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
        <select name="exam_id" required>
          @forelse($exams as $e)<option value="{{ $e->id }}">{{ $e->title }}</option>
          @empty<option value="" disabled>Create an exam first</option>@endforelse
        </select>
        <label>Prompt</label><textarea name="prompt" rows="3" required></textarea>
        <label>Choice A</label><input name="choice_a" required>
        <label>Choice B</label><input name="choice_b" required>
        <label>Choice C</label><input name="choice_c">
        <label>Choice D</label><input name="choice_d">
        <label>Correct key</label>
        <select name="correct_key" required>
          <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
        </select>
        <label>Points</label><input type="number" step="0.01" name="points" value="1" min="0">
        <p style="margin-top:14px"><button class="btn" type="submit">Add question</button></p>
      </form>
    </div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Exams</h3>
    <table>
      <thead><tr><th>Title</th><th>Duration</th><th>Questions</th><th>Published</th><th></th></tr></thead>
      <tbody>
      @forelse($exams as $e)
        <tr>
          <td>{{ $e->title }}</td>
          <td>{{ $e->duration_minutes }}m</td>
          <td>{{ $e->questions_count }}</td>
          <td>{{ $e->is_published ? 'Yes' : 'No' }}</td>
          <td>
            <form method="post" action="{{ route('app.cbt.exams.publish', $e) }}">@csrf
              <button class="btn ghost" type="submit">{{ $e->is_published ? 'Unpublish' : 'Publish' }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">None.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
