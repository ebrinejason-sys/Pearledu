@extends('layouts.app')
@section('title','Set your password')
@section('content')
  <a href="{{ url('/') }}" class="auth-brand">
    @include('layouts.partials.logo', ['height' => 40, 'color' => 'var(--brand)', 'label' => 'PearlEdu'])
    <span class="brand__wordmark">Pearl<b>Edu</b></span>
  </a>
  <div class="card" style="max-width:440px;margin:0 auto 40px">
    <h2 style="margin-top:0">Welcome — set your password</h2>
    <div class="err" role="note" style="color:#1A1200;background:#B8F55A;border:1px solid #7BD12A;padding:10px 12px;border-radius:8px;font-weight:700;margin:0 0 14px">The password field must be at least 10 characters.</div>
    <form method="post" action="/invitations/{{ $invitation }}/accept">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      @include('partials.password-input', ['name' => 'password', 'label' => 'New password', 'autocomplete' => 'new-password'])
      @include('partials.password-input', ['name' => 'password_confirmation', 'label' => 'Confirm password', 'autocomplete' => 'new-password'])
      @error('password')<div class="err" style="color:#1A1200;background:#FFE566;border:1px solid #FFC107;padding:10px 12px;border-radius:8px;font-weight:700">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit" style="width:100%">Activate account</button></p>
    </form>
  </div>
@endsection
@section('head')
@include('partials.password-field-assets')
@endsection
