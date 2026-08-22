<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', config('app.name'))</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
@auth
<meta name="idle-lifetime" content="{{ (int) config('session.lifetime') * 60 }}">
<meta name="idle-warning" content="{{ (int) config('session.idle_warning_minutes', 2) * 60 }}">
<meta name="idle-heartbeat" content="{{ route('session.heartbeat') }}">
<meta name="idle-login" content="{{ route('login') }}">
@endauth
@include('layouts.partials.favicons')
@include('layouts.partials.offline-head')
@if(!empty($themeFontUrl))
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $themeFontUrl }}" rel="stylesheet">
@endif
<style>
  {!! $themeCss !!}
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:var(--font);background:var(--bg);color:var(--ink);line-height:1.5;-webkit-font-smoothing:antialiased}
  :focus-visible{outline:3px solid var(--focus);outline-offset:2px}
  .skip-link{position:absolute;left:-9999px;top:8px;z-index:200;padding:10px 14px;background:var(--brand);color:var(--on-brand);border-radius:var(--radius-sm);font-weight:700}
  .skip-link:focus,.skip-link:focus-visible{left:12px}
  .breadcrumb{margin:0 0 14px;font-size:13px;color:var(--muted)}
  .breadcrumb ol{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:6px;align-items:center}
  .breadcrumb li:not(:last-child)::after{content:"/";margin-left:6px;color:var(--line)}
  .breadcrumb a{color:var(--muted)}
  .breadcrumb [aria-current="page"]{color:var(--ink);font-weight:600}
  .child-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:16px}
  .child-card{display:block;padding:14px;border:1px solid var(--line);border-radius:var(--radius);background:var(--surface);color:inherit}
  .child-card[aria-current="true"]{border-color:var(--brand);box-shadow:0 0 0 2px color-mix(in srgb, var(--brand) 25%, transparent)}
  .child-card strong{display:block;font-size:15px}
  .child-card span{display:block;margin-top:4px;font-size:13px;color:var(--muted)}
  .workspace-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
  a{color:var(--brand);text-decoration:none}
  .wrap{flex:1;min-width:0;max-width:1140px;padding:24px 20px 40px}
  .impersonation-banner{background:var(--warning-soft);border-bottom:1px solid var(--warning);padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
  .impersonation-banner__text{font-size:14px;color:var(--accent-ink)}
  .impersonation-banner__meta{display:block;font-size:12px;color:var(--warning);margin-top:2px}
  .impersonation-banner__btn{background:var(--accent-ink);color:var(--on-brand);border:0;border-radius:var(--radius-sm);padding:8px 14px;font-weight:600;cursor:pointer;font-size:13px}
  .app-header{background:var(--surface);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:40}
  .app-header__row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 20px}
  .topbar{background:var(--surface);border-bottom:1px solid var(--line);padding:14px 24px;display:flex;align-items:center;gap:14px}
  .brand{display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:var(--brand);white-space:nowrap}
  .vx-logo{display:block;flex-shrink:0;height:var(--vx-logo-h,28px);width:auto}
  .brand__wordmark{font-weight:800;font-size:18px;color:var(--brand)}
  .brand__wordmark b{color:var(--accent)}
  .auth-brand{display:flex;flex-direction:column;align-items:center;gap:10px;margin:24px 0 8px;text-decoration:none}
  .auth-brand .brand__wordmark{font-size:22px}
  @media(max-width:800px){
    .brand__wordmark{font-size:16px}
    .brand .vx-logo{--vx-logo-h:22px;height:22px}
    .wrap{padding:16px 14px 32px}
    .page-header{margin-bottom:16px}
    .page-header{margin-bottom:16px}
    .btn{min-height:44px;min-width:44px;display:inline-flex;align-items:center;justify-content:center}
    input,select,textarea{min-height:44px;font-size:16px}
    table{display:block;overflow-x:auto;-webkit-overflow-scrolling:touch}
  }
  .context-pill{font-size:12px;font-weight:600;color:var(--brand);background:var(--accent-soft);border:1px solid var(--line);border-radius:999px;padding:4px 12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .context-pill--platform{color:var(--muted);background:var(--bg)}
  .user-menu{margin-left:auto;position:relative}
  .user-menu__details{position:relative}
  .user-menu__trigger{list-style:none;display:flex;align-items:center;gap:10px;cursor:pointer;padding:6px 10px 6px 6px;border-radius:999px;border:1px solid var(--line);background:var(--surface)}
  .user-menu__trigger::-webkit-details-marker{display:none}
  .user-menu__avatar{width:34px;height:34px;border-radius:50%;background:var(--brand);color:var(--on-brand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
  .user-menu__avatar-img{width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid var(--line)}
  .user-menu__meta{display:flex;flex-direction:column;line-height:1.25;text-align:left;min-width:0}
  .user-menu__name{font-size:13px;font-weight:700;color:var(--ink);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .user-menu__role{font-size:11px;color:var(--muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .user-menu__panel{position:absolute;right:0;top:calc(100% + 8px);min-width:240px;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:8px;z-index:100}
  .user-menu__email{font-size:12px;color:var(--muted);margin:4px 10px 10px;word-break:break-all}
  .user-menu__link{display:block;width:100%;text-align:left;padding:10px 12px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;color:var(--ink);background:transparent;border:0;cursor:pointer}
  .user-menu__link:hover,.user-menu__link.active{background:var(--brand-soft);color:var(--brand)}
  .user-menu__link--danger{color:var(--danger)}
  .user-menu__link--danger:hover{background:var(--danger-soft)}

  .sidebar-toggle{display:flex;align-items:center;gap:8px;background:transparent;border:0;cursor:pointer;color:inherit;font:inherit;padding:8px;border-radius:var(--radius)}
  .sidebar-toggle--mobile{display:none;font-size:20px;color:var(--brand)}
  .sidebar-toggle--desktop{width:100%;color:var(--sidebar-ink);opacity:.85;font-size:13px;font-weight:600}
  .sidebar-toggle--desktop:hover{background:var(--sidebar-hover);opacity:1}
  .sidebar-toggle--desktop svg{width:18px;height:18px;flex-shrink:0;transition:transform .15s}

  .app-shell{display:flex;align-items:flex-start}
  .sidebar-backdrop{display:none}
  .sidebar{width:230px;flex-shrink:0;background:var(--sidebar);color:var(--sidebar-ink);min-height:calc(100vh - 61px);position:sticky;top:61px;display:flex;flex-direction:column;justify-content:space-between;transition:width .15s}
  .sidebar__nav{padding:16px 10px;overflow-y:auto}
  .sidebar__section{margin-bottom:18px}
  .sidebar__section-label{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--sidebar-ink);opacity:.6;margin:0 0 6px;padding:0 10px}
  .sidebar__list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:2px}
  .sidebar__link{display:flex;align-items:center;gap:12px;padding:9px 10px;border-radius:var(--radius);color:var(--sidebar-ink);font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden}
  .sidebar__link:hover{background:var(--sidebar-hover)}
  .sidebar__link.active{background:var(--sidebar-active);color:var(--on-brand)}
  .sidebar__link--cta{color:var(--accent)}
  .sidebar__icon{display:flex;flex-shrink:0}
  .sidebar__icon svg{width:19px;height:19px}
  .sidebar__label{overflow:hidden;text-overflow:ellipsis}
  .sidebar__footer{padding:10px;border-top:1px solid color-mix(in srgb, var(--sidebar-ink) 18%, transparent);display:flex;flex-direction:column;gap:2px}

  body.sidebar-collapsed .sidebar{width:60px}
  body.sidebar-collapsed .sidebar__section-label,
  body.sidebar-collapsed .sidebar__label{display:none}
  body.sidebar-collapsed .sidebar__link{justify-content:center}
  body.sidebar-collapsed .sidebar-toggle--desktop{justify-content:center}
  body.sidebar-collapsed .sidebar-toggle--desktop svg{transform:rotate(180deg)}

  .page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
  .page-header__eyebrow{font-size:13px;color:var(--muted);margin:0 0 4px}
  .page-header__title{margin:0;font-size:28px;line-height:1.2;font-family:var(--font-display);letter-spacing:-.02em}
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
  .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:18px;margin-bottom:16px;box-shadow:0 1px 0 color-mix(in srgb, var(--ink) 3%, transparent)}
  .grid{display:grid;gap:16px} .g4{grid-template-columns:repeat(4,1fr)} .g2{grid-template-columns:repeat(2,1fr)}
  @media(max-width:800px){.g4,.g2{grid-template-columns:1fr}}
  .stat .v{font-size:26px;font-weight:800;color:var(--brand);font-family:var(--font-display)} .stat .l{color:var(--muted);font-size:13px}
  .btn{display:inline-block;background:var(--brand);color:var(--on-brand);border:0;border-radius:var(--radius);padding:9px 15px;font-weight:600;cursor:pointer}
  .btn:hover{background:var(--brand-600)}
  .btn.accent{background:var(--accent);color:var(--accent-ink)}
  .btn.ghost{background:transparent;color:var(--brand);border:1px solid var(--line)}
  .btn.ghost:hover{background:var(--brand-soft)}
  table{width:100%;border-collapse:collapse} th,td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);font-size:14px}
  th{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em}
  input,select,textarea{width:100%;padding:9px;border:1px solid var(--line);border-radius:var(--radius);background:var(--surface);color:var(--ink);font:inherit}
  input:focus,select:focus,textarea:focus{border-color:var(--focus);outline:none;box-shadow:0 0 0 3px color-mix(in srgb, var(--focus) 22%, transparent)}
  /* Checkboxes/radios must not inherit width:100% or they blow out flex labels. */
  input[type=checkbox],input[type=radio]{
    width:auto;min-width:1.05rem;height:auto;min-height:1.05rem;padding:0;margin:0;
    flex-shrink:0;accent-color:var(--brand);vertical-align:middle;
  }
  label{display:block;font-size:13px;color:var(--muted);margin:10px 0 4px}
  label.check,
  label:has(> input[type=checkbox]),
  label:has(> input[type=radio]){
    display:flex;align-items:flex-start;gap:10px;margin:0 0 10px;cursor:pointer;
    color:var(--ink);font-weight:500;line-height:1.35;
  }
  label.check input[type=checkbox],
  label.check input[type=radio],
  label:has(> input[type=checkbox]) > input[type=checkbox],
  label:has(> input[type=radio]) > input[type=radio]{margin-top:.15em}
  .status{background:var(--brand-soft);border:1px solid color-mix(in srgb, var(--brand) 28%, var(--line));border-radius:var(--radius);padding:10px 14px;margin-bottom:14px;font-size:14px}
  .err{color:var(--danger);font-size:13px;margin-top:4px}
  .pill{display:inline-block;background:var(--brand-soft);color:var(--brand);border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600}
  .pill--muted{background:var(--surface-2);color:var(--muted)}
  .pill--success{background:var(--success-soft);color:var(--success)}
  .pill--warning{background:var(--warning-soft);color:var(--warning)}
  .pill--danger{background:var(--danger-soft);color:var(--danger)}
  .idle-dialog[hidden]{display:none}
  .idle-dialog:not([hidden]){display:flex;position:fixed;inset:0;z-index:80;align-items:center;justify-content:center;padding:20px;background:color-mix(in srgb, var(--ink) 45%, transparent)}
  .idle-dialog__card{max-width:420px;width:100%;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow)}
  .idle-dialog__card h2{margin:0 0 8px;font-size:20px;font-family:var(--font-display)}
  .idle-dialog__card p{margin:0 0 16px;color:var(--muted);font-size:14px}
  .idle-dialog__actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
  .pill--active{background:var(--accent);color:#fff}
  .emis-filter{background:color-mix(in srgb, var(--accent) 8%, var(--surface));border:1px solid color-mix(in srgb, var(--accent) 28%, var(--line));border-radius:var(--radius);padding:14px 16px;margin-bottom:16px}
  .emis-filter label{display:block;font-size:12px;font-weight:700;color:var(--muted);margin-bottom:4px}
  .emis-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:0 0 16px}
  .emis-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:16px 18px;position:relative;overflow:hidden}
  .emis-card::before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px}
  .emis-card--teal::before{background:var(--accent)}
  .emis-card--pink::before{background:#C45C8A}
  .emis-card--navy::before{background:var(--brand)}
  .emis-card__value{font-size:28px;font-weight:800;font-family:var(--font-display);line-height:1.1}
  .emis-card__label{margin-top:6px;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted)}
  .emis-card__split{margin-top:8px;font-size:13px;color:var(--ink)}
  .fee-tabs{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 16px}
  .fee-tabs__link{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;border:1px solid var(--line);background:var(--surface);font-size:13px;font-weight:700;color:var(--ink)}
  .fee-tabs__link.is-active{background:var(--accent);border-color:var(--accent);color:#fff}
  .fee-tabs__link.is-active .pill{background:rgba(255,255,255,.2);color:#fff}
  .pe-modal{border:0;padding:0;background:transparent;max-width:420px;width:calc(100% - 32px)}
  .pe-modal::backdrop{background:color-mix(in srgb, var(--ink) 45%, transparent)}
  .pe-modal__card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow)}
  .learner-name{display:flex;align-items:center;gap:10px;font-weight:700;color:var(--accent)}
  .learner-avatar{width:32px;height:32px;border-radius:999px;object-fit:cover;background:var(--surface-2);flex-shrink:0}
  .learner-avatar--empty{display:inline-block;background:var(--brand-soft)}
  .nin-missing{color:var(--danger);font-weight:700;font-size:13px}
  @media print {.no-print,.sidebar,.topbar,.fee-tabs,.emis-filter{display:none !important}}
</style>
@yield('head')
</head>
<body class="{{ request()->cookie('sidebar_collapsed') === '1' ? 'sidebar-collapsed' : '' }}">
  <a class="skip-link" href="#main-content">Skip to content</a>
  @auth
    @php
      $nav = $nav ?? app(\App\Services\Navigation\NavigationBuilder::class)->build(auth()->user());
      $crumbSection = null;
      $crumbItem = null;
      foreach ($nav['sections'] ?? [] as $section) {
          foreach ($section['items'] ?? [] as $item) {
              if (! empty($item['active'])) {
                  $crumbSection = $section['label'] ?? null;
                  $crumbItem = $item['label'] ?? null;
              }
          }
      }
    @endphp
    @include('layouts.partials.topbar', ['nav' => $nav])
    <div class="app-shell">
      @include('layouts.partials.sidebar', ['nav' => $nav])
      <main id="main-content" class="wrap" tabindex="-1">
        @if(($nav['zone'] ?? '') === 'school' && $crumbItem && ! request()->routeIs('app.home'))
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
              <li><a href="{{ route('app.home') }}">Home</a></li>
              @if($crumbSection && $crumbSection !== 'Home')
                <li>{{ $crumbSection }}</li>
              @endif
              <li aria-current="page">{{ $crumbItem }}</li>
            </ol>
          </nav>
        @endif
        @if(session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
        @yield('content')
      </main>
    </div>
    <div id="idle-session-dialog" class="idle-dialog" hidden role="alertdialog" aria-labelledby="idle-session-title" aria-describedby="idle-session-desc">
      <div class="idle-dialog__card">
        <h2 id="idle-session-title">Still there?</h2>
        <p id="idle-session-desc">You will be signed out in <strong id="idle-session-countdown">120</strong> seconds because this session has been idle. This protects learner and fee records on a shared computer.</p>
        <div class="idle-dialog__actions">
          <button type="button" class="btn ghost" id="idle-session-leave">Sign out now</button>
          <button type="button" class="btn" id="idle-session-stay">Stay signed in</button>
        </div>
      </div>
    </div>
    <form id="idle-session-logout" method="post" action="{{ route('logout') }}" hidden>
      @csrf
    </form>
    <script src="{{ asset('js/idle-session.js') }}" defer></script>
    @include('layouts.partials.offline-body')
  @else
    <div class="topbar">
      @include('layouts.partials.brand', ['brandHref' => url('/login')])
    </div>
    <main id="main-content" class="wrap" tabindex="-1">
      @if(session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
      @yield('content')
    </main>
    @include('layouts.partials.offline-body')
  @endauth
</body>
</html>
