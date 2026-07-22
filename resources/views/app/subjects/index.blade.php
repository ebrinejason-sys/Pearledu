@extends('layouts.app')
@section('title','Subjects · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Subjects</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add subject</h3>
      <form method="post" action="{{ route('app.subjects.store') }}">
        @csrf
        <label>Name</label>
        <input name="name" value="{{ old('name') }}" required>
        <label>Code</label>
        <input name="code" value="{{ old('code') }}" required>
        @error('code')<div class="err">{{ $message }}</div>@enderror
        <p style="margin-top:14px"><button class="btn" type="submit">Save</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Catalogue</h3>
      <table>
        <thead><tr><th>Name</th><th>Code</th><th></th></tr></thead>
        <tbody>
        @forelse($subjects as $subject)
          <tr>
            <td>{{ $subject->name }}</td>
            <td>{{ $subject->code ?: '—' }}</td>
            <td>
              <form method="post" action="{{ route('app.subjects.destroy', $subject) }}" onsubmit="return confirm('Remove subject?')">
                @csrf @method('DELETE')
                <button class="btn" type="submit">Remove</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No subjects yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
