@extends('layouts.app')
@section('title', 'School identity · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Setup</p>
      <h1 class="page-header__title">School identity</h1>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Badge and logo appear on report cards and school printouts.</p>
    </div>
  </div>

  <div class="grid g2">
    <div class="card">
      <form method="post" action="{{ route('app.settings.school.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <label>School name</label>
        <input name="name" value="{{ old('name', $school->name) }}" required>
        <label>Motto</label>
        <input name="motto" value="{{ old('motto', $school->motto) }}" placeholder="Excellence through knowledge">
        <label>Badge text (short, e.g. crest letters)</label>
        <input name="badge_text" value="{{ old('badge_text', $school->badge_text) }}" maxlength="12" placeholder="{{ strtoupper(substr($school->name, 0, 3)) }}">
        <label>Address</label>
        <input name="address" value="{{ old('address', $school->address) }}">
        <label>District</label>
        <input name="district" value="{{ old('district', $school->district) }}">
        <label>EMIS number</label>
        <input name="emis_number" value="{{ old('emis_number', $school->emis_number) }}">
        <label>School logo / crest</label>
        <input type="file" name="logo" accept="image/*">
        @if($school->logo_path)
          <label style="display:flex;align-items:center;gap:8px;margin-top:8px">
            <input type="checkbox" name="remove_logo" value="1"> Remove current logo
          </label>
        @endif
        @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
        <p style="margin-top:14px"><button class="btn" type="submit">Save identity</button></p>
      </form>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Card preview</h3>
      @include('partials.school-badge', ['school' => $school, 'size' => 'lg'])
      <p style="margin:14px 0 0;color:var(--muted);font-size:13px">{{ $school->motto ?: 'Add a motto to show under the name.' }}</p>
      <p style="margin:6px 0 0;color:var(--muted);font-size:12px">{{ $school->address ?: $school->district }}</p>
    </div>
  </div>
@endsection
