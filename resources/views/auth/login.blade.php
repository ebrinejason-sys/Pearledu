@extends('layouts.auth')
@section('title','Sign in')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        <img src="{{ asset('images/brand/logo.png') }}" alt="" width="40" height="40">
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
      <div id="vx-login-avatar-3d" class="vx-login-avatar-3d"></div>
      <p class="vx-sr-only">Decorative 3D figure, no functional purpose.</p>
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
  .vx-auth-card .btn{width:100%;margin-top:20px;background:var(--accent);color:var(--ink)}
  .vx-auth-card .err{color:#FFD3D3;font-size:13px;margin-top:6px}
  .vx-auth-stage{flex:1;background:var(--surface);display:flex;align-items:center;justify-content:center}
  .vx-login-avatar-3d{width:320px;height:420px}
  .vx-login-avatar-3d canvas{display:block;margin:0 auto}
  .vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  @media(max-width:860px){
    .vx-auth-split{flex-direction:column;min-height:auto}
    .vx-auth-panel{flex:none;max-width:none;min-width:0;padding:32px 24px}
    .vx-auth-stage{display:none}
  }
</style>
<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.170.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.170.0/examples/jsm/"
  }
}
</script>
<script type="module">
  if (window.matchMedia('(min-width: 861px)').matches) {
    import('/js/vx-avatar-loader.js').then(function (mod) {
      mod.mountAvatar({
        container: 'vx-login-avatar-3d',
        mode: 'idle',
        width: 320,
        height: 420,
        colorVars: ['--brand', '--accent'],
        colorFallbacks: ['#13443A', '#DDA22E']
      });
    });
  }
</script>
@endsection
