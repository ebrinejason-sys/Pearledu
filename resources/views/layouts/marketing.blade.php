<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'VoxSign — Speak the Future. See It Signed.')</title>
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
    --voice:#F27F0C; --sign:#429EBD; --cyan:#9FE7F5; --gold:#F7AD19;
    --muted:#4A6270; --line:#D4E8EE;
    --ink-line:rgba(255,255,255,.12); --ink-muted:#9FE7F5;
    --nav-glass:rgba(255,255,255,.84);
    --input-bg:#fff; --status-bg:#E8F7FA;
    --display:'Google Sans',system-ui,sans-serif;
    --body:'Satoshi',system-ui,sans-serif;
  }
  html[data-theme="dark"]{
    color-scheme:dark;
    --paper:#0B1220; --surface:#141B2A; --fg:#E8EEF5; --logo:#9FE7F5;
    --muted:#9AA8B8; --line:#2A3447;
    --nav-glass:rgba(20,27,42,.92);
    --input-bg:#141B2A; --status-bg:rgba(66,158,189,.16);
  }
  *{box-sizing:border-box} html,body{margin:0}
  html{scroll-behavior:smooth}
  @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  body{font-family:var(--body);background:var(--paper);color:var(--fg);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3{font-family:var(--display);line-height:1.08;letter-spacing:-.02em;margin:0}
  a{color:inherit;text-decoration:none}
  .vx-wrap{max-width:1120px;margin:0 auto;padding:0 24px}
  .vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  /* Floating pill nav */
  .vx-nav-shell{position:sticky;top:12px;z-index:50;padding:0 16px}
  .vx-nav{position:relative;display:flex;align-items:center;gap:12px;max-width:1120px;margin:0 auto;padding:10px 14px 10px 16px;
          background:var(--nav-glass);backdrop-filter:blur(14px);border:1px solid var(--line);
          border-radius:999px;box-shadow:0 12px 34px -22px rgba(11,16,32,.45)}
  .vx-logo-link{display:flex;align-items:center;color:var(--logo);min-width:0}
  .vx-logo{display:block;flex-shrink:0;height:var(--vx-logo-h,30px);width:auto}
  @media(max-width:860px){
    .vx-logo-link .vx-logo{--vx-logo-h:22px;height:22px}
  }
  .vx-nav-links{margin-left:10px;display:flex;gap:22px;font-size:14.5px;color:var(--muted);flex-wrap:wrap}
  .vx-nav-links a:hover{color:var(--fg)}
  .vx-nav-cta{margin-left:auto;display:flex;align-items:center;gap:8px}
  .vx-nav-cta .vx-btn{padding:10px 20px}
  .vx-theme-btn{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:999px;
                 border:1.5px solid var(--line);background:transparent;color:var(--fg);cursor:pointer;flex-shrink:0}
  .vx-theme-btn:hover{border-color:var(--fg)}
  .vx-theme-btn .vx-theme-icon-dark{display:none}
  html[data-theme="dark"] .vx-theme-btn .vx-theme-icon-light{display:none}
  html[data-theme="dark"] .vx-theme-btn .vx-theme-icon-dark{display:block}
  .vx-nav-toggle{display:none;background:none;border:1.5px solid var(--line);border-radius:999px;padding:8px 12px;font-size:18px;cursor:pointer;color:var(--fg)}
  .vx-nav-mobile-cta{display:none}
  @media(max-width:860px){
    .vx-nav-links{display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;flex-direction:column;
                  background:var(--surface);border:1px solid var(--line);border-radius:20px;
                  box-shadow:0 18px 40px -20px rgba(11,16,32,.4);padding:16px 20px;gap:14px;margin:0}
    .vx-nav-links.open{display:flex}
    .vx-nav-cta .vx-btn{display:none}
    .vx-nav-toggle{display:block}
    .vx-nav-mobile-cta{display:flex;margin-top:4px}
    .vx-nav-mobile-cta .vx-btn{width:100%;justify-content:center;min-height:48px}
  }

  /* Mobile centered pill CTAs */
  .vx-cta-row{display:flex;gap:12px;flex-wrap:wrap}
  @media(max-width:640px){
    .vx-cta-row{flex-direction:column;align-items:stretch;width:100%;max-width:360px;margin-left:auto;margin-right:auto}
    .vx-cta-row > a,.vx-cta-row > .vx-btn,.vx-cta-row > .vx-btn-grad,.vx-cta-row > .vx-btn-ghost{
      width:100%;justify-content:center;text-align:center;min-height:48px;box-sizing:border-box}
    .vx-hero-copy{text-align:center;margin-left:auto;margin-right:auto}
    .vx-hero-copy .vx-lead{margin-left:auto;margin-right:auto}
    .vx-hero-copy .vx-h1{margin-left:auto;margin-right:auto}
  }

  .vx-section{padding:clamp(48px,8vw,88px) 0;border-bottom:1px solid var(--line)}
  .vx-section:last-of-type{border-bottom:0}
  .vx-band{background:var(--ink);color:#fff;border-bottom:0}
  .vx-band .vx-eyebrow{color:var(--cyan)}
  .vx-band .vx-lead{color:#C5DDE8}
  .vx-band .vx-btn-ghost{color:#fff;border-color:var(--ink-line)}
  .vx-band .vx-btn-ghost:hover{border-color:#fff}
  .vx-section:nth-of-type(even):not(.vx-band){background:var(--paper)}

  .vx-eyebrow{display:inline-block;font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--voice);font-weight:700;margin-bottom:14px;text-transform:uppercase;background:rgba(242,127,12,.1);border:1px solid rgba(242,127,12,.25);border-radius:999px;padding:5px 14px}
  .vx-band .vx-eyebrow{background:rgba(159,231,245,.14);border-color:rgba(159,231,245,.35)}
  .vx-h1{font-size:clamp(34px,5.8vw,64px);font-weight:800;line-height:1.04;max-width:720px;margin:0 0 18px}
  .vx-h1 .vx-flow{color:var(--cyan)}
  .vx-hero{position:relative;overflow-x:hidden;overflow-y:visible;margin-top:-66px;
           padding-top:calc(clamp(48px,8vw,88px) + 96px)}
  .vx-hero .vx-wrap{position:relative;z-index:1}
  .vx-h2{font-size:clamp(26px,3.8vw,40px);font-weight:700;margin:0 0 6px}
  .vx-lead{color:var(--muted);max-width:620px;font-size:17px}
  .vx-sec-head{margin-bottom:clamp(28px,4vw,44px)}

  .vx-btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--ink);color:#fff;border:1.5px solid var(--ink);border-radius:999px;padding:12px 22px;cursor:pointer;
          transition:transform .15s ease,box-shadow .2s ease}
  .vx-btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px rgba(11,16,32,.5)}
  .vx-btn-grad{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--voice);color:#fff;border:0;border-radius:999px;padding:13px 24px;cursor:pointer;
          box-shadow:0 10px 26px -12px rgba(242,127,12,.45);transition:transform .15s ease,box-shadow .2s ease}
  .vx-btn-grad:hover{transform:translateY(-2px);box-shadow:0 16px 34px -12px rgba(66,158,189,.45)}
  .vx-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
                background:transparent;color:var(--fg);border:1.5px solid var(--line);border-radius:999px;padding:12px 22px;cursor:pointer}
  .vx-btn-ghost:hover{border-color:var(--fg)}
  @media(prefers-reduced-motion:reduce){.vx-btn,.vx-btn-grad{transition:none}.vx-btn:hover,.vx-btn-grad:hover{transform:none}}

  .vx-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}
  .vx-grid-team{max-width:960px;grid-template-columns:repeat(2,1fr)}
  @media(min-width:720px){.vx-grid-team{grid-template-columns:repeat(4,1fr)}}
  .vx-grid-team .vx-card{padding:14px}
  @media(max-width:480px){.vx-grid-team{gap:10px}.vx-grid-team .vx-card{padding:10px}.vx-grid-team .vx-card h3{font-size:14px}.vx-grid-team .vx-card p{font-size:12px}}
  .vx-card{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:22px;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease}
  .vx-card::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:var(--sign);transform:scaleX(0);transform-origin:left;transition:transform .25s ease}
  .vx-card:hover::before{transform:scaleX(1)}
  @media(prefers-reduced-motion:reduce){.vx-card::before{transition:none}}
  .vx-card:hover{transform:translateY(-3px);box-shadow:0 18px 36px -20px rgba(11,16,32,.35)}
  .vx-band .vx-card{background:rgba(255,255,255,.05);border-color:var(--ink-line);backdrop-filter:blur(6px)}
  .vx-band .vx-card h3{color:#fff}
  .vx-band .vx-card p{color:var(--ink-muted)}
  @media(prefers-reduced-motion:reduce){.vx-card{transition:none}.vx-card:hover{transform:none}}
  .vx-grid .vx-card:nth-child(1){transition-delay:0ms}
  .vx-grid .vx-card:nth-child(2){transition-delay:50ms}
  .vx-grid .vx-card:nth-child(3){transition-delay:100ms}
  .vx-grid .vx-card:nth-child(4){transition-delay:150ms}
  .vx-grid .vx-card:nth-child(5){transition-delay:200ms}
  .vx-grid .vx-card:nth-child(n+6){transition-delay:250ms}
  @media(prefers-reduced-motion:reduce){.vx-grid .vx-card{transition-delay:0ms!important}}
  .vx-card img{width:100%;aspect-ratio:1;object-fit:cover;margin-bottom:12px;border-radius:12px}
  .vx-card h3{margin:0 0 6px;font-size:18px;font-weight:700}
  .vx-card p{margin:0;color:var(--muted);font-size:14px}

  .vx-steps{display:flex;gap:22px;flex-wrap:wrap}
  .vx-step{flex:1;min-width:180px}
  .vx-step-badge{position:relative;width:52px;height:52px;border-radius:14px;display:grid;place-items:center;margin-bottom:14px;background:var(--step-color,var(--voice));box-shadow:0 8px 18px -10px rgba(11,16,32,.4)}
  .vx-step-badge svg{width:24px;height:24px;stroke:#fff}
  .vx-step-n{position:absolute;top:-8px;right:-8px;width:22px;height:22px;border-radius:50%;background:var(--ink);color:#fff;font-family:var(--display);font-size:12px;font-weight:800;display:grid;place-items:center;line-height:1;border:2px solid var(--paper)}
  .vx-step h4{margin:8px 0;font-size:17px}
  .vx-step p{margin:0;color:var(--muted);font-size:14px}
  @media(max-width:640px){
    .vx-steps{flex-direction:column;gap:18px}
    .vx-step{display:flex;align-items:flex-start;gap:14px;min-width:0}
    .vx-step-badge{margin-bottom:0;flex-shrink:0}
  }

  .vx-partner-text{color:var(--muted);font-size:13px;border:1px dashed var(--line);padding:10px 14px;border-radius:10px}
  .vx-quote{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:24px 24px 24px 28px;margin-bottom:16px;overflow:hidden}
  .vx-quote::before{content:"";position:absolute;top:0;left:0;bottom:0;width:3px;background:var(--sign)}
  .vx-quote p{margin:0 0 12px;font-size:16px}
  .vx-quote cite{color:var(--muted);font-size:13px;font-style:normal}

  .vx-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:var(--input-bg);color:var(--fg);font:inherit;font-size:16px;margin-bottom:12px}
  .vx-input:focus{border-color:var(--sign);outline:none}
  .vx-input::placeholder{color:var(--muted);opacity:.85}
  .vx-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--fg);margin:0 0 6px}
  .vx-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .vx-status{background:var(--status-bg);border:1px solid var(--sign);color:var(--fg);padding:12px 16px;margin-bottom:16px;border-radius:12px;font-size:15px}
  .vx-contact .vx-h2{color:var(--fg)}
  .vx-form-card{position:relative;max-width:480px;margin:0 auto;background:var(--surface);color:var(--fg);border:1px solid var(--line);
                border-radius:20px;padding:clamp(20px,4vw,28px);box-shadow:0 18px 40px -28px rgba(11,16,32,.35)}
  .vx-form-card .vx-btn-grad{min-height:48px}
  html[data-theme="dark"] .vx-form-card{background:var(--surface);border-color:var(--line);box-shadow:0 18px 40px -24px rgba(0,0,0,.55)}
  html[data-theme="dark"] .vx-form-card .vx-input{background:var(--paper);border-color:var(--line);color:var(--fg)}
  html[data-theme="dark"] .vx-form-card .vx-label{color:var(--fg)}
  @media(max-width:640px){
    .vx-form-card{padding:20px 16px;border-radius:16px}
    .vx-contact .vx-lead{font-size:15px}
  }

  .vx-footer{background:var(--ink);color:#c7cdda;padding:56px 24px 28px}
  .vx-footer-inner{max-width:1120px;margin:0 auto}
  .vx-footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:8px}
  .vx-footer-brand svg{height:28px;width:auto}
  .vx-footer-tagline{color:#aeb4c2;font-size:14px;margin:0 0 36px}
  .vx-footer-cols{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;padding-bottom:28px;border-bottom:1px solid rgba(255,255,255,.12)}
  .vx-footer-col h4{font-family:var(--display);font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin:0 0 14px}
  .vx-footer-col a,.vx-footer-col span{display:block;color:#c7cdda;font-size:14px;margin-bottom:10px}
  .vx-footer-col a:hover{color:#fff}
  .vx-footer-bottom{padding-top:20px;font-size:13px;color:#8b93a5}
  @media(max-width:640px){.vx-footer-cols{grid-template-columns:1fr;gap:28px}}

  .vx-reveal{opacity:0;transform:translateY(14px);transition:opacity .7s cubic-bezier(.16,1,.3,1),transform .7s cubic-bezier(.16,1,.3,1)}
  .vx-reveal.in{opacity:1;transform:none}
  @media(prefers-reduced-motion:reduce){.vx-reveal{opacity:1;transform:none;transition:none}}
</style>
<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.170.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.170.0/examples/jsm/"
  }
}
</script>
</head>
<body>
  <div class="vx-nav-shell">
  <div class="vx-nav">
    <a href="{{ url('/') }}" class="vx-logo-link" aria-label="VoxSign home">
      @include('layouts.partials.logo', ['height' => 28, 'color' => 'currentColor', 'label' => 'VoxSign'])
    </a>
    <div class="vx-nav-links" id="vx-nav-links">
      <a href="#accessibility">Accessibility</a>
      <a href="#pearledu">Institutions</a>
      <a href="#team">Team</a>
      <a href="#contact">Contact</a>
      <div class="vx-nav-mobile-cta"><a href="#contact" class="vx-btn">Talk to us</a></div>
    </div>
    <div class="vx-nav-cta">
      <button type="button" class="vx-theme-btn" id="vx-theme-toggle" aria-label="Toggle color theme" title="Toggle light/dark mode">
        <svg class="vx-theme-icon-light" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="vx-theme-icon-dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/></svg>
      </button>
      <a href="#contact" class="vx-btn">Talk to us</a>
      <button class="vx-nav-toggle" aria-label="Menu" aria-expanded="false" id="vx-nav-toggle">&#9776;</button>
    </div>
  </div>
  </div>
  @if(session('status'))
    <div class="vx-wrap" style="padding-top:20px;position:relative;z-index:5"><div class="vx-status">{{ session('status') }}</div></div>
  @endif
  @yield('content')
  <div class="vx-footer">
    <div class="vx-footer-inner">
      <div class="vx-footer-brand">
        @include('layouts.partials.logo', ['height' => 26, 'color' => '#9FE7F5', 'label' => 'VoxSign'])
      </div>
      <p class="vx-footer-tagline">Technology built to include everyone.</p>
      <div class="vx-footer-cols">
        <div class="vx-footer-col">
          <h4>Products</h4>
          <a href="https://accessibility.{{ config('tenancy.base_domain') }}">VoxSign Accessibility</a>
          <a href="https://pearledu.{{ config('tenancy.base_domain') }}">VoxSign Institutions</a>
        </div>
        <div class="vx-footer-col">
          <h4>Company</h4>
          <a href="#team">Team</a>
          <a href="#contact">Contact</a>
        </div>
        <div class="vx-footer-col">
          <h4>Contact</h4>
          <span>+256 770 680769</span>
          <span>info@voxsign.co.ug</span>
        </div>
      </div>
      <div class="vx-footer-bottom">&copy; {{ date('Y') }} VoxSign, Uganda</div>
    </div>
  </div>
  <script>
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, {threshold: .12});
    document.querySelectorAll('.vx-reveal').forEach(function(el){ io.observe(el); });

    var navToggle = document.getElementById('vx-nav-toggle');
    var navLinks = document.getElementById('vx-nav-links');
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
    var themeBtn = document.getElementById('vx-theme-toggle');
    function vxApplyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      try { localStorage.setItem(themeKey, theme); } catch (e) {}
      if (themeBtn) {
        themeBtn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      }
    }
    if (themeBtn) {
      themeBtn.addEventListener('click', function(){
        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        vxApplyTheme(next);
      });
    }
  </script>
</body>
</html>
