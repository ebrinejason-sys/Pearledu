@extends('layouts.auth')
@section('title','Set up your authenticator')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        @include('layouts.partials.logo', ['height' => 36, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Set up your authenticator</h1>
        <p class="vx-auth-lead">
          Scan this QR code with Google Authenticator, Authy, or any TOTP app, then enter the 6-digit code it shows.
        </p>
        @if(session('status'))<div class="vx-auth-status">{{ session('status') }}</div>@endif
        <div class="vx-auth-qr">{!! $qrSvg !!}</div>
        <p class="vx-auth-manual">Can't scan? Enter this key manually: <code>{{ $manualKey }}</code></p>
        <form method="post" action="/login/2fa/setup">
          @csrf
          <label>6-digit code</label>
          <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus placeholder="000000">
          @error('code')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Confirm and continue</button>
        </form>
        <form method="post" action="/login/2fa/setup/skip" style="margin-top:14px">
          @csrf
          <button class="btn-link" type="submit">Continue without authenticator</button>
        </form>
      </div>
    </div>
    <div class="vx-auth-stage">
      <div class="vx-stage-inner">
        <div class="vx-illustration-card">
          <img src="{{ asset('images/auth/login-illustration.png') }}" alt="" class="vx-login-illustration">
        </div>
        <p class="vx-stage-copy"><strong>Optional, but recommended.</strong><br>An authenticator app keeps your platform account safer if email is ever compromised.</p>
      </div>
    </div>
  </div>
@endsection
@section('head')
@include('auth.partials.auth-styles')
@endsection
