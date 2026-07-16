@extends('layouts.auth')
@section('title','Verify it\'s you')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        @include('layouts.partials.logo', ['height' => 36, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Check your email</h1>
        <p class="vx-auth-lead">
          We sent a 6-digit sign-in code to <strong>{{ $email }}</strong>.
          Enter it below to finish signing in.
        </p>
        @if(session('status'))<div class="vx-auth-status">{{ session('status') }}</div>@endif
        <form method="post" action="/login/2fa/challenge">
          @csrf
          <label>Email code</label>
          <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus placeholder="6-digit code">
          @error('code')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Verify and continue</button>
        </form>
        <form method="post" action="/login/2fa/email" style="margin-top:14px">
          @csrf
          <button class="btn-link" type="submit">Resend code</button>
        </form>
        @if($hasAuthenticator)
          <div class="vx-auth-alt">
            <details>
              <summary>Use authenticator app instead</summary>
              <form method="post" action="/login/2fa/challenge" style="margin-top:12px">
                @csrf
                <label>Authenticator code</label>
                <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code">
                @error('code')<div class="err">{{ $message }}</div>@enderror
                <button class="btn" type="submit">Verify</button>
              </form>
            </details>
            <details>
              <summary>Use a recovery code instead</summary>
              <form method="post" action="/login/2fa/challenge" style="margin-top:12px">
                @csrf
                <label>Recovery code</label>
                <input name="recovery_code" type="text" autocomplete="off">
                @error('recovery_code')<div class="err">{{ $message }}</div>@enderror
                <button class="btn" type="submit">Verify</button>
              </form>
            </details>
          </div>
        @endif
      </div>
    </div>
    <div class="vx-auth-stage">
      <div class="vx-stage-inner">
        <div class="vx-illustration-card">
          <img src="{{ asset('images/auth/login-illustration.png') }}" alt="" class="vx-login-illustration">
        </div>
        <p class="vx-stage-copy"><strong>One more step to protect your console.</strong><br>Platform sign-in always verifies your email before you can manage schools.</p>
      </div>
    </div>
  </div>
@endsection
@section('head')
@include('auth.partials.auth-styles')
@endsection
