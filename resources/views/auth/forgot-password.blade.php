@extends('layouts.auth')
@section('title','Forgot password')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        @include('layouts.partials.logo', ['height' => 36, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Forgot password</h1>
        <p class="vx-auth-lead">Enter your email and we will send you a link to choose a new password.</p>
        @if(session('status'))<div class="vx-auth-status">{{ session('status') }}</div>@endif
        <form method="post" action="{{ route('password.email') }}">
          @csrf
          <label>Email</label><input name="email" type="email" value="{{ old('email') }}" required autofocus>
          @error('email')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Send reset link</button>
        </form>
        <p class="vx-auth-links"><a href="{{ route('login') }}">Back to sign in</a></p>
      </div>
    </div>
    <div class="vx-auth-stage">
      <div class="vx-stage-inner">
        <div class="vx-illustration-card">
          <img src="{{ asset('images/auth/login-illustration.png') }}" alt="" class="vx-login-illustration">
        </div>
        <p class="vx-stage-copy"><strong>Secure account recovery.</strong><br>Reset links expire after one hour and can only be used once.</p>
      </div>
    </div>
  </div>
@endsection
@section('head')
@include('auth.partials.auth-styles')
@endsection
