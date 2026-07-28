@extends('layouts.app')
@section('title','Set your password')
@section('content')
  <a href="{{ url('/') }}" class="auth-brand">
    @include('layouts.partials.logo', ['height' => 40, 'color' => 'var(--brand)', 'label' => 'PearlEdu'])
    <span class="brand__wordmark">Pearl<b>Edu</b></span>
  </a>
  <div class="card" style="max-width:440px;margin:0 auto 40px">
    <h2 style="margin-top:0">Welcome — set your password</h2>
    <p style="color:var(--muted)">Choose a strong password (10+ characters) to activate your account.</p>
    <form method="post" action="/invitations/{{ $invitation }}/accept">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      @include('partials.password-input', ['name' => 'password', 'label' => 'New password', 'autocomplete' => 'new-password'])
      @include('partials.password-input', ['name' => 'password_confirmation', 'label' => 'Confirm password', 'autocomplete' => 'new-password'])
      @error('password')<div class="err">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit" style="width:100%">Activate account</button></p>
    </form>
  </div>
@endsection
@section('head')
@include('partials.password-field-assets')
@endsection
