@extends('layouts.app')
@section('title','Add PearlEdu staff')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.operators.index') }}">PearlEdu staff</a></p>
      <h2 class="page-header__title">Add staff member</h2>
    </div>
  </div>

  <div class="card" style="max-width:640px">
    <form method="post" action="{{ route('platform.operators.store') }}">
      @csrf
      <label>Full name</label>
      <input name="full_name" value="{{ old('full_name') }}" required>
      @error('full_name')<div class="err">{{ $message }}</div>@enderror

      <label>Email</label>
      <input name="email" type="email" value="{{ old('email') }}" required>
      @error('email')<div class="err">{{ $message }}</div>@enderror

      <label>Phone (optional)</label>
      <input name="phone" value="{{ old('phone') }}">

      <label>Role</label>
      <select name="role_key" required>
        @foreach($roles as $key => $label)
          <option value="{{ $key }}" @selected(old('role_key', 'support_agent') === $key)>{{ $label }}</option>
        @endforeach
      </select>
      <p style="margin:6px 0 12px;font-size:13px;color:var(--muted)">
        <strong>Platform Admin</strong> — full console ·
        <strong>Platform Ops</strong> — schools &amp; onboarding ·
        <strong>EMIS Data Entrant</strong> — enter school data via workspace ·
        <strong>Support Agent</strong> — handle support tickets
      </p>

      <label>Password (optional)</label>
      <input name="password" type="password" minlength="10" autocomplete="new-password" placeholder="Leave blank to auto-generate">
      <p style="margin:6px 0 0;font-size:12px;color:var(--muted)">If blank, a temporary password is shown once after create.</p>

      @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
      <p style="margin-top:16px">
        <button class="btn" type="submit">Create staff account</button>
        <a class="btn ghost" href="{{ route('platform.operators.index') }}">Cancel</a>
      </p>
    </form>
  </div>
@endsection
