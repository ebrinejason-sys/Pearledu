@extends('layouts.app')
@section('title', 'Apply · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h1 class="page-header__title">Apply for admission</h1>
    </div>
  </div>
  <div class="card" style="max-width:560px">
    <form method="post" action="{{ route('public.admissions.store') }}">@csrf
      <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
      <label>Applicant full name</label><input name="applicant_name" value="{{ old('applicant_name') }}" required>
      <label>Guardian name</label><input name="guardian_name" value="{{ old('guardian_name') }}">
      <label>Guardian phone</label><input name="guardian_phone" value="{{ old('guardian_phone') }}">
      <label>Guardian email</label><input type="email" name="guardian_email" value="{{ old('guardian_email') }}">
      <label>Requested class</label>
      <select name="requested_class_id">
        <option value="">—</option>
        @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('requested_class_id') == $c->id)>{{ $c->name }}</option>@endforeach
      </select>
      <label>Notes</label><textarea name="notes" rows="3">{{ old('notes') }}</textarea>
      @include('partials.turnstile', ['errorClass' => 'err'])
      @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
      <p style="margin-top:14px"><button class="btn" type="submit">Submit application</button></p>
    </form>
  </div>
@endsection
