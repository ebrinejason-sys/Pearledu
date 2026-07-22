@extends('layouts.app')
@section('title','Teaching · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Teaching assignments</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="card">
    <h3 style="margin-top:0">Assign teacher</h3>
    <form method="post" action="{{ route('app.teaching.store') }}">
      @csrf
      <label>Staff role assignment</label>
      <select name="assignment_id" required>
        <option value="">Select…</option>
        @foreach($roleAssignments as $ra)
          <option value="{{ $ra->id }}">{{ $ra->user?->full_name }} · {{ $ra->role?->label }}</option>
        @endforeach
      </select>
      <label>Subject</label>
      <select name="subject_id" required>
        @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
      </select>
      <label>Class</label>
      <select name="class_id" required>
        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
      </select>
      <p style="margin-top:14px"><button class="btn" type="submit">Save</button></p>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Current assignments</h3>
    <table>
      <thead><tr><th>Teacher</th><th>Subject</th><th>Class</th><th></th></tr></thead>
      <tbody>
      @forelse($assignments as $a)
        <tr>
          <td>{{ $a->roleAssignment?->user?->full_name }}</td>
          <td>{{ $a->subject?->name }}</td>
          <td>{{ $a->schoolClass?->name }}</td>
          <td>
            <form method="post" action="{{ route('app.teaching.destroy', $a) }}">@csrf @method('DELETE')
              <button class="btn" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">None yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
