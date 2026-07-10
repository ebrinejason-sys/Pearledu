{{-- resources/views/auth/two-factor-setup.blade.php --}}
@extends('layouts.auth')
@section('title','Set up your authenticator')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <div class="vx-auth-card">
        <h1>Set up your authenticator</h1>
        <p>Scan this QR code with Google Authenticator, Authy, or any TOTP app, then enter the 6-digit code it shows.</p>
        <div>{!! $qrSvg !!}</div>
        <p>Can't scan? Enter this key manually: <code>{{ $manualKey }}</code></p>
        <form method="post" action="/login/2fa/setup">
          @csrf
          <label>6-digit code</label>
          <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus>
          @error('code')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Confirm and continue</button>
        </form>
      </div>
    </div>
  </div>
@endsection
