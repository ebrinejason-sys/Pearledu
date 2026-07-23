@extends('layouts.app')
@section('title','LMS · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Learning</p><h2 class="page-header__title">My LMS</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <p style="color:var(--muted);margin-top:0">Signed in as {{ $student->full_name }}</p>
  <div class="card">
    <h3 style="margin-top:0">Materials</h3>
    <table>
      <thead><tr><th>Title</th><th>Subject</th><th>Class</th><th></th></tr></thead>
      <tbody>
      @forelse($materials as $m)
        <tr>
          <td>
            <strong>{{ $m->title }}</strong>
            @if($m->body)<div style="color:var(--muted);margin-top:4px">{{ \Illuminate\Support\Str::limit($m->body, 120) }}</div>@endif
          </td>
          <td>{{ $m->subject?->name ?: '—' }}</td>
          <td>{{ $m->schoolClass?->name ?: '—' }}</td>
          <td>@if($m->url)<a href="{{ $m->url }}" target="_blank" rel="noopener">Open</a>@endif</td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No materials.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Assignments</h3>
    @forelse($assignments as $a)
      @php $sub = $mySubmissions->get($a->id); @endphp
      <div style="border-top:1px solid var(--border, #ddd);padding:14px 0">
        <strong>{{ $a->title }}</strong>
        <div style="color:var(--muted);font-size:0.9rem">
          {{ $a->subject?->name ?: '—' }} · due {{ $a->due_at?->format('Y-m-d H:i') ?: '—' }}
        </div>
        @if($a->instructions)<p>{{ $a->instructions }}</p>@endif
        @if($sub)
          <p style="margin-bottom:8px">
            Submitted {{ $sub->submitted_at?->format('Y-m-d H:i') }}
            @if($sub->score !== null) · Score <strong>{{ $sub->score }}</strong>@endif
            @if($sub->feedback)<span style="color:var(--muted)"> — {{ $sub->feedback }}</span>@endif
          </p>
        @endif
        <form method="post" action="{{ route('app.lms.assignments.submit', $a) }}">@csrf
          <label>Your answer</label>
          <textarea name="body" rows="3">{{ $sub?->body }}</textarea>
          <label>Link (optional)</label>
          <input type="url" name="url" value="{{ $sub?->url }}">
          <p style="margin-top:10px"><button class="btn" type="submit">{{ $sub ? 'Update submission' : 'Submit' }}</button></p>
        </form>
      </div>
    @empty
      <p style="color:var(--muted)">No assignments.</p>
    @endforelse
  </div>
@endsection
