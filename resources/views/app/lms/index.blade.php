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
@endsection
