@extends('layouts.app')
@section('title', 'Add student')
@section('content')
  <h2>Add student</h2>
  <div class="card">
    <form method="post" action="{{ route('app.students.store') }}" enctype="multipart/form-data">
      @csrf
      @include('app.students._form')

      @if(!empty($canApplyFees))
        @include('app.students._fee_attach', [
          'layout' => 'form',
          'canApplyFees' => true,
          'applyableStructures' => $applyableStructures ?? collect(),
          'invoicedStructureIds' => [],
        ])
      @endif

      <fieldset style="border:1px solid var(--line);border-radius:var(--radius-sm);padding:14px;margin-top:18px">
        <legend style="font-size:14px;padding:0 6px">Guardian (same window)</legend>
        <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Capture the related parent or guardian now. You can add more on the learner profile after saving.</p>
        <div class="grid g2">
          <div>
            <label>Guardian full name</label>
            <input name="guardian_full_name" value="{{ old('guardian_full_name') }}" autocomplete="name">
            @error('guardian_full_name')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div>
            <label>Guardian email</label>
            <input name="guardian_email" type="email" value="{{ old('guardian_email') }}" autocomplete="email">
            @error('guardian_email')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div>
            <label>Guardian phone</label>
            <input name="guardian_phone" value="{{ old('guardian_phone') }}" autocomplete="tel">
          </div>
          <div>
            <label>Guardian NIN</label>
            <input name="guardian_nin" value="{{ old('guardian_nin') }}" autocomplete="off" minlength="10" maxlength="20">
            @error('guardian_nin')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div>
            <label>Relationship</label>
            <input name="guardian_relationship" value="{{ old('guardian_relationship') }}" placeholder="mother, father, …">
          </div>
          <div>
            <label>Guardian photo</label>
            <input type="file" name="guardian_photo" accept="image/*" capture="user">
            @error('guardian_photo')<div class="err">{{ $message }}</div>@enderror
          </div>
        </div>
      </fieldset>

      <p style="margin-top:16px">
        <button class="btn" type="submit">Create</button>
        <a class="btn ghost" href="{{ route('app.students.index') }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
