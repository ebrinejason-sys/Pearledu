{{-- resources/views/auth/two-factor-recovery-codes.blade.php --}}
@extends('layouts.auth')
@section('title','Save your recovery codes')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <div class="vx-auth-card">
        <h1>Save your recovery codes</h1>
        <p>Each code works once, if you lose access to both your authenticator app and your email. Save them somewhere safe now — they will not be shown again.</p>
        <ul>
          @foreach($codes as $code)
            <li><code>{{ $code }}</code></li>
          @endforeach
        </ul>
        <a class="btn" href="{{ route('platform.dashboard') }}">I've saved these — continue</a>
      </div>
    </div>
  </div>
@endsection
