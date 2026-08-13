@extends('layouts.app')
@section('title', 'Classes & streams · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Classes &amp; streams</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px;max-width:60ch">
        Create each teaching group (e.g. P.5 / S.1). Add a stream when the same class runs in parallel sections (East, West, A, B).
      </p>
    </div>
  </div>

  @error('class')<div class="err" style="margin-bottom:12px">{{ $message }}</div>@enderror

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add class / stream</h3>
      <form method="post" action="{{ route('app.classes.store') }}">
        @csrf
        <label>Level</label>
        <select name="level" required>
          @foreach($levels as $level)
            <option value="{{ $level }}" @selected(old('level') === $level)>{{ str_replace('_', ' ', ucfirst($level)) }}</option>
          @endforeach
        </select>
        @error('level')<div class="err">{{ $message }}</div>@enderror

        <label>Class name</label>
        <input name="name" value="{{ old('name') }}" required placeholder="e.g. Senior 1 or P.5">
        @error('name')<div class="err">{{ $message }}</div>@enderror

        <label>Stream <span style="font-weight:400;color:var(--muted)">(optional)</span></label>
        <input name="stream" value="{{ old('stream') }}" placeholder="e.g. East, West, A, Blue">
        @error('stream')<div class="err">{{ $message }}</div>@enderror

        <label>Code <span style="font-weight:400;color:var(--muted)">(optional — auto if blank)</span></label>
        <input name="code" value="{{ old('code') }}" placeholder="e.g. S1-EAST">
        @error('code')<div class="err">{{ $message }}</div>@enderror

        <p style="margin-top:14px"><button class="btn" type="submit">Create</button></p>
      </form>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Current classes</h3>
      @if($classes->isEmpty())
        <p style="color:var(--muted);margin:0">No classes yet. Add the first class so learners can be enrolled and placed.</p>
      @else
        <table>
          <thead>
            <tr><th>Class</th><th>Stream</th><th>Level</th><th>Code</th><th>Learners</th><th></th></tr>
          </thead>
          <tbody>
          @foreach($classes as $class)
            <tr>
              <td><strong>{{ $class->name }}</strong></td>
              <td>{{ $class->stream ?: '—' }}</td>
              <td>{{ $class->level }}</td>
              <td><code>{{ $class->code }}</code></td>
              <td>{{ $class->students_count }}</td>
              <td>
                <form method="post" action="{{ route('app.classes.destroy', $class) }}" onsubmit="return confirm('Delete {{ $class->displayName() }}?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn ghost" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
@endsection
