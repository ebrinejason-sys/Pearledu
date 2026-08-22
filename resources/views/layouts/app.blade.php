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
  .brand--stacked{align-items:center;gap:10px;white-space:normal}
  .vx-logo{display:block;flex-shrink:0;height:var(--vx-logo-h,28px);width:auto}
  .brand__copy{display:flex;flex-direction:column;line-height:1.15;min-width:0}
  .brand__wordmark{font-weight:800;font-size:18px;color:var(--brand)}
  .brand__wordmark b{color:var(--accent)}
  .brand__tagline{font-size:10px;font-weight:500;letter-spacing:.01em;color:currentColor;opacity:.88;max-width:148px;line-height:1.3}
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

  .app-shell{display:flex;align-items:stretch;min-height:100vh}
  .app-col{flex:1;min-width:0;display:flex;flex-direction:column}
  .sidebar-backdrop{display:none}
  .sidebar{width:248px;flex-shrink:0;background:var(--sidebar);color:var(--sidebar-ink);min-height:100vh;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;transition:width .15s}
  .sidebar__head{background:var(--accent);color:#fff;min-height:72px;padding:12px 14px;display:flex;align-items:center;flex-shrink:0}
  .sidebar__head .brand{color:#fff}
  .sidebar__head .brand__wordmark{color:#fff}
  .sidebar__head .brand__wordmark b{color:#fff}
  .sidebar__head .brand__tagline{color:#fff;opacity:.9}
  .sidebar__nav{padding:16px 10px;overflow-y:auto;flex:1}
  .sidebar__section{margin-bottom:18px}
  .sidebar__section-label{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--sidebar-ink);opacity:.6;margin:0 0 6px;padding:0 10px}
  .sidebar__list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:2px}
  .sidebar__link{display:flex;align-items:center;gap:12px;padding:9px 10px;border-radius:var(--radius);color:var(--sidebar-ink);font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden}
  .sidebar__link:hover{background:var(--sidebar-hover)}
  .sidebar__link.active{background:var(--sidebar-active);color:var(--on-brand)}
  .sidebar__link--open{background:var(--sidebar-hover)}
  .sidebar__link--cta{color:var(--accent)}
  .sidebar__icon{display:flex;flex-shrink:0}
  .sidebar__icon svg{width:19px;height:19px}
  .sidebar__label{overflow:hidden;text-overflow:ellipsis}
  .sidebar__chevron{margin-left:auto;display:flex;opacity:.7;flex-shrink:0}
  .sidebar__chevron svg{width:14px;height:14px;transition:transform .15s}
  .sidebar__group > summary{list-style:none;cursor:pointer}
  .sidebar__group > summary::-webkit-details-marker{display:none}
  .sidebar__group[open] > summary .sidebar__chevron svg{transform:rotate(90deg)}
  .sidebar__sub{list-style:none;margin:0;padding:2px 0 6px 0;display:flex;flex-direction:column;gap:1px}
  .sidebar__link--sub{font-size:13px;font-weight:500;padding-left:41px}
  .sidebar__footer{padding:10px;border-top:1px solid color-mix(in srgb, var(--sidebar-ink) 18%, transparent);display:flex;flex-direction:column;gap:2px}
  .year-chip{font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#fff;background:var(--accent);border-radius:999px;padding:8px 14px;white-space:nowrap}
  .topbar__brand{display:none}

  body.sidebar-collapsed .sidebar{width:60px}
  body.sidebar-collapsed .sidebar__section-label,
  body.sidebar-collapsed .sidebar__label,
  body.sidebar-collapsed .sidebar__chevron,
  body.sidebar-collapsed .sidebar__sub,
  body.sidebar-collapsed .sidebar__head .brand__copy{display:none}
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
    .topbar__brand{display:inline-flex}
    .sidebar{position:fixed;top:0;left:0;height:100vh;z-index:60;transform:translateX(-100%);width:248px}
    body.sidebar-open .sidebar{transform:translateX(0)}
    body.sidebar-open .sidebar-backdrop{display:block;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:50}
    body.sidebar-collapsed .sidebar{width:248px}
    body.sidebar-collapsed .sidebar__section-label,
    body.sidebar-collapsed .sidebar__label,
    body.sidebar-collapsed .sidebar__chevron,
    body.sidebar-collapsed .sidebar__sub,
    body.sidebar-collapsed .sidebar__head .brand__copy{display:flex}
    body.sidebar-collapsed .sidebar__sub{display:flex}
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
  .pe-modal--wide{max-width:720px}
  .pe-modal--form{max-width:560px}
  .pe-modal::backdrop{background:color-mix(in srgb, var(--ink) 45%, transparent)}
  .pe-modal__card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow);max-height:min(90vh, 880px);overflow:auto}
  .staff-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
  .staff-grid--invites{margin-top:4px}
  .staff-band{margin:8px 0 28px}
  .staff-band__title{font-size:18px;margin:0 0 12px;display:flex;align-items:center;gap:10px}
  .staff-band__count{font-size:12px;font-weight:700;background:var(--accent-soft);color:var(--brand);border-radius:999px;padding:2px 10px}
  .staff-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:0;overflow:hidden;display:flex;flex-direction:column;--staff-tone:var(--accent)}
  .staff-card--school_admin,.staff-card--director{--staff-tone:#053F5C}
  .staff-card--head_teacher,.staff-card--deputy_head_teacher{--staff-tone:#10897C}
  .staff-card--director_of_studies{--staff-tone:#1B6B93}
  .staff-card--bursar{--staff-tone:#C47B17}
  .staff-card--secretary{--staff-tone:#5B6B7A}
  .staff-card--class_teacher,.staff-card--subject_teacher{--staff-tone:#2F6FED}
  .staff-card__hero{background:linear-gradient(135deg,var(--staff-tone) 0%,color-mix(in srgb,var(--staff-tone) 55%,var(--brand)) 100%);color:#fff;padding:18px 16px 20px;display:flex;gap:14px;align-items:flex-end;min-height:108px}
  .staff-card__hero--compact{min-height:88px;padding:14px}
  .staff-card__photo-lg{width:72px;height:72px;border-radius:18px;object-fit:cover;flex-shrink:0;border:3px solid rgba(255,255,255,.88);box-shadow:0 8px 20px rgba(0,0,0,.18);background:color-mix(in srgb,#fff 18%,var(--staff-tone))}
  .staff-card__photo-lg--initial{display:flex;align-items:center;justify-content:center;font-weight:800;font-size:22px;color:#fff}
  .staff-card__identity{min-width:0;flex:1}
  .staff-card__name{display:block;font-weight:800;font-size:16px;color:#fff}
  .staff-card__meta{display:block;font-size:12px;color:var(--muted)}
  .staff-card__meta--on-hero{color:rgba(255,255,255,.88);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .staff-card__status{display:inline-block;margin-top:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:rgba(255,255,255,.18);border-radius:999px;padding:2px 8px}
  .staff-card__status--invited{background:#F5C518;color:#1a1a1a}
  .staff-card__body{padding:14px 16px 16px}
  .staff-card__roles,.teach-chips{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 10px}
  .staff-card__stats{display:flex;gap:14px;font-size:13px;color:var(--muted);margin:0 0 10px}
  .staff-card__stats strong{color:var(--ink)}
  .staff-card__head{display:flex;align-items:center;gap:12px;margin-bottom:12px}
  .staff-card__avatar{width:42px;height:42px;border-radius:50%;background:var(--brand);color:var(--on-brand);display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0}
  .staff-card__avatar img,.staff-card__photo{width:42px;height:42px;border-radius:50%;object-fit:cover;display:block}
  .fee-attach{margin:0 0 18px;padding:14px;border:1px solid var(--line);border-radius:var(--radius);background:color-mix(in srgb,var(--accent-soft) 55%,var(--surface))}
  .fee-attach--readonly{background:var(--surface)}
  .fee-attach__list{margin:0;padding-left:18px}
  .fee-attach__list li{margin:0 0 6px}
  .learner-name{display:flex;align-items:center;gap:10px;font-weight:700;color:var(--accent)}
  .learner-avatar{width:32px;height:32px;border-radius:999px;object-fit:cover;background:var(--surface-2);flex-shrink:0}
  .learner-avatar--empty{display:inline-block;background:var(--brand-soft)}
  .lp{display:grid;grid-template-columns:240px minmax(0,1fr);gap:20px;align-items:start}
  .lp-rail{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:20px}
  .lp-rail__who{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding-bottom:16px;border-bottom:1px solid var(--line);margin-bottom:12px}
  .lp-rail__photo{width:96px;height:96px;border-radius:12px;object-fit:cover;background:var(--surface-2)}
  .lp-rail__name{font-weight:800;font-size:15px;line-height:1.3}
  .lp-rail__meta{font-size:13px;color:var(--muted)}
  .lp-nav{list-style:none;margin:0;padding:0;display:flex;flex-direction:column}
  .lp-nav a{display:block;padding:10px 8px;border-radius:var(--radius-sm);color:var(--ink);font-weight:600;font-size:14px}
  .lp-nav a.is-active{color:var(--brand);background:var(--brand-soft)}
  .lp-panel{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:20px}
  .lp-tabs{display:flex;gap:18px;border-bottom:1px solid var(--line);margin:0 0 16px}
  .lp-tabs span,.lp-tabs a{padding:8px 0;font-size:13px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--muted);border-bottom:2px solid transparent}
  .lp-tabs .is-active{color:var(--brand);border-bottom-color:var(--brand)}
  .lp-dl{display:grid;grid-template-columns:minmax(140px,220px) minmax(0,1fr);gap:0}
  .lp-dl dt{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding:12px 12px 12px 0;border-bottom:1px solid var(--line);font-weight:700}
  .lp-dl dd{margin:0;padding:12px 0;border-bottom:1px solid var(--line);font-weight:600}
  @media(max-width:800px){
    .lp{grid-template-columns:1fr}
    .lp-dl{grid-template-columns:1fr}
    .lp-dl dt{padding-bottom:2px;border-bottom:0}
    .lp-dl dd{padding-top:2px}
  }
  .nin-missing{color:var(--danger);font-weight:700;font-size:13px}
  .role-picks{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin:8px 0 12px}
  .role-pick{display:flex;flex-direction:column;gap:4px;margin:0;padding:12px;border:1px solid var(--line);border-radius:var(--radius);background:var(--surface);cursor:pointer;color:var(--ink)}
  .role-pick:has(input:checked){border-color:var(--accent);background:color-mix(in srgb, var(--accent) 10%, var(--surface));box-shadow:0 0 0 2px color-mix(in srgb, var(--accent) 22%, transparent)}
  .role-pick strong{font-size:14px}
  .role-pick span{font-size:12px;color:var(--muted);font-weight:500;line-height:1.35}
  .role-pick input{margin-top:0}
  .teach-builder{margin:8px 0 0}
  .teach-builder__hint,.teach-row__hint{margin:0 0 10px;font-size:13px;color:var(--muted)}
  .teach-builder__actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:8px}
  .teach-builder__summary{margin:0;font-size:13px;font-weight:600;color:var(--brand)}
  .teach-row{border:1px solid var(--line);border-radius:var(--radius);padding:14px;margin-bottom:10px;background:color-mix(in srgb, var(--accent) 6%, var(--surface))}
  .teach-row__head{display:flex;align-items:center;gap:8px;margin-bottom:8px}
  .teach-row__badge{width:26px;height:26px;border-radius:999px;background:var(--brand);color:var(--on-brand);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800}
  .teach-row__remove{margin-left:auto;background:transparent;border:0;color:var(--danger);font:inherit;font-size:13px;font-weight:700;cursor:pointer}
  .teach-row__classes{border:0;padding:0;margin:10px 0 0}
  .teach-row__classes legend{font-size:13px;color:var(--muted);padding:0}
  .teach-row__periods{max-width:160px;margin-top:8px}
  .teach-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
  .teach-chip{display:inline-flex;align-items:center;gap:8px;margin:0;padding:7px 12px;border:1px solid var(--line);border-radius:999px;background:var(--surface);font-size:13px;font-weight:600;color:var(--ink)}
  .teach-chip:has(input:checked){border-color:var(--accent);background:var(--accent);color:#fff}
  .teach-chip-mini{display:inline-flex;flex-direction:column;gap:2px;padding:6px 8px;border-radius:10px;background:var(--brand-soft);color:var(--brand);font-size:12px;font-weight:700;line-height:1.2}
  .teach-matrix-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--line);border-radius:var(--radius)}
  .teach-matrix{width:max-content;min-width:100%;border-collapse:separate;border-spacing:0}
  .teach-matrix th,.teach-matrix td{border-bottom:1px solid var(--line);border-right:1px solid var(--line);padding:8px;vertical-align:top;min-width:110px}
  .teach-matrix th:first-child,.teach-matrix td:first-child{position:sticky;left:0;background:var(--surface);z-index:1;min-width:140px;font-weight:700}
  .teach-matrix thead th{background:var(--surface-2,#f4f6f8);font-size:12px;text-transform:none;letter-spacing:0}
  .teach-matrix td.has-load{background:color-mix(in srgb, var(--accent) 12%, var(--surface))}
  .teach-matrix td.is-collision{background:var(--warning-soft);box-shadow:inset 0 0 0 2px var(--warning)}
  .teach-matrix td.is-empty{background:repeating-linear-gradient(-45deg,transparent,transparent 6px,color-mix(in srgb,var(--line) 35%,transparent) 6px,color-mix(in srgb,var(--line) 35%,transparent) 7px)}
  .load-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
  .load-card{border:1px solid var(--line);border-radius:var(--radius);padding:14px;background:var(--surface)}
  .load-card__bar{height:8px;border-radius:999px;background:var(--surface-2,#eef2f4);overflow:hidden;margin:8px 0 10px}
  .load-card__bar span{display:block;height:100%;background:linear-gradient(90deg,var(--brand),var(--accent))}
  .dash-bar__track--split{display:flex}
  .dash-bar__track--split .m{background:var(--brand)}
  .dash-bar__track--split .f{background:var(--accent)}
  .dash-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .dash-stat{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:14px 16px;position:relative;overflow:hidden}
  .dash-stat::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--brand)}
  .dash-stat--accent::before{background:var(--accent)}
  .dash-stat--warning::before{background:var(--warning)}
  .dash-stat__value{font-size:22px;font-weight:800;line-height:1.15;font-family:var(--font-display);color:var(--ink);letter-spacing:-.02em}
  .dash-stat__label{margin-top:6px;font-size:13px;font-weight:700;color:var(--brand)}
  .dash-stat__hint{margin-top:2px;font-size:12px;color:var(--muted)}
  .dash-chart-card__head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
  .dash-bars{display:flex;flex-direction:column;gap:10px;margin-top:16px}
  .dash-bar__meta{display:flex;justify-content:space-between;gap:10px;font-size:13px;margin-bottom:4px}
  .dash-bar__meta strong{font-variant-numeric:tabular-nums}
  .dash-bar__track{height:10px;border-radius:999px;background:var(--surface-2);overflow:hidden}
  .dash-bar__track span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,var(--brand),color-mix(in srgb,var(--brand) 65%, var(--accent)))}
  .dash-cols{display:flex;align-items:flex-end;gap:10px;height:160px;margin-top:18px;padding:0 4px}
  .dash-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:8px;min-width:0}
  .dash-col__bar{width:100%;max-width:36px;border-radius:8px 8px 4px 4px;background:linear-gradient(180deg,var(--accent),color-mix(in srgb,var(--brand) 70%, var(--accent)));box-shadow:0 6px 14px color-mix(in srgb, var(--brand) 18%, transparent)}
  .dash-col__label{font-size:11px;color:var(--muted);font-weight:600}
  .dash-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
  .dash-tile{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--line);border-radius:var(--radius);background:color-mix(in srgb, var(--surface) 88%, var(--brand-soft));color:inherit;transition:border-color .15s, transform .12s, background .15s}
  .dash-tile:hover{border-color:color-mix(in srgb, var(--brand) 35%, var(--line));background:var(--brand-soft);transform:translateY(-1px)}
  .dash-tile__glyph{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--brand);color:var(--on-brand);font-weight:800;font-size:14px;flex-shrink:0}
  .dash-tile__body{display:flex;flex-direction:column;min-width:0;line-height:1.25}
  .dash-tile__body strong{font-size:14px;color:var(--ink)}
  .dash-tile__body span{font-size:12px;color:var(--muted);margin-top:2px}
  .dash-access summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .dash-access summary::-webkit-details-marker{display:none}
  .dash-access summary span{font-size:13px;color:var(--muted)}
  .dash-access__list{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
  .face{display:inline-flex;align-items:center;justify-content:center;border-radius:50%;background:var(--brand);color:var(--on-brand);font-weight:800;overflow:hidden;flex-shrink:0}
  .face--md{width:40px;height:40px;font-size:16px}
  .face--lg{width:56px;height:56px;font-size:22px}
  .face img{width:100%;height:100%;object-fit:cover;display:block}
  .ws-greet{display:flex;align-items:center;gap:14px}
  .ws-crest{width:56px;height:56px;border-radius:var(--radius-sm);object-fit:cover;border:1px solid var(--line);background:var(--surface)}
  .ws-mantra{margin:6px 0 0;color:var(--muted);font-size:14px}
  .ws-block{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:18px;margin-bottom:16px}
  .ws-block--strip{padding:12px 16px}
  .ws-block__title{margin:0;font-size:20px}
  .ws-sub{font-size:15px;margin:18px 0 8px}
  .ws-hint{margin:6px 0 0;color:var(--muted);font-size:13px}
  .ws-strip-meta{margin:8px 0 0;color:var(--muted);font-size:14px}
  .ws-cta{min-height:44px;min-width:44px;display:inline-flex;align-items:center;justify-content:center}
  .ws-cta--xl{font-size:16px;padding:12px 20px}
  .ws-hero{display:flex;gap:20px;flex-wrap:wrap;align-items:center;margin:12px 0}
  .ws-ring{width:132px;height:132px;border-radius:50%;display:grid;place-items:center;flex-shrink:0}
  .ws-ring__hole{width:88px;height:88px;border-radius:50%;background:var(--surface);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
  .ws-ring__hole strong{font-size:22px;font-family:var(--font-display)}
  .ws-ring__hole span{font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em}
  .ws-legend{display:flex;flex-wrap:wrap;gap:10px;font-size:13px;margin:0 0 12px}
  .ws-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px;vertical-align:middle}
  .ws-dot--success{background:var(--success)}
  .ws-dot--warning{background:var(--warning)}
  .ws-dot--danger{background:var(--danger)}
  .ws-dot--muted{background:var(--muted)}
  .face-row{display:flex;flex-wrap:wrap;gap:10px}
  .face-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 10px 6px 6px;border:1px solid var(--line);border-radius:999px;background:var(--surface);color:inherit;font-size:13px}
  .attn-queue{list-style:none;margin:0;padding:0}
  .attn-queue__item{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;padding:12px 0;border-top:1px solid var(--line)}
  .attn-queue__item p{margin:4px 0 0;color:var(--muted);font-size:13px}
  .attn-queue__item--danger{border-left:4px solid var(--danger);padding-left:10px}
  .attn-queue__item--warning{border-left:4px solid var(--warning);padding-left:10px}
  .attn-queue__empty{padding:10px 0;color:var(--muted)}
  .lesson-tl{list-style:none;margin:0;padding:0;border-left:3px solid var(--line)}
  .lesson-tl__item{display:flex;gap:12px;padding:8px 0 8px 14px;position:relative}
  .lesson-tl__item::before{content:"";position:absolute;left:-7px;top:14px;width:10px;height:10px;border-radius:50%;background:var(--line)}
  .lesson-tl__item.is-now{background:var(--accent-soft);border-radius:var(--radius-sm)}
  .lesson-tl__item.is-now::before{background:var(--accent)}
  .lesson-tl__time{font-weight:700;min-width:72px;font-variant-numeric:tabular-nums}
  .ws-actions,.ws-flag{display:flex;flex-wrap:wrap;gap:8px;align-items:end;margin-top:10px}
  .ws-flag label{flex:1;min-width:160px;margin:0}
  .funnel{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin:12px 0}
  .funnel__step strong{display:block;font-size:22px;font-family:var(--font-display);margin:6px 0}
  .ops-cols{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
  .ops-col{border:1px solid var(--line);border-radius:var(--radius);padding:14px;background:var(--surface-2)}
  .ops-col h3{margin:0 0 8px;font-size:15px}
  .heat{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px}
  .heat__cell{display:flex;flex-direction:column;gap:4px;padding:10px;border-radius:var(--radius-sm);border:1px solid var(--line);color:inherit;min-height:64px}
  .heat__cell--success{background:var(--success-soft);border-color:var(--success)}
  .heat__cell--warning{background:var(--warning-soft);border-color:var(--warning)}
  .heat__cell--danger{background:var(--danger-soft);border-color:var(--danger)}
  .heat__cell--muted{background:var(--surface-2)}
  .hygiene-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-top:12px}
  .hygiene-tile{display:flex;flex-direction:column;gap:4px;padding:14px;border:1px solid var(--line);border-radius:var(--radius);color:inherit;min-height:88px}
  .hygiene-tile strong{font-size:26px;font-family:var(--font-display)}
  .hygiene-tile--warning{background:var(--warning-soft)}
  .hygiene-tile--danger{background:var(--danger-soft)}
  .hygiene-tile--success{background:var(--success-soft)}
  .ws-checks{list-style:none;margin:8px 0 0;padding:0}
  .ws-checks li{display:flex;gap:8px;align-items:center;margin:6px 0}
  .exam-set{margin-bottom:14px}
  .exam-set__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
  .exam-set__card{display:flex;gap:10px;align-items:flex-start;border:1px solid var(--line);border-radius:var(--radius);padding:10px}
  .portal-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:16px}
  .portal-tile{display:flex;flex-direction:column;gap:6px;padding:16px;border:1px solid var(--line);border-radius:var(--radius);background:var(--surface);color:inherit;min-height:88px}
  .portal-tile strong{font-size:16px}
  .portal-tile span{font-size:13px;color:var(--muted)}
  .att-dot{display:inline-block;margin-left:6px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase}
  .att-dot--present{background:var(--success-soft);color:var(--success)}
  .att-dot--late{background:var(--warning-soft);color:var(--warning)}
  .att-dot--absent{background:var(--danger-soft);color:var(--danger)}
  .att-btns{display:flex;flex-wrap:wrap;gap:6px}
  .att-btn{position:relative;display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 12px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--surface);cursor:pointer;font-size:13px;font-weight:700}
  .att-btn input{position:absolute;opacity:0;pointer-events:none}
  .att-btn:has(input:checked){border-color:var(--brand);background:var(--brand-soft)}
  .att-btn--present:has(input:checked){border-color:var(--success);background:var(--success-soft);color:var(--success)}
  .att-btn--absent:has(input:checked){border-color:var(--danger);background:var(--danger-soft);color:var(--danger)}
  .att-btn--late:has(input:checked){border-color:var(--warning);background:var(--warning-soft);color:var(--warning)}
  .reg-person{display:flex;align-items:center;gap:10px}
  .reg-sticky{position:sticky;left:0;background:var(--surface);z-index:1}
  .ws-pulse{display:grid;gap:10px}
  .ws-kpis-thin{margin-bottom:12px}
  .child-card .face{margin-bottom:8px}
  @media(max-width:800px){
    .dash-stats{grid-template-columns:repeat(2,1fr)}
    .dash-cols{height:140px}
    .ops-cols{grid-template-columns:1fr}
    .reg-table thead{display:none}
    .reg-table,.reg-table tbody,.reg-table tr,.reg-table td{display:block;width:100%}
    .reg-table tr{border:1px solid var(--line);border-radius:var(--radius);padding:12px;margin-bottom:10px}
    .reg-table td{border:0;padding:6px 0}
    .reg-sticky{position:static}
    .att-btn{flex:1;min-width:44px}
  }
  @media(prefers-reduced-motion:reduce){
    .dash-tile,.ws-ring,.btn{transition:none !important;transform:none !important}
  }
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
      $crumbGroup = null;
      $crumbItem = null;
      foreach ($nav['sections'] ?? [] as $section) {
          foreach ($section['items'] ?? [] as $item) {
              $matchedChild = null;
              foreach ($item['children'] ?? [] as $child) {
                  if (! empty($child['active'])) {
                      $matchedChild = $child;
                      break;
                  }
              }
              if ($matchedChild) {
                  $crumbSection = $section['label'] ?? null;
                  $crumbGroup = $item['label'] ?? null;
                  $crumbItem = $matchedChild['label'] ?? null;
              } elseif (! empty($item['active']) && empty($item['children'])) {
                  $crumbSection = $section['label'] ?? null;
                  $crumbGroup = null;
                  $crumbItem = $item['label'] ?? null;
              }
          }
      }
    @endphp
    @if(!empty($nav['impersonation']))
      <div class="impersonation-banner" role="status">
        <div class="impersonation-banner__text">
          <strong>Imitation mode{{ !empty($nav['impersonation']['read_only']) ? ' (read-only)' : ' (elevated write)' }}</strong>
          — viewing as {{ $nav['impersonation']['target_name'] }}
          @if($nav['impersonation']['school_name'])
            at {{ $nav['impersonation']['school_name'] }}
          @endif
          <span class="impersonation-banner__meta">
            Operator: {{ $nav['impersonation']['operator_name'] }}
            @if(!empty($nav['impersonation']['reason']))
              · Reason: {{ $nav['impersonation']['reason'] }}
            @endif
          </span>
        </div>
        <form method="post" action="{{ route('impersonation.stop') }}">
          @csrf
          <button type="submit" class="impersonation-banner__btn">End imitation</button>
        </form>
      </div>
    @endif
    <div class="app-shell">
      @include('layouts.partials.sidebar', ['nav' => $nav])
      <div class="app-col">
      @include('layouts.partials.topbar', ['nav' => $nav])
      <main id="main-content" class="wrap" tabindex="-1">
        @if(($nav['zone'] ?? '') === 'school' && $crumbItem && ! request()->routeIs('app.home'))
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
              <li><a href="{{ route('app.home') }}">Home</a></li>
              @if($crumbSection && $crumbSection !== 'Home')
                <li>{{ $crumbSection }}</li>
              @endif
              @if($crumbGroup)
                <li>{{ $crumbGroup }}</li>
              @endif
              <li aria-current="page">{{ $crumbItem }}</li>
            </ol>
          </nav>
        @endif
        @if(session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
        @yield('content')
      </main>
      </div>
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
    <script>
      document.addEventListener('click', function (e) {
        var openBtn = e.target.closest('[data-open-modal]');
        if (openBtn) {
          var dlg = document.getElementById(openBtn.getAttribute('data-open-modal'));
          if (dlg && dlg.showModal) dlg.showModal();
        }
        var closeBtn = e.target.closest('[data-close-modal]');
        if (closeBtn) {
          var dlg = closeBtn.closest('dialog');
          if (dlg) dlg.close();
        }
      });
    </script>
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
