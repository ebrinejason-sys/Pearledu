@extends('layouts.app')
@section('title','Account')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">You</p>
      <h2 class="page-header__title">Account</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">Manage your profile, photo, password, and appearance.</p>
    </div>
  </div>

  @if(session('status'))<div class="status">{{ session('status') }}</div>@endif

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Profile</h3>
      <form method="post" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div style="display:flex;gap:14px;align-items:center;margin-bottom:14px">
          @if(auth()->user()->avatarUrl())
            <img src="{{ auth()->user()->avatarUrl() }}" alt="" width="64" height="64" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1px solid var(--line)">
          @else
            <span class="user-menu__avatar" style="width:64px;height:64px;font-size:24px;display:inline-flex;align-items:center;justify-content:center" aria-hidden="true">{{ auth()->user()->avatarInitial() }}</span>
          @endif
          <div style="flex:1;min-width:0">
            <label>Profile photo</label>
            <input type="file" name="avatar" accept="image/*">
            @if(auth()->user()->avatar_path)
              <label style="display:flex;align-items:center;gap:8px;margin-top:8px;font-weight:500">
                <input type="checkbox" name="remove_avatar" value="1"> Remove photo
              </label>
            @endif
          </div>
        </div>
        <label>Full name</label>
        <input name="full_name" value="{{ old('full_name', auth()->user()->full_name) }}" required>
        @error('full_name')<div class="err">{{ $message }}</div>@enderror
        <label>Email</label>
        <input name="email" type="email" value="{{ old('email', auth()->user()->email) }}">
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <label>Phone</label>
        <input name="phone" value="{{ old('phone', auth()->user()->phone) }}">
        @error('phone')<div class="err">{{ $message }}</div>@enderror
        <label>Personal theme override</label>
        <select name="preferred_theme">
          <option value="">Use school / default theme</option>
          @foreach($themes as $key => $theme)
            <option value="{{ $key }}" @selected(old('preferred_theme', auth()->user()->preferred_theme) === $key)>{{ $theme['label'] ?? $key }}</option>
          @endforeach
        </select>
        <p style="margin:6px 0 0;font-size:12px;color:var(--muted)">Optional. School admins set the school-wide theme under School identity.</p>
        @error('avatar')<div class="err">{{ $message }}</div>@enderror
        <p style="margin-top:14px"><button class="btn" type="submit">Save profile</button></p>
      </form>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Change password</h3>
      <form method="post" action="{{ route('account.password.update') }}">
        @csrf @method('PUT')
        @include('partials.password-input', ['name' => 'current_password', 'label' => 'Current password', 'autocomplete' => 'current-password'])
        @error('current_password')<div class="err">{{ $message }}</div>@enderror
        @include('partials.password-input', ['name' => 'password', 'label' => 'New password', 'autocomplete' => 'new-password'])
        @error('password')<div class="err">{{ $message }}</div>@enderror
        @include('partials.password-input', ['name' => 'password_confirmation', 'label' => 'Confirm new password', 'autocomplete' => 'new-password', 'required' => true])
        <p style="margin-top:14px"><button class="btn" type="submit">Update password</button></p>
      </form>
      <p style="margin:18px 0 0;font-size:13px;color:var(--muted)">
        Forgot your password while signed out?
        <a href="{{ route('password.request') }}">Reset via email</a>.
      </p>
    </div>
  </div>

  <div class="card" style="border-color:#e6b4b0">
    <h3 style="color:#b3261e">Delete account</h3>
    <p style="color:var(--muted)">This permanently erases your personal data and removes your access everywhere. A child's school academic record is retained by the school but unlinked from your account. This cannot be undone.</p>
    <form method="post" action="{{ route('account.destroy') }}">
      @csrf @method('DELETE')
      @include('partials.password-input', ['name' => 'password', 'label' => 'Password', 'autocomplete' => 'current-password'])
      <label>Type DELETE to confirm</label><input name="confirm" required style="max-width:320px">
      @error('password')<div class="err">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit" style="background:#b3261e">Permanently delete my account</button></p>
    </form>
  </div>
@endsection

@section('head')
@include('partials.password-field-assets')
@endsection
