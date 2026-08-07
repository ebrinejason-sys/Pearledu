<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'PearlEdu — School Management Platform by VoxSign')</title>
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
    --ink:#053F5C; --ink-2:#034A6B; --paper:#FFFFFF; --surface:#FFFFFF;
    --fg:#2A3542; --logo:#FFFFFF;
    --voice:#F27F0C; --gold:#F7AD19; --sign:#429EBD; --cyan:#9FE7F5;
    --muted:#5B6B78; --line:#E6EEF2; --hero:#053F5C;
    --input-bg:#fff; --status-bg:#E8F7FA;
    --display:'Google Sans',system-ui,sans-serif;
    --body:'Satoshi',system-ui,sans-serif;
  }
  html[data-theme="dark"]{
    color-scheme:dark;
    --paper:#0B1220; --surface:#141B2A; --fg:#E8EEF5; --logo:#FFFFFF;
    --muted:#9AA8B8; --line:#2A3447; --hero:#0B1220;
    --input-bg:#141B2A; --status-bg:rgba(66,158,189,.16);
  }
  *{box-sizing:border-box} html,body{margin:0}
  html{scroll-behavior:smooth}
  @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  body{font-family:var(--body);background:var(--paper);color:var(--fg);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3,h4{font-family:var(--display);line-height:1.15;letter-spacing:-.01em;margin:0}
  a{color:inherit;text-decoration:none}
  .pe-wrap{max-width:1140px;margin:0 auto;padding:0 24px}
  .pe-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  /* EMIS-style flat portal header */
  .pe-topbar{background:var(--hero);color:#fff;position:sticky;top:0;z-index:50;border-bottom:1px solid rgba(255,255,255,.12)}
  .pe-nav{display:flex;align-items:center;gap:18px;max-width:1140px;margin:0 auto;padding:12px 24px;min-height:72px}
  .pe-brand{display:flex;align-items:center;gap:12px;color:#fff;min-width:0;flex-shrink:0}
  .vx-logo{display:block;flex-shrink:0;height:var(--vx-logo-h,34px);width:auto;filter:none}
  .pe-brand-text{display:flex;flex-direction:column;line-height:1.15;min-width:0}
  .pe-brand-name{font-family:var(--display);font-weight:700;font-size:18px;color:#fff;letter-spacing:.02em;text-transform:uppercase}
  .pe-brand-tagline{font-size:11px;color:rgba(255,255,255,.78);letter-spacing:.04em;text-transform:uppercase}
  .pe-nav-links{margin-left:auto;display:flex;align-items:center;gap:22px;font-family:var(--display);font-size:13px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.88)}
  .pe-nav-links a:hover{color:#fff}
  .pe-nav-cta{display:flex;gap:10px;align-items:center;margin-left:8px}
  .pe-theme-btn{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:8px;
                border:1.5px solid rgba(255,255,255,.35);background:transparent;color:#fff;cursor:pointer;flex-shrink:0}
  .pe-theme-btn:hover{border-color:#fff;background:rgba(255,255,255,.08)}
  .pe-theme-btn .pe-theme-icon-dark{display:none}
  html[data-theme="dark"] .pe-theme-btn .pe-theme-icon-light{display:none}
  html[data-theme="dark"] .pe-theme-btn .pe-theme-icon-dark{display:block}
  .pe-nav-toggle{display:none;background:none;border:1.5px solid rgba(255,255,255,.35);border-radius:8px;padding:8px 12px;font-size:18px;cursor:pointer;color:#fff}
  .pe-nav-mobile-cta{display:none}

  .pe-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-family:var(--display);font-weight:700;font-size:13px;
          letter-spacing:.06em;text-transform:uppercase;background:transparent;color:#fff;border:1.5px solid #fff;border-radius:8px;padding:11px 18px;cursor:pointer}
  .pe-btn:hover{background:rgba(255,255,255,.1)}
  .pe-btn-grad{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-family:var(--display);font-weight:700;font-size:13px;
          letter-spacing:.06em;text-transform:uppercase;background:var(--voice);color:#fff;border:1.5px solid var(--voice);border-radius:8px;padding:11px 18px;cursor:pointer}
  .pe-btn-grad:hover{filter:brightness(1.05)}
  .pe-btn-ghost{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-family:var(--display);font-weight:700;font-size:13px;
                letter-spacing:.06em;text-transform:uppercase;background:transparent;color:#fff;border:1.5px solid #fff;border-radius:8px;padding:11px 18px;cursor:pointer}
  .pe-btn-ghost:hover{background:rgba(255,255,255,.1)}
  .pe-btn-solid{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-family:var(--display);font-weight:700;font-size:13px;
                letter-spacing:.06em;text-transform:uppercase;background:#fff;color:var(--ink);border:1.5px solid #fff;border-radius:8px;padding:11px 18px;cursor:pointer}
  .pe-btn-solid:hover{background:var(--cyan);border-color:var(--cyan)}

  @media(max-width:900px){
    .pe-brand-tagline{display:none}
    .pe-nav-links{display:none;position:absolute;top:72px;left:0;right:0;flex-direction:column;align-items:stretch;
                  background:var(--ink-2);padding:16px 20px;gap:14px;margin:0;border-bottom:1px solid rgba(255,255,255,.12)}
    .pe-nav-links.open{display:flex}
    .pe-nav-cta .pe-btn,.pe-nav-cta .pe-btn-grad{display:none}
    .pe-nav-toggle{display:block}
    .pe-nav-mobile-cta{display:flex;margin-top:8px;gap:10px}
    .pe-nav-mobile-cta .pe-btn,.pe-nav-mobile-cta .pe-btn-grad{width:100%;min-height:46px}
    .pe-nav{position:relative}
  }

  .pe-cta-row{display:flex;gap:12px;flex-wrap:wrap}
  @media(max-width:640px){
    .pe-cta-row{flex-direction:column;align-items:stretch;width:100%;max-width:360px}
    .pe-cta-row > a{width:100%;min-height:48px}
  }

  .pe-section{padding:clamp(48px,7vw,84px) 0}
  .pe-hero{background:var(--hero);color:#fff;padding:clamp(48px,8vw,96px) 0 clamp(56px,9vw,110px)}
  .pe-hero-inner{max-width:760px}
  .pe-hero h1{font-size:clamp(28px,4.2vw,42px);font-weight:700;margin:0 0 16px;color:#fff}
  .pe-hero p{margin:0 0 28px;font-size:clamp(16px,2vw,18px);color:rgba(255,255,255,.86);max-width:640px;line-height:1.65}
  .pe-hero .pe-cta-row .pe-btn-solid,
  .pe-hero .pe-cta-row .pe-btn-ghost{min-width:160px}

  .pe-modules{background:var(--paper)}
  .pe-modules-head{margin-bottom:clamp(28px,4vw,40px)}
  .pe-modules-head h2{font-size:clamp(24px,3vw,34px);color:var(--ink);margin:0 0 8px}
  html[data-theme="dark"] .pe-modules-head h2{color:var(--fg)}
  .pe-modules-head p{margin:0;color:var(--muted);max-width:620px;font-size:16px}
  .pe-module-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px 36px}
  @media(max-width:900px){.pe-module-grid{grid-template-columns:1fr 1fr}}
  @media(max-width:640px){.pe-module-grid{grid-template-columns:1fr}}
  .pe-module{display:flex;gap:16px;align-items:flex-start}
  .pe-module-icon{flex-shrink:0;width:52px;height:52px;border-radius:10px;display:grid;place-items:center;
                  background:rgba(66,158,189,.12);color:var(--sign)}
  .pe-module-icon svg{width:26px;height:26px}
  .pe-module h3{font-size:17px;font-weight:700;margin:0 0 6px;color:var(--ink)}
  html[data-theme="dark"] .pe-module h3{color:var(--fg)}
  .pe-module p{margin:0;font-size:14.5px;color:var(--muted);line-height:1.55}

  .pe-stats{background:var(--sign);color:#fff;padding:clamp(36px,5vw,56px) 0;position:relative;overflow:hidden}
  .pe-stats::before{content:"";position:absolute;inset:0;opacity:.18;pointer-events:none;
    background:radial-gradient(circle at 20% 40%,#fff 0 1px,transparent 2px),
               radial-gradient(circle at 70% 60%,#fff 0 1px,transparent 2px),
               linear-gradient(120deg,transparent 40%,rgba(255,255,255,.12) 50%,transparent 60%)}
  .pe-stats-grid{position:relative;z-index:1;display:grid;grid-template-columns:repeat(4,1fr);gap:20px;text-align:center}
  @media(max-width:800px){.pe-stats-grid{grid-template-columns:1fr 1fr}}
  @media(max-width:480px){.pe-stats-grid{grid-template-columns:1fr}}
  .pe-stat b{display:block;font-family:var(--display);font-size:clamp(28px,4vw,40px);font-weight:700;letter-spacing:-.02em}
  .pe-stat span{display:block;margin-top:6px;font-size:13px;color:rgba(255,255,255,.88)}

  .pe-tint{background:#F5FBFD}
  html[data-theme="dark"] .pe-tint{background:var(--paper)}
  .pe-sec-label{font-family:var(--display);font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--voice);font-weight:700;margin:0 0 10px}
  .pe-h2{font-size:clamp(24px,3vw,34px);font-weight:700;color:var(--ink);margin:0 0 10px}
  html[data-theme="dark"] .pe-h2{color:var(--fg)}
  .pe-lead{color:var(--muted);max-width:640px;font-size:16px;margin:0}
  .pe-sec-head{margin-bottom:clamp(24px,4vw,36px)}

  .pe-pricing-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));align-items:stretch}
  .pe-price-card{position:relative;display:flex;flex-direction:column;background:var(--surface);border:1px solid var(--line);
                 border-radius:12px;padding:28px 26px}
  .pe-price-card--hot{background:var(--ink);color:#fff;border-color:transparent}
  .pe-price-card--hot .pe-price-amount,.pe-price-card--hot h3{color:#fff}
  .pe-price-card--hot .pe-price-tagline,.pe-price-card--hot .pe-price-period{color:rgba(255,255,255,.75)}
  .pe-price-card--hot li{color:rgba(255,255,255,.88)}
  .pe-price-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);white-space:nowrap;
                  background:var(--voice);color:#fff;font-family:var(--display);font-weight:700;font-size:11px;
                  letter-spacing:.08em;text-transform:uppercase;border-radius:999px;padding:5px 14px}
  .pe-price-card h3{font-size:18px;margin:0 0 4px}
  .pe-price-tagline{color:var(--muted);font-size:13.5px;margin:0 0 16px}
  .pe-price-amount{font-family:var(--display);font-weight:800;font-size:clamp(26px,3vw,32px)}
  .pe-price-period{color:var(--muted);font-size:13px;margin:2px 0 16px}
  .pe-price-card ul{list-style:none;margin:0 0 20px;padding:0;display:flex;flex-direction:column;gap:9px;flex:1}
  .pe-price-card li{display:flex;gap:9px;align-items:flex-start;font-size:14px;color:var(--muted)}
  .pe-price-card li svg{width:16px;height:16px;flex-shrink:0;margin-top:3px;color:var(--sign)}
  .pe-price-card .pe-btn-grad,.pe-price-card .pe-btn{width:100%;border-radius:8px}
  .pe-price-card:not(.pe-price-card--hot) .pe-btn{color:var(--ink);border-color:var(--ink)}
  .pe-price-card:not(.pe-price-card--hot) .pe-btn:hover{background:rgba(5,63,92,.06)}

  .pe-faq-wrap{max-width:820px;margin:0 auto}
  .pe-faq-wrap > h2{text-align:center}
  .pe-faq-wrap > p{text-align:center;margin:0 auto 28px}
  .pe-faq details{background:var(--surface);border:1px solid var(--line);border-radius:10px;margin-bottom:10px;overflow:hidden}
  .pe-faq summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:14px;
                  padding:16px 18px;font-family:var(--display);font-weight:600;font-size:15.5px}
  .pe-faq summary::-webkit-details-marker{display:none}
  .pe-faq summary::after{content:"+";font-size:20px;color:var(--muted)}
  .pe-faq details[open] summary::after{content:"–"}
  .pe-faq details p{margin:0;padding:0 18px 16px;color:var(--muted);font-size:14.5px}
  .pe-faq-mail{text-align:center;margin-top:22px;color:var(--muted);font-size:15px}
  .pe-faq-mail a{color:var(--sign);font-weight:600;text-decoration:underline}

  .pe-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:8px;background:var(--input-bg);color:var(--fg);font:inherit;font-size:16px;margin-bottom:12px}
  .pe-input:focus{border-color:var(--sign);outline:none}
  .pe-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--fg);margin:0 0 6px}
  .pe-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .pe-status{background:var(--status-bg);border:1px solid var(--sign);color:var(--fg);padding:12px 16px;margin:16px 0;border-radius:10px;font-size:15px}
  .pe-form-card{position:relative;max-width:520px;margin:0 auto;background:var(--surface);color:var(--fg);border:1px solid var(--line);
                border-radius:12px;padding:clamp(20px,4vw,28px);text-align:left;box-shadow:0 12px 32px -24px rgba(11,16,32,.35)}
  .pe-form-card .pe-btn-grad{min-height:48px;width:100%}
  .pe-onboard{background:var(--hero);color:#fff}
  .pe-onboard .pe-h2,.pe-onboard .pe-sec-label{color:#fff}
  .pe-onboard .pe-lead{color:rgba(255,255,255,.82);margin-left:auto;margin-right:auto}
  .pe-onboard .pe-sec-label{color:var(--cyan)}

  .pe-footer{background:#032A3D;color:#c7cdda;padding:48px 24px 24px}
  .pe-footer-inner{max-width:1140px;margin:0 auto}
  .pe-footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:8px}
  .pe-footer-brand b{font-family:var(--display);font-size:16px;color:#fff;text-transform:uppercase;letter-spacing:.04em}
  .pe-footer-tagline{color:#aeb4c2;font-size:14px;margin:0 0 28px}
  .pe-footer-cols{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;padding-bottom:22px;border-bottom:1px solid rgba(255,255,255,.12)}
  .pe-footer-col h4{font-family:var(--display);font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#fff;margin:0 0 12px}
  .pe-footer-col a,.pe-footer-col span{display:block;color:#c7cdda;font-size:14px;margin-bottom:8px}
  .pe-footer-col a:hover{color:#fff}
  .pe-footer-bottom{padding-top:18px;font-size:13px;color:#8b93a5}
  @media(max-width:640px){.pe-footer-cols{grid-template-columns:1fr;gap:22px}}

  .pe-reveal{opacity:0;transform:translateY(12px);transition:opacity .6s ease,transform .6s ease}
  .pe-reveal.in{opacity:1;transform:none}
  @media(prefers-reduced-motion:reduce){.pe-reveal{opacity:1;transform:none;transition:none}}
</style>
@yield('head')
</head>
<body>
  <header class="pe-topbar">
    <div class="pe-nav">
      <a href="{{ url('/') }}" class="pe-brand" aria-label="PearlEdu home">
        @include('layouts.partials.logo', ['height' => 34, 'color' => '#FFFFFF', 'label' => 'PearlEdu'])
        <span class="pe-brand-text">
          <span class="pe-brand-name">PearlEdu</span>
          <span class="pe-brand-tagline">By VoxSign Technologies</span>
        </span>
      </a>
      <nav class="pe-nav-links" id="pe-nav-links" aria-label="Primary">
        <a href="#modules">Modules</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
        <a href="#onboard">Onboard</a>
        <div class="pe-nav-mobile-cta">
          <a href="{{ url('/login') }}" class="pe-btn">Login</a>
          <a href="#onboard" class="pe-btn-grad">Onboard</a>
        </div>
      </nav>
      <div class="pe-nav-cta">
        <button type="button" class="pe-theme-btn" id="pe-theme-toggle" aria-label="Toggle color theme" title="Toggle light/dark mode">
          <svg class="pe-theme-icon-light" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="pe-theme-icon-dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/></svg>
        </button>
        <a href="{{ url('/login') }}" class="pe-btn">Login</a>
        <a href="#onboard" class="pe-btn-grad">Onboard</a>
        <button class="pe-nav-toggle" aria-label="Menu" aria-expanded="false" id="pe-nav-toggle">&#9776;</button>
      </div>
    </div>
  </header>

  @if(session('status'))
    <div class="pe-wrap"><div class="pe-status">{{ session('status') }}</div></div>
  @endif

  @yield('content')

  <footer class="pe-footer">
    <div class="pe-footer-inner">
      <div class="pe-footer-brand">
        @include('layouts.partials.logo', ['height' => 24, 'color' => '#9FE7F5', 'label' => 'PearlEdu'])
        <b>PearlEdu</b>
      </div>
      <p class="pe-footer-tagline">School management platform for institutions — by VoxSign Technologies.</p>
      <div class="pe-footer-cols">
        <div class="pe-footer-col">
          <h4>Product</h4>
          <a href="#modules">Modules</a>
          <a href="#pricing">Pricing</a>
          <a href="#faq">FAQ</a>
          <a href="#onboard">Onboard your school</a>
        </div>
        <div class="pe-footer-col">
          <h4>Company</h4>
          <a href="https://{{ config('tenancy.base_domain') }}">VoxSign Technologies</a>
          <a href="{{ url('/login') }}">Staff login</a>
        </div>
        <div class="pe-footer-col">
          <h4>Contact</h4>
          <span>+256 770 680769</span>
          <span>info@voxsign.co.ug</span>
        </div>
      </div>
      <div class="pe-footer-bottom">&copy; {{ date('Y') }} PearlEdu · VoxSign Technologies, Uganda. All rights reserved.</div>
    </div>
  </footer>

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
