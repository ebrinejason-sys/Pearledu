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
        <img src="{{ asset('images/brand/logo.svg') }}" alt="" width="40" height="40">
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Sign in</h1>
        <form method="post" action="/login">
          @csrf
          <label>Email</label><input name="email" type="email" value="{{ old('email') }}" required autofocus>
          <label>Password</label><input name="password" type="password" required>
          <label class="vx-auth-remember"><input type="checkbox" name="remember"> Remember me</label>
          @error('email')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Sign in</button>
        </form>
      </div>
    </div>
    <div class="vx-auth-stage">
      <div class="vx-stage-inner">
        <div class="vx-illustration-card">
          <img src="{{ asset('images/auth/login-illustration.png') }}" alt="" class="vx-login-illustration">
        </div>
        <p class="vx-stage-copy"><strong>Run your whole school from one dashboard.</strong><br>Academics, attendance, fees and communication — together.</p>
      </div>
    </div>
  </div>
@endsection
@section('head')
<style>
  .vx-auth-split{display:flex;min-height:100vh}
  .vx-auth-panel{flex:0 0 44%;max-width:480px;min-width:340px;background:var(--sidebar);color:var(--sidebar-ink);display:flex;flex-direction:column;justify-content:center;padding:48px;gap:28px}
  .vx-auth-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:20px;color:#fff}
  .vx-auth-brand span b{opacity:.8}
  .vx-auth-card h1{margin:0 0 18px;font-size:26px;color:#fff}
  .vx-auth-card label{display:block;color:var(--sidebar-ink);font-size:13px;margin:12px 0 4px}
  .vx-auth-card input{display:block;box-sizing:border-box;width:100%;padding:9px;border-radius:var(--radius);font:inherit;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);color:#fff}
  .vx-auth-card input::placeholder{color:rgba(255,255,255,.5)}
  .vx-auth-remember{display:flex;align-items:center;gap:8px;font-size:13px}
  .vx-auth-remember input{width:auto}
  .vx-auth-card .btn{width:100%;margin-top:20px;padding:12px 16px;background:var(--accent);color:var(--ink);border:0;border-radius:var(--radius);font:inherit;font-weight:700;font-size:15px;letter-spacing:.2px;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,.15),0 4px 12px rgba(0,0,0,.12);transition:background-color .15s ease,box-shadow .15s ease,transform .05s ease}
  .vx-auth-card .btn:hover{background:color-mix(in srgb,var(--accent) 88%,#fff)}
  .vx-auth-card .btn:active{transform:translateY(1px);box-shadow:0 1px 2px rgba(0,0,0,.15)}
  .vx-auth-card .btn:focus-visible{outline:2px solid #fff;outline-offset:2px}
  .vx-auth-card .err{color:#FFD3D3;font-size:13px;margin-top:6px}
  .vx-auth-stage{
    flex:1;display:flex;align-items:center;justify-content:center;padding:48px;
    background-color:var(--bg);
    background-image:radial-gradient(circle,rgba(19,68,58,.10) 1.5px,transparent 1.5px);
    background-size:22px 22px;
    position:relative;overflow:hidden;
  }
  .vx-auth-stage::before{
    content:"";position:absolute;width:640px;height:640px;border-radius:50%;
    background:radial-gradient(circle,var(--accent-soft) 0%,rgba(247,235,207,0) 70%);
    top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;
  }
  .vx-stage-inner{position:relative;max-width:400px;width:100%;text-align:center;animation:vx-stage-in .5s ease both}
  .vx-illustration-card{
    background:var(--surface);border-radius:calc(var(--radius) + 6px);padding:28px;
    box-shadow:0 24px 48px -16px rgba(19,68,58,.28),0 2px 6px rgba(19,68,58,.08);
    border-top:3px solid var(--accent);
  }
  .vx-login-illustration{display:block;max-width:100%;width:100%;height:auto}
  .vx-stage-copy{margin:22px 6px 0;font-size:14px;line-height:1.6;color:var(--muted)}
  .vx-stage-copy strong{display:block;margin-bottom:4px;font-size:16px;color:var(--ink)}
  @keyframes vx-stage-in{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
  @media(prefers-reduced-motion:reduce){.vx-stage-inner{animation:none}}
  .vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  .vx-preloader{position:fixed;inset:0;z-index:999;display:flex;align-items:center;justify-content:center;background:var(--bg,#F4F4EF);color:#B5652F}
  .vx-preloader svg{width:160px;height:160px}
  html.vx-preloader-skip .vx-preloader{display:none}
  @media(max-width:860px){
    .vx-auth-split{flex-direction:column;min-height:auto}
    .vx-auth-panel{flex:none;max-width:none;min-width:0;padding:32px 24px}
    .vx-auth-stage{display:none}
  }
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
