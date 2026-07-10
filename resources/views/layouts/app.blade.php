<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', config('app.name'))</title>
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<style>
  {!! $themeCss !!}
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:var(--font,'Inter',sans-serif);background:var(--bg);color:var(--ink);line-height:1.5}
  a{color:var(--brand);text-decoration:none}
  .wrap{flex:1;min-width:0;max-width:1140px;padding:24px 20px 40px}
  .impersonation-banner{background:#fff4e5;border-bottom:1px solid #f0c674;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
  .impersonation-banner__text{font-size:14px;color:#5c3d00}
  .impersonation-banner__meta{display:block;font-size:12px;color:#8a5a00;margin-top:2px}
  .impersonation-banner__btn{background:#5c3d00;color:#fff;border:0;border-radius:var(--radius);padding:8px 14px;font-weight:600;cursor:pointer;font-size:13px}
  .app-header{background:var(--surface);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:40}
  .app-header__row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 20px}
  .topbar{background:var(--surface);border-bottom:1px solid var(--line);padding:14px 24px;display:flex;align-items:center;gap:14px}
  .brand{display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:var(--brand);white-space:nowrap}
  .brand__mark{display:block;width:36px;height:36px;object-fit:contain;flex-shrink:0}
  .brand__mark--lg{width:56px;height:56px}
  .brand__wordmark{font-weight:800;font-size:18px;color:var(--brand)}
  .brand__wordmark b{color:var(--accent)}
  .auth-brand{display:flex;flex-direction:column;align-items:center;gap:10px;margin:24px 0 8px;text-decoration:none}
  .auth-brand .brand__wordmark{font-size:22px}
  .context-pill{font-size:12px;font-weight:600;color:var(--brand);background:var(--accent-soft);border:1px solid var(--line);border-radius:999px;padding:4px 12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .context-pill--platform{color:var(--muted);background:var(--bg)}
  .user-menu{margin-left:auto;position:relative}
  .user-menu__details{position:relative}
  .user-menu__trigger{list-style:none;display:flex;align-items:center;gap:10px;cursor:pointer;padding:6px 10px 6px 6px;border-radius:999px;border:1px solid var(--line);background:var(--surface)}
  .user-menu__trigger::-webkit-details-marker{display:none}
  .user-menu__avatar{width:34px;height:34px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
  .user-menu__meta{display:flex;flex-direction:column;line-height:1.25;text-align:left;min-width:0}
  .user-menu__name{font-size:13px;font-weight:700;color:var(--ink);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .user-menu__role{font-size:11px;color:var(--muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .user-menu__panel{position:absolute;right:0;top:calc(100% + 8px);min-width:240px;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:0 12px 32px rgba(0,0,0,.1);padding:8px;z-index:100}
  .user-menu__email{font-size:12px;color:var(--muted);margin:4px 10px 10px;word-break:break-all}
  .user-menu__link{display:block;width:100%;text-align:left;padding:10px 12px;border-radius:calc(var(--radius) - 2px);font-size:14px;font-weight:600;color:var(--ink);background:transparent;border:0;cursor:pointer}
  .user-menu__link:hover,.user-menu__link.active{background:var(--accent-soft);color:var(--brand)}
  .user-menu__link--danger{color:#b3261e}
  .user-menu__link--danger:hover{background:#fdecea}

  .sidebar-toggle{display:flex;align-items:center;gap:8px;background:transparent;border:0;cursor:pointer;color:inherit;font:inherit;padding:8px;border-radius:var(--radius)}
  .sidebar-toggle--mobile{display:none;font-size:20px;color:var(--brand)}
  .sidebar-toggle--desktop{width:100%;color:var(--sidebar-ink);opacity:.85;font-size:13px;font-weight:600}
  .sidebar-toggle--desktop:hover{background:rgba(255,255,255,.08);opacity:1}
  .sidebar-toggle--desktop svg{width:18px;height:18px;flex-shrink:0;transition:transform .15s}

  .app-shell{display:flex;align-items:flex-start}
  .sidebar-backdrop{display:none}
  .sidebar{width:230px;flex-shrink:0;background:var(--sidebar);color:var(--sidebar-ink);min-height:calc(100vh - 61px);position:sticky;top:61px;display:flex;flex-direction:column;justify-content:space-between;transition:width .15s}
  .sidebar__nav{padding:16px 10px;overflow-y:auto}
  .sidebar__section{margin-bottom:18px}
  .sidebar__section-label{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--sidebar-ink);opacity:.6;margin:0 0 6px;padding:0 10px}
  .sidebar__list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:2px}
  .sidebar__link{display:flex;align-items:center;gap:12px;padding:9px 10px;border-radius:var(--radius);color:var(--sidebar-ink);font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden}
  .sidebar__link:hover{background:rgba(255,255,255,.08)}
  .sidebar__link.active{background:rgba(255,255,255,.14);color:#fff}
  .sidebar__link--cta{color:var(--accent)}
  .sidebar__icon{display:flex;flex-shrink:0}
  .sidebar__icon svg{width:19px;height:19px}
  .sidebar__label{overflow:hidden;text-overflow:ellipsis}
  .sidebar__footer{padding:10px;border-top:1px solid rgba(255,255,255,.12);display:flex;flex-direction:column;gap:2px}

  body.sidebar-collapsed .sidebar{width:60px}
  body.sidebar-collapsed .sidebar__section-label,
  body.sidebar-collapsed .sidebar__label{display:none}
  body.sidebar-collapsed .sidebar__link{justify-content:center}
  body.sidebar-collapsed .sidebar-toggle--desktop{justify-content:center}
  body.sidebar-collapsed .sidebar-toggle--desktop svg{transform:rotate(180deg)}

  .page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
  .page-header__eyebrow{font-size:13px;color:var(--muted);margin:0 0 4px}
  .page-header__title{margin:0;font-size:28px;line-height:1.2}
  .page-header__actions{display:flex;gap:8px;align-items:center}
  @media(max-width:800px){
    .user-menu__meta{display:none}
    .page-header__title{font-size:22px}
    .sidebar-toggle--mobile{display:flex}
    .sidebar{position:fixed;top:0;left:0;height:100vh;z-index:60;transform:translateX(-100%);width:230px}
    body.sidebar-open .sidebar{transform:translateX(0)}
    body.sidebar-open .sidebar-backdrop{display:block;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:50}
    body.sidebar-collapsed .sidebar{width:230px}
    body.sidebar-collapsed .sidebar__section-label,
    body.sidebar-collapsed .sidebar__label{display:block}
    body.sidebar-collapsed .sidebar__link{justify-content:flex-start}
    .sidebar-toggle--desktop{display:none}
  }
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
  .pill--muted{background:var(--bg);color:var(--muted)}
</style>
</head>
<body class="{{ request()->cookie('sidebar_collapsed') === '1' ? 'sidebar-collapsed' : '' }}">
  @auth
    @php($nav = $nav ?? app(\App\Services\Navigation\NavigationBuilder::class)->build(auth()->user()))
    @include('layouts.partials.topbar', ['nav' => $nav])
    <div class="app-shell">
      @include('layouts.partials.sidebar', ['nav' => $nav])
      <div class="wrap">
        @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
        @yield('content')
      </div>
    </div>
  @else
    <div class="topbar">
      @include('layouts.partials.brand', ['brandHref' => url('/login')])
    </div>
    <div class="wrap">
      @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
      @yield('content')
    </div>
  @endauth
</body>
</html>
