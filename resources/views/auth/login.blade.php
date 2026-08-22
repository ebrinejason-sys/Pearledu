@extends('layouts.auth')
@section('title','Sign in')
@section('content')
  <script>
    (function () {
      try {
        var lastShown = parseInt(sessionStorage.getItem('vx-preloader-shown-at'), 10);
        if (lastShown && (Date.now() - lastShown) < 60000) {
          document.documentElement.classList.add('vx-preloader-skip');
        }
      } catch (e) {}
    })();
  </script>
  <div id="vx-preloader" class="vx-preloader" aria-hidden="true">
    <div id="vx-preloader-lines">
      @include('layouts.partials.logo', ['height' => 160, 'color' => 'currentColor', 'label' => 'PearlEdu', 'indexed' => true])
    </div>
  </div>
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        @include('layouts.partials.logo', ['height' => 36, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Sign in</h1>
        <p style="margin:0 0 14px;font-size:14px;color:var(--muted, #5a6b75)">One login for every school — your account opens your school’s dashboard.</p>
        @if(session('status'))<div class="vx-auth-status">{{ session('status') }}</div>@endif
        <div class="vx-auth-hint" role="note">The password field must be at least 10 characters.</div>
        <form method="post" action="/login">
          @csrf
          <label>Email or phone</label><input name="identifier" type="text" value="{{ old('identifier') }}" required autofocus autocomplete="username" placeholder="you@school.com or 07…">
          @include('partials.password-input', ['name' => 'password', 'label' => 'Password', 'autocomplete' => 'current-password'])
          <label class="vx-auth-remember"><input type="checkbox" name="remember"> Stay signed in on this device</label>
          <p class="vx-auth-idle-note">You are signed out after {{ (int) config('session.lifetime') }} minutes of inactivity, even if this box is checked.</p>
          @error('identifier')<div class="err">{{ $message }}</div>@enderror
          @error('password')<div class="err">{{ $message }}</div>@enderror
          <p style="margin:10px 0 0;font-size:13px"><a href="{{ route('password.request') }}" style="color:var(--sidebar-ink,#9FE7F5)">Forgot password?</a></p>
          <button class="btn" type="submit">Sign in</button>
        </form>
      </div>
    </div>
    <div class="vx-auth-stage">
      <div class="vx-stage-inner">
        <img src="{{ asset('images/auth/login-illustration.png') }}" alt="" class="vx-login-illustration" width="640" height="480">
        <p class="vx-stage-copy"><strong>Run your whole school from one dashboard.</strong><br>Academics, attendance, fees and communication — together.</p>
      </div>
    </div>
  </div>
@endsection
@section('head')
@include('auth.partials.auth-styles')
@include('partials.password-field-assets')
<style>
  .vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  .vx-preloader{position:fixed;inset:0;z-index:999;display:flex;align-items:center;justify-content:center;background:var(--bg,#F4F4EF);color:var(--brand,#053F5C)}
  .vx-preloader svg{width:160px;height:160px}
  html.vx-preloader-skip .vx-preloader{display:none}
</style>
<script type="module">
  var preloaderEl = document.getElementById('vx-preloader');
  if (preloaderEl) {
    if (document.documentElement.classList.contains('vx-preloader-skip')) {
      preloaderEl.remove();
    } else {
      import('/js/vx-preloader.js').then(function (mod) {
        mod.runPreloader(preloaderEl, {
          onDone: function () {
            preloaderEl.remove();
            try { sessionStorage.setItem('vx-preloader-shown-at', String(Date.now())); } catch (e) {}
          }
        });
      }).catch(function () {
        preloaderEl.remove();
      });
    }
  }
</script>
@endsection
