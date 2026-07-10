{{-- resources/views/auth/two-factor-challenge.blade.php --}}
@extends('layouts.auth')
@section('title','Verify it\'s you')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <div class="vx-auth-card">
        <h1>Verify it's you</h1>
        @if(session('status'))<div class="vx-auth-status">{{ session('status') }}</div>@endif
        <form method="post" action="/login/2fa/challenge">
          @csrf
          <label>Authenticator code</label>
          <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus>
          @error('code')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Verify</button>
        </form>
        <form method="post" action="/login/2fa/email">
          @csrf
          <button class="btn-link" type="submit">Send a code to my email instead</button>
        </form>
        <details>
          <summary>Use a recovery code instead</summary>
          <form method="post" action="/login/2fa/challenge">
            @csrf
            <label>Recovery code</label>
            <input name="recovery_code" type="text" autocomplete="off">
            @error('recovery_code')<div class="err">{{ $message }}</div>@enderror
            <button class="btn" type="submit">Verify</button>
          </form>
        </details>
      </div>
    </div>
  </div>
@endsection
