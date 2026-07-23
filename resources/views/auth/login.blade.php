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
    <svg viewBox="0 0 100 100" role="img" aria-label="PearlEdu">
      <g id="vx-preloader-lines" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
        <line data-index="-10" x1="35.97" y1="6.19" x2="64.03" y2="6.19"/>
        <line data-index="-9" x1="26.31" y1="10.57" x2="73.69" y2="10.57"/>
        <line data-index="-8" x1="20.21" y1="14.95" x2="79.79" y2="14.95"/>
        <line data-index="-7" x1="15.71" y1="19.33" x2="84.29" y2="19.33"/>
        <line data-index="-6" x1="12.25" y1="23.71" x2="87.75" y2="23.71"/>
        <line data-index="-5" x1="9.55" y1="28.1" x2="90.45" y2="28.1"/>
        <line data-index="-4" x1="7.47" y1="32.48" x2="92.53" y2="32.48"/>
        <line data-index="-3" x1="5.92" y1="36.86" x2="94.08" y2="36.86"/>
        <line data-index="-2" x1="4.84" y1="41.24" x2="95.16" y2="41.24"/>
        <line data-index="-1" x1="4.21" y1="45.62" x2="95.79" y2="45.62"/>
        <line data-index="0" x1="4" y1="50" x2="96" y2="50"/>
        <line data-index="1" x1="4.21" y1="54.38" x2="95.79" y2="54.38"/>
        <line data-index="2" x1="4.84" y1="58.76" x2="95.16" y2="58.76"/>
        <line data-index="3" x1="5.92" y1="63.14" x2="94.08" y2="63.14"/>
        <line data-index="4" x1="7.47" y1="67.52" x2="92.53" y2="67.52"/>
        <line data-index="5" x1="9.55" y1="71.9" x2="90.45" y2="71.9"/>
        <line data-index="6" x1="12.25" y1="76.29" x2="87.75" y2="76.29"/>
        <line data-index="7" x1="15.71" y1="80.67" x2="84.29" y2="80.67"/>
        <line data-index="8" x1="20.21" y1="85.05" x2="79.79" y2="85.05"/>
        <line data-index="9" x1="26.31" y1="89.43" x2="73.69" y2="89.43"/>
        <line data-index="10" x1="35.97" y1="93.81" x2="64.03" y2="93.81"/>
      </g>
    </svg>
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
        <form method="post" action="/login">
          @csrf
          <label>Email or phone</label><input name="identifier" type="text" value="{{ old('identifier') }}" required autofocus autocomplete="username" placeholder="you@school.com or 07…">
          <label>Password</label><input name="password" type="password" required>
          <label class="vx-auth-remember"><input type="checkbox" name="remember"> Remember me</label>
          @error('identifier')<div class="err">{{ $message }}</div>@enderror
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
<style>
  .vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  .vx-preloader{position:fixed;inset:0;z-index:999;display:flex;align-items:center;justify-content:center;background:var(--bg,#F4F4EF);color:#B5652F}
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
