<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', config('app.name'))</title>
<style>
  {!! $themeCss !!}
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:var(--font,'Inter',sans-serif);background:var(--bg);color:var(--ink);line-height:1.5}
  a{color:var(--brand);text-decoration:none}
  .wrap{max-width:1100px;margin:0 auto;padding:24px}
  .topbar{background:var(--surface);border-bottom:1px solid var(--line);padding:14px 24px;display:flex;align-items:center;gap:14px}
  .brand{font-weight:800;color:var(--brand)} .brand b{color:var(--accent)}
  .spacer{margin-left:auto}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:18px;margin-bottom:16px}
  .grid{display:grid;gap:16px} .g4{grid-template-columns:repeat(4,1fr)} .g2{grid-template-columns:repeat(2,1fr)}
  @media(max-width:800px){.g4,.g2{grid-template-columns:1fr}}
  .stat .v{font-size:26px;font-weight:800;color:var(--brand)} .stat .l{color:var(--muted);font-size:13px}
  .btn{display:inline-block;background:var(--brand);color:#fff;border:0;border-radius:var(--radius);padding:9px 15px;font-weight:600;cursor:pointer}
  .btn.accent{background:var(--accent);color:#3a2c08}
  .btn.ghost{background:transparent;color:var(--brand);border:1px solid var(--line)}
  table{width:100%;border-collapse:collapse} th,td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);font-size:14px}
  th{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em}
  input,select,textarea{width:100%;padding:9px;border:1px solid var(--line);border-radius:var(--radius);background:var(--surface);color:var(--ink);font:inherit}
  label{display:block;font-size:13px;color:var(--muted);margin:10px 0 4px}
  .status{background:var(--accent-soft);border:1px solid var(--accent);border-radius:var(--radius);padding:10px 14px;margin-bottom:14px;font-size:14px}
  .err{color:#b3261e;font-size:13px;margin-top:4px}
  .pill{display:inline-block;background:var(--accent-soft);color:var(--brand);border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600}
</style>
</head>
<body>
  <div class="topbar">
    <span class="brand">Pearl<b>Edu</b></span>
    <span class="pill">{{ ucfirst(app(\App\Services\Theme\ThemeManager::class)->activeKey()) }} theme</span>
    <span class="spacer"></span>
    @auth
      <a href="{{ route('account.settings') }}">Account</a>
      <form method="post" action="{{ route('logout') }}" style="display:inline">@csrf<button class="btn ghost">Sign out</button></form>
    @endauth
  </div>
  <div class="wrap">
    @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
    @yield('content')
  </div>
</body>
</html>
