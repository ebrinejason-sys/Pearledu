@extends('layouts.app')
@section('title', $student->full_name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.students.index') }}">Students</a> · {{ $school->name }}</p>
      <h2 class="page-header__title">{{ $student->full_name }}</h2>
      <p style="color:var(--muted);margin:6px 0 0">
        <span class="pill">{{ $student->status }}</span>
        @if($student->schoolClass) · {{ $student->schoolClass->name }} @endif
      </p>
    </div>
    <div class="page-header__actions">
      <a class="btn" href="{{ route('platform.students.edit', $student) }}">Edit</a>
      <form method="post" action="{{ route('platform.students.destroy', $student) }}" onsubmit="return confirm('Archive this student record?')">
        @csrf
        @method('DELETE')
        <button class="btn ghost" type="submit">Archive</button>
      </form>
    </div>
  </div>

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Record</h3>
      <p><strong>EMIS:</strong> {{ $student->emis_number ?: '—' }}</p>
      <p><strong>Class:</strong> {{ $student->schoolClass?->name ?: '—' }}</p>
      <p><strong>Status:</strong> {{ $student->status }}</p>
      <p style="color:var(--muted);font-size:13px">LIN/NIN stay hidden here to avoid unnecessary sensitive reads. Open Edit to view or change them.</p>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Guardians</h3>
      @if($student->guardianships->isEmpty())
        <p style="color:var(--muted)">No guardians linked yet.</p>
      @else
        <ul style="list-style:none;padding:0;margin:0 0 16px">
          @foreach($student->guardianships as $link)
            <li style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:8px 0;border-top:1px solid var(--line)">
              <div>
                <strong>{{ $link->guardian?->full_name ?? 'Unknown' }}</strong>
                <div style="color:var(--muted);font-size:13px">
                  {{ $link->guardian?->email }}
                  @if($link->relationship) · {{ $link->relationship }} @endif
                  @if($link->is_primary) · <span class="pill">primary</span> @endif
                </div>
              </div>
              <div style="display:flex;gap:8px;align-items:center">
                @unless($link->is_primary)
                  <form method="post" action="{{ route('platform.students.guardians.primary', [$student, $link]) }}">
                    @csrf
                    @method('PUT')
                    <button class="btn ghost" type="submit">Make primary</button>
                  </form>
                @endunless
                <form method="post" action="{{ route('platform.students.guardians.destroy', [$student, $link]) }}" onsubmit="return confirm('Detach this guardian?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn ghost" type="submit">Detach</button>
                </form>
              </div>
            </li>
          @endforeach
        </ul>
      @endif

      <h4>Attach existing member</h4>
      <form method="post" action="{{ route('platform.students.guardians.store', $student) }}" style="margin-bottom:18px">
        @csrf
        <input type="hidden" name="mode" value="attach">
        <div class="grid g2">
          <div><label>Email</label><input name="email" type="email" required value="{{ old('mode') === 'attach' ? old('email') : '' }}"></div>
          <div><label>Relationship</label><input name="relationship" value="{{ old('mode') === 'attach' ? old('relationship') : '' }}" placeholder="mother, father, …"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;width:auto;margin:8px 0">
          <input type="checkbox" name="is_primary" value="1" style="width:auto"> Primary guardian
        </label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <button class="btn" type="submit">Attach</button>
      </form>

      <h4>Invite new guardian</h4>
      <form method="post" action="{{ route('platform.students.guardians.store', $student) }}">
        @csrf
        <input type="hidden" name="mode" value="invite">
        <div class="grid g2">
          <div><label>Full name</label><input name="full_name" required value="{{ old('mode') === 'invite' ? old('full_name') : '' }}"></div>
          <div><label>Email</label><input name="email" type="email" required value="{{ old('mode') === 'invite' ? old('email') : '' }}"></div>
          <div><label>Phone</label><input name="phone" value="{{ old('mode') === 'invite' ? old('phone') : '' }}"></div>
          <div><label>Relationship</label><input name="relationship" value="{{ old('mode') === 'invite' ? old('relationship') : '' }}"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;width:auto;margin:8px 0">
          <input type="checkbox" name="is_primary" value="1" style="width:auto"> Primary guardian
        </label>
        @error('full_name')<div class="err">{{ $message }}</div>@enderror
        <button class="btn" type="submit">Invite &amp; link</button>
      </form>
    </div>
  </div>
@endsection
