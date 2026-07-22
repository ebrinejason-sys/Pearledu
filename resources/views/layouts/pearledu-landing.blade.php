<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'PearlEdu — School management, without the spreadsheets.')</title>
@include('layouts.partials.favicons')
<script>
(function () {
  try {
    var key = 'voxsign-color-scheme';
    var stored = localStorage.getItem(key);
    var theme = (stored === 'light' || stored === 'dark')
      ? stored
      : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
  } catch (e) {}
})();
</script>
<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#053F5C; --ink-2:#034A6B; --paper:#F5FBFD; --surface:#FFFFFF;
    --fg:#053F5C; --logo:#053F5C;
    --voice:#F27F0C; --gold:#F7AD19; --sign:#429EBD; --cyan:#9FE7F5; --muted:#4A6270; --line:#D4E8EE;
    --ink-line:rgba(255,255,255,.12); --ink-muted:#9FE7F5;
    --nav-glass:rgba(255,255,255,.82);
    --input-bg:#fff; --status-bg:#E8F7FA;
    --mock-bar:#F6F4EE; --mock-row:#F8F6F1; --mock-bubble:#F1EEE6;
    --display:'Google Sans',system-ui,sans-serif;
    --body:'Satoshi',system-ui,sans-serif;
  }
  html[data-theme="dark"]{
    color-scheme:dark;
    --paper:#0B1220; --surface:#141B2A; --fg:#E8EEF5; --logo:#9FE7F5;
    --muted:#9AA8B8; --line:#2A3447;
    --nav-glass:rgba(20,27,42,.92);
    --input-bg:#141B2A; --status-bg:rgba(66,158,189,.16);
    --mock-bar:#1A2233; --mock-row:#1A2233; --mock-bubble:#1A2233;
  }
  *{box-sizing:border-box} html,body{margin:0}
  html{scroll-behavior:smooth}
  @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  body{font-family:var(--body);background:var(--paper);color:var(--fg);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3,h4{font-family:var(--display);line-height:1.1;letter-spacing:-.02em;margin:0}
  a{color:inherit;text-decoration:none}
  .pe-wrap{max-width:1120px;margin:0 auto;padding:0 24px}
  .pe-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  .pe-nav-shell{position:sticky;top:12px;z-index:50;padding:0 16px}
  .pe-nav{position:relative;display:flex;align-items:center;gap:12px;max-width:1120px;margin:0 auto;padding:10px 14px 10px 12px;
          background:var(--nav-glass);backdrop-filter:blur(14px);border:1px solid var(--line);
          border-radius:999px;box-shadow:0 12px 34px -22px rgba(11,16,32,.45)}
  .pe-brand{display:flex;align-items:center;gap:10px;padding-left:4px;color:var(--logo);min-width:0}
  .vx-logo{display:block;flex-shrink:0;height:var(--vx-logo-h,26px);width:auto}
  .pe-brand-text{display:flex;flex-direction:column;line-height:1.12;min-width:0}
  @media(max-width:760px){
    .pe-brand .vx-logo{--vx-logo-h:20px;height:20px}
    .pe-brand-tagline{display:none}
  }
  .pe-brand-name{font-family:var(--display);font-weight:700;font-size:16px;color:var(--fg)}
  .pe-brand-tagline{font-size:10.5px;color:var(--muted)}
  .pe-nav-links{margin-left:8px;display:flex;gap:20px;font-size:14.5px;color:var(--muted)}
  .pe-nav-links a:hover{color:var(--fg)}
  .pe-nav-cta{margin-left:auto;display:flex;gap:8px;align-items:center}
  .pe-theme-btn{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:999px;
                border:1.5px solid var(--line);background:transparent;color:var(--fg);cursor:pointer;flex-shrink:0}
  .pe-theme-btn:hover{border-color:var(--fg)}
  .pe-theme-btn .pe-theme-icon-dark{display:none}
  html[data-theme="dark"] .pe-theme-btn .pe-theme-icon-light{display:none}
  html[data-theme="dark"] .pe-theme-btn .pe-theme-icon-dark{display:block}
  .pe-nav-toggle{display:none;background:none;border:1.5px solid var(--line);border-radius:999px;padding:8px 12px;font-size:18px;cursor:pointer;color:var(--fg)}
  .pe-nav-mobile-cta{display:none}
  @media(max-width:760px){
    .pe-nav-links{display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;flex-direction:column;
                  background:var(--surface);border:1px solid var(--line);border-radius:20px;
                  box-shadow:0 18px 40px -20px rgba(11,16,32,.4);padding:16px 20px;gap:14px;margin:0}
    .pe-nav-links.open{display:flex}
    .pe-nav-cta .pe-btn{display:none}
    .pe-nav-toggle{display:block}
    .pe-nav-mobile-cta{display:flex;margin-top:4px}
    .pe-nav-mobile-cta .pe-btn{width:100%;justify-content:center;min-height:48px}
  }

  .pe-btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--ink);color:#fff;border:1.5px solid var(--ink);border-radius:999px;padding:12px 22px;cursor:pointer;
          transition:transform .15s ease,box-shadow .2s ease}
  .pe-btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px rgba(11,16,32,.5)}
  .pe-btn-grad{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--voice);color:#fff;border:0;border-radius:999px;padding:13px 24px;cursor:pointer;
          box-shadow:0 10px 26px -12px rgba(242,127,12,.45);transition:transform .15s ease,box-shadow .2s ease}
  .pe-btn-grad:hover{transform:translateY(-2px);box-shadow:0 16px 34px -12px rgba(66,158,189,.45)}
  .pe-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
                background:transparent;color:var(--fg);border:1.5px solid var(--line);border-radius:999px;padding:12px 22px;cursor:pointer}
  .pe-btn-ghost:hover{border-color:var(--fg)}
  .pe-band .pe-btn-ghost{color:#fff;border-color:var(--ink-line)}
  .pe-band .pe-btn-ghost:hover{border-color:#fff}
  @media(prefers-reduced-motion:reduce){.pe-btn,.pe-btn-grad{transition:none}.pe-btn:hover,.pe-btn-grad:hover{transform:none}}

  .pe-cta-row{display:flex;gap:12px;flex-wrap:wrap;justify-content:center}
  @media(max-width:640px){
    .pe-cta-row{flex-direction:column;align-items:stretch;width:100%;max-width:360px;margin-left:auto;margin-right:auto}
    .pe-cta-row > a,.pe-cta-row > .pe-btn,.pe-cta-row > .pe-btn-grad,.pe-cta-row > .pe-btn-ghost{
      width:100%;justify-content:center;text-align:center;min-height:48px;box-sizing:border-box}
  }

  .pe-section{padding:clamp(52px,8vw,96px) 0}
  .pe-band{background:var(--ink);color:#fff}
  .pe-band .pe-lead{color:var(--ink-muted)}
  .pe-tint{background:var(--paper)}

  .pe-eyebrow{display:inline-block;font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--voice);font-weight:700;margin-bottom:14px;text-transform:uppercase;background:rgba(242,127,12,.1);border:1px solid rgba(242,127,12,.25);border-radius:999px;padding:5px 14px}
  .pe-band .pe-eyebrow{color:var(--cyan);background:rgba(159,231,245,.14);border-color:rgba(159,231,245,.35)}
  .pe-h1{font-size:clamp(34px,5.6vw,60px);font-weight:800;line-height:1.04;max-width:760px;margin:0 0 18px}
  .pe-h1 .pe-flow{color:var(--cyan)}
  .pe-h2{font-size:clamp(24px,3.6vw,38px);font-weight:700;margin:0 0 6px}
  .pe-lead{color:var(--muted);max-width:640px;font-size:17px}
  .pe-sec-head{margin-bottom:clamp(28px,4vw,44px)}

  .pe-hero{position:relative;overflow:hidden;margin-top:-68px;padding-top:calc(clamp(52px,8vw,96px) + 92px)}
  .pe-hero .pe-wrap{position:relative;z-index:1}

  .pe-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .pe-card{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:22px;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease}
  .pe-card::before{content:"";position:absolute;top:0;left:0;right:0;height:2px;background:var(--sign);transform:scaleX(0);transform-origin:left;transition:transform .25s ease}
  .pe-card:hover::before{transform:scaleX(1)}
  .pe-card:hover{transform:translateY(-3px);box-shadow:0 12px 24px -16px rgba(11,16,32,.25)}
  @media(prefers-reduced-motion:reduce){.pe-card{transition:none}.pe-card:hover{transform:none}.pe-card::before{transition:none}}
  .pe-card-icon{display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;
    background:rgba(66,158,189,.12);color:var(--sign);margin-bottom:14px}
  .pe-card-icon svg{width:22px;height:22px}
  .pe-card h3{margin:0 0 6px;font-size:17px;font-weight:700}
  .pe-card p{margin:0;color:var(--muted);font-size:14px}

  .pe-mock{background:var(--surface);color:var(--fg);border:1px solid var(--line);border-radius:18px;overflow:hidden;
           box-shadow:0 30px 60px -30px rgba(11,16,32,.35)}
  .pe-mock-bar{display:flex;align-items:center;gap:6px;padding:10px 14px;border-bottom:1px solid var(--line);background:var(--mock-bar)}
  .pe-mock-bar i{width:10px;height:10px;border-radius:50%;background:#D9D4C8;display:block}
  .pe-mock-bar i:first-child{background:#F2B8B5}
  .pe-mock-bar i:nth-child(2){background:#F2DCA0}
  .pe-mock-bar i:nth-child(3){background:#B7E1CD}
  .pe-mock-url{margin-left:8px;flex:1;max-width:260px;background:var(--surface);border:1px solid var(--line);border-radius:999px;
               font-size:11px;color:var(--muted);padding:3px 12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .pe-mock-body{padding:18px}
  .pe-mock-row{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;font-size:12.5px}
  .pe-mock-row:nth-child(even){background:var(--mock-row)}
  .pe-mock-dot{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-family:var(--display);font-weight:700;font-size:10px;color:#fff;flex-shrink:0}
  .pe-mock-chip{margin-left:auto;font-family:var(--display);font-weight:700;font-size:10px;letter-spacing:.04em;text-transform:uppercase;border-radius:999px;padding:3px 10px}
  .pe-chip-ok{background:rgba(66,158,189,.14);color:#034A6B}
  html[data-theme="dark"] .pe-chip-ok{color:#9FE7F5}
  .pe-chip-warn{background:rgba(242,127,12,.16);color:#B35F08}
  html[data-theme="dark"] .pe-chip-warn{color:#F7AD19}
  .pe-mock-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
  .pe-mock-stat{background:var(--mock-row);border:1px solid var(--line);border-radius:12px;padding:10px 12px}
  .pe-mock-stat b{display:block;font-family:var(--display);font-size:17px}
  .pe-mock-stat span{font-size:10.5px;color:var(--muted)}
  .pe-mock-bars{display:flex;align-items:flex-end;gap:10px;height:110px;padding:6px 4px 0}
  .pe-mock-bars i{flex:1;border-radius:6px 6px 2px 2px;background:var(--sign);display:block}
  .pe-mock-bars i.alt{background:var(--gold)}
  .pe-mock-labels{display:flex;gap:10px;padding:6px 4px 0;font-size:10px;color:var(--muted)}
  .pe-mock-labels span{flex:1;text-align:center}
  .pe-bubble{max-width:78%;border-radius:14px;padding:9px 13px;font-size:12.5px;margin-bottom:8px}
  .pe-bubble-in{background:var(--mock-bubble);border:1px solid var(--line)}
  .pe-bubble-out{background:var(--ink);color:#fff;margin-left:auto}

  .pe-feature-row{display:grid;grid-template-columns:1fr 1fr;gap:clamp(24px,5vw,64px);align-items:center;padding:clamp(28px,4vw,44px) 0}
  .pe-feature-row + .pe-feature-row{border-top:1px solid var(--line)}
  .pe-feature-row h3{font-size:clamp(20px,2.6vw,27px);margin:0 0 10px}
  .pe-feature-row p{color:var(--muted);margin:0;font-size:15.5px}
  .pe-feature-copy .pe-card-icon{margin-bottom:16px}
  @media(max-width:820px){
    .pe-feature-row{grid-template-columns:1fr}
    .pe-feature-row .pe-feature-mock{order:2}
  }

  .pe-pricing-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));align-items:stretch}
  .pe-price-card{position:relative;display:flex;flex-direction:column;background:var(--surface);border:1px solid var(--line);
                 border-radius:22px;padding:28px 26px;transition:transform .2s ease,box-shadow .2s ease}
  .pe-price-card:hover{transform:translateY(-4px);box-shadow:0 18px 40px -22px rgba(11,16,32,.35)}
  @media(prefers-reduced-motion:reduce){.pe-price-card{transition:none}.pe-price-card:hover{transform:none}}
  .pe-price-card--hot{background:var(--ink);color:#fff;border-color:transparent;
                      box-shadow:0 26px 54px -26px rgba(5,63,92,.45)}
  .pe-price-card--hot .pe-price-amount,.pe-price-card--hot h3{color:#fff}
  .pe-price-card--hot .pe-price-tagline,.pe-price-card--hot .pe-price-period{color:var(--ink-muted)}
  .pe-price-card--hot li{color:#E7EAF3}
  .pe-price-badge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);white-space:nowrap;
                  background:var(--voice);color:#fff;font-family:var(--display);font-weight:700;font-size:11px;
                  letter-spacing:.08em;text-transform:uppercase;border-radius:999px;padding:5px 14px}
  .pe-price-card h3{font-size:19px;margin:0 0 4px}
  .pe-price-tagline{color:var(--muted);font-size:13.5px;margin:0 0 18px;min-height:20px}
  .pe-price-amount{font-family:var(--display);font-weight:800;font-size:clamp(26px,3vw,34px);letter-spacing:-.02em}
  .pe-price-period{color:var(--muted);font-size:13px;margin:2px 0 18px}
  .pe-price-card ul{list-style:none;margin:0 0 22px;padding:0;display:flex;flex-direction:column;gap:9px;flex:1}
  .pe-price-card li{display:flex;gap:9px;align-items:flex-start;font-size:14px;color:var(--muted)}
  .pe-price-card li svg{width:16px;height:16px;flex-shrink:0;margin-top:3px;color:var(--sign)}

  .pe-faq{max-width:720px;margin:0 auto}
  .pe-faq details{background:var(--surface);border:1px solid var(--line);border-radius:16px;margin-bottom:10px;overflow:hidden}
  .pe-faq summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:14px;
                  padding:16px 20px;font-family:var(--display);font-weight:600;font-size:15.5px}
  .pe-faq summary::-webkit-details-marker{display:none}
  .pe-faq summary::after{content:"+";font-size:20px;color:var(--muted);transition:transform .2s ease}
  .pe-faq details[open] summary::after{transform:rotate(45deg)}
  @media(prefers-reduced-motion:reduce){.pe-faq summary::after{transition:none}}
  .pe-faq details p{margin:0;padding:0 20px 16px;color:var(--muted);font-size:14.5px}

  .pe-quote{background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:24px}
  .pe-quote p{margin:0 0 12px;font-size:15.5px}
  .pe-quote cite{color:var(--muted);font-size:13px;font-style:normal}

  .pe-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:var(--input-bg);color:var(--fg);font:inherit;font-size:16px;margin-bottom:12px}
  .pe-input:focus{border-color:var(--sign);outline:none}
  .pe-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--fg);margin:0 0 6px}
  .pe-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .pe-status{background:var(--status-bg);border:1px solid var(--sign);color:var(--fg);padding:12px 16px;margin-bottom:16px;border-radius:12px;font-size:15px}
  .pe-form-card{position:relative;max-width:480px;margin:0 auto;background:var(--surface);color:var(--fg);border:1px solid var(--line);
                border-radius:20px;padding:clamp(20px,4vw,28px);text-align:left;box-shadow:0 18px 40px -28px rgba(11,16,32,.35)}
  .pe-form-card .pe-btn,.pe-form-card .pe-btn-grad{min-height:48px;width:100%;justify-content:center}
  html[data-theme="dark"] .pe-form-card{background:var(--surface);border-color:var(--line);box-shadow:0 18px 40px -24px rgba(0,0,0,.55)}
  html[data-theme="dark"] .pe-form-card .pe-input{background:var(--paper);border-color:var(--line);color:var(--fg)}
  html[data-theme="dark"] .pe-form-card .pe-label{color:var(--fg)}
  @media(max-width:640px){.pe-form-card{padding:20px 16px;border-radius:16px}}

  .pe-footer{background:var(--ink);color:#c7cdda;padding:56px 24px 28px}
  .pe-footer-inner{max-width:1120px;margin:0 auto}
  .pe-footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:8px}
  .pe-footer-brand b{font-family:var(--display);font-size:17px;color:#fff}
  .pe-footer-tagline{color:#aeb4c2;font-size:14px;margin:0 0 32px}
  .pe-footer-cols{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;padding-bottom:26px;border-bottom:1px solid rgba(255,255,255,.12)}
  .pe-footer-col h4{font-family:var(--display);font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin:0 0 14px}
  .pe-footer-col a,.pe-footer-col span{display:block;color:#c7cdda;font-size:14px;margin-bottom:10px}
  .pe-footer-col a:hover{color:#fff}
  .pe-footer-bottom{padding-top:20px;font-size:13px;color:#8b93a5}
  @media(max-width:640px){.pe-footer-cols{grid-template-columns:1fr;gap:26px}}

  .pe-reveal{opacity:0;transform:translateY(14px);transition:opacity .7s cubic-bezier(.16,1,.3,1),transform .7s cubic-bezier(.16,1,.3,1)}
  .pe-reveal.in{opacity:1;transform:none}
  @media(prefers-reduced-motion:reduce){.pe-reveal{opacity:1;transform:none;transition:none}}
</style>
@yield('head')
</head>
<body>
  <div class="pe-nav-shell">
    <div class="pe-nav">
      <a href="{{ url('/') }}" class="pe-brand" aria-label="PearlEdu home">
        @include('layouts.partials.logo', ['height' => 26, 'color' => 'currentColor', 'label' => 'PearlEdu'])
        <span class="pe-brand-text">
          <span class="pe-brand-name">PearlEdu</span>
          <span class="pe-brand-tagline">By VoxSign Technologies</span>
        </span>
      </a>
      <div class="pe-nav-links" id="pe-nav-links">
        <a href="#how-it-works">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
        <a href="#onboard">Onboard</a>
        <div class="pe-nav-mobile-cta"><a href="{{ url('/login') }}" class="pe-btn">Login</a></div>
      </div>
      <div class="pe-nav-cta">
        <button type="button" class="pe-theme-btn" id="pe-theme-toggle" aria-label="Toggle color theme" title="Toggle light/dark mode">
          <svg class="pe-theme-icon-light" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="pe-theme-icon-dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/></svg>
        </button>
        <a href="{{ url('/login') }}" class="pe-btn">Login</a>
        <button class="pe-nav-toggle" aria-label="Menu" aria-expanded="false" id="pe-nav-toggle">&#9776;</button>
      </div>
    </div>
  </div>
  @if(session('status'))
    <div class="pe-wrap" style="padding-top:20px;position:relative;z-index:5"><div class="pe-status">{{ session('status') }}</div></div>
  @endif
  @yield('content')
  <div class="pe-footer">
    <div class="pe-footer-inner">
      <div class="pe-footer-brand">
        @include('layouts.partials.logo', ['height' => 24, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <b>PearlEdu</b>
      </div>
      <p class="pe-footer-tagline">School management, without the spreadsheets.</p>
      <div class="pe-footer-cols">
        <div class="pe-footer-col">
          <h4>Product</h4>
          <a href="#how-it-works">Features</a>
          <a href="#pricing">Pricing</a>
          <a href="#faq">FAQ</a>
          <a href="#onboard">Onboard your school</a>
        </div>
        <div class="pe-footer-col">
          <h4>Company</h4>
          <a href="https://{{ config('tenancy.base_domain') }}">VoxSign Technologies</a>
        </div>
        <div class="pe-footer-col">
          <h4>Contact</h4>
          <span>+256 770 680769</span>
          <span>info@voxsign.co.ug</span>
        </div>
      </div>
      <div class="pe-footer-bottom">&copy; {{ date('Y') }} PearlEdu, by VoxSign Technologies, Uganda</div>
    </div>
  </div>
  <script>
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, {threshold: .12});
    document.querySelectorAll('.pe-reveal').forEach(function(el){ io.observe(el); });

    var navToggle = document.getElementById('pe-nav-toggle');
    var navLinks = document.getElementById('pe-nav-links');
    if (navToggle && navLinks) {
      navToggle.addEventListener('click', function(){
        var open = navLinks.classList.toggle('open');
        navToggle.setAttribute('aria-expanded', open);
      });
      navLinks.querySelectorAll('a').forEach(function(link){
        link.addEventListener('click', function(){
          navLinks.classList.remove('open');
          navToggle.setAttribute('aria-expanded', 'false');
        });
      });
    }

    var themeKey = 'voxsign-color-scheme';
    var themeBtn = document.getElementById('pe-theme-toggle');
    function peApplyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      try { localStorage.setItem(themeKey, theme); } catch (e) {}
      if (themeBtn) {
        themeBtn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      }
    }
    if (themeBtn) {
      themeBtn.addEventListener('click', function(){
        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        peApplyTheme(next);
      });
    }
  </script>
</body>
</html>
