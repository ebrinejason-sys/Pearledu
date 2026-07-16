@extends('layouts.auth')
@section('title','Save your recovery codes')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        @include('layouts.partials.logo', ['height' => 36, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Save your recovery codes</h1>
        <p class="vx-auth-lead">
          Each code works once if you lose access to both your authenticator app and your email.
          Save them somewhere safe now — they will not be shown again.
        </p>
        <ul class="vx-auth-codes">
          @foreach($codes as $code)
            <li><code>{{ $code }}</code></li>
          @endforeach
        </ul>
        <a class="btn" href="{{ route('platform.dashboard') }}">I've saved these — continue</a>
      </div>
    </div>
    <div class="vx-auth-stage">
      <div class="vx-stage-inner">
        <div class="vx-illustration-card">
          <img src="{{ asset('images/auth/login-illustration.png') }}" alt="" class="vx-login-illustration">
        </div>
        <p class="vx-stage-copy"><strong>Store these offline.</strong><br>Treat recovery codes like passwords — anyone with one can sign in as you.</p>
      </div>
    </div>
  </div>
@endsection
@section('head')
@include('auth.partials.auth-styles')
@endsection
