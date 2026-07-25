@extends('layouts.app')
@section('title','Classes · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.workspace') }}">{{ $school->name }}</a> · Data entry</p>
      <h2 class="page-header__title">Classes</h2>
    </div>
  </div>

  @if(session('status'))<div class="status" style="margin-bottom:12px">{{ session('status') }}</div>@endif
  @error('school')<div class="err" style="margin-bottom:12px">{{ $message }}</div>@enderror
  @error('class')<div class="err" style="margin-bottom:12px">{{ $message }}</div>@enderror

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add class</h3>
      @if(empty($levels))
        <p style="color:var(--muted)">This school has no offered levels yet. Edit the school offerings before adding classes.</p>
      @else
        <form method="post" action="{{ route('platform.classes.store') }}">
          @csrf
          <label>Level</label>
          <select name="level" required>
            @foreach($levels as $level)
              <option value="{{ $level }}" @selected(old('level') === $level)>{{ $level }}</option>
            @endforeach
          </select>
          @error('level')<div class="err">{{ $message }}</div>@enderror
          <label>Name</label>
          <input name="name" value="{{ old('name') }}" required placeholder="e.g. P.5 Blue">
          @error('name')<div class="err">{{ $message }}</div>@enderror
          <label>Code</label>
          <input name="code" value="{{ old('code') }}" required placeholder="e.g. P5-B">
          @error('code')<div class="err">{{ $message }}</div>@enderror
          <p style="margin-top:14px"><button class="btn" type="submit">Create class</button></p>
        </form>
      @endif
    </div>

    <div class="card">
      <h3 style="margin-top:0">Current classes</h3>
      @if($classes->isEmpty())
        <p style="color:var(--muted);margin:0">
          No classes have been created for {{ $school->name }}.
          @if(!empty($levels)) Use the form to create the first class. @endif
        </p>
      @else
        <table>
          <thead>
            <tr><th>Name</th><th>Level</th><th>Code</th><th>Students</th><th></th></tr>
          </thead>
          <tbody>
          @foreach($classes as $class)
            <tr>
              <td><strong>{{ $class->name }}</strong></td>
              <td>{{ $class->level }}</td>
              <td>{{ $class->code }}</td>
              <td>{{ $class->students_count }}</td>
              <td>
                <form method="post" action="{{ route('platform.classes.destroy', $class) }}" onsubmit="return confirm('Delete this class?')">
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
