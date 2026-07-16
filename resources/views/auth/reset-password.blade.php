@extends('layouts.auth')
@section('title','Choose a new password')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        @include('layouts.partials.logo', ['height' => 36, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Choose a new password</h1>
        <p class="vx-auth-lead">Use at least 10 characters. After saving, you can sign in right away.</p>
        <form method="post" action="{{ route('password.update') }}">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">
          <input type="hidden" name="email" value="{{ old('email', $email) }}">
          <label>New password</label><input name="password" type="password" required minlength="10" autofocus>
          <label>Confirm password</label><input name="password_confirmation" type="password" required minlength="10">
          @error('email')<div class="err">{{ $message }}</div>@enderror
          @error('password')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Update password</button>
        </form>
        <p class="vx-auth-links"><a href="{{ route('login') }}">Back to sign in</a></p>
      </div>
    </div>
    <div class="vx-auth-stage">
      <div class="vx-stage-inner">
        <div class="vx-illustration-card">
          <img src="{{ asset('images/auth/login-illustration.png') }}" alt="" class="vx-login-illustration">
        </div>
        <p class="vx-stage-copy"><strong>Almost there.</strong><br>Pick a strong password you have not used here before.</p>
      </div>
    </div>
  </div>
@endsection
@section('head')
@include('auth.partials.auth-styles')
@endsection
