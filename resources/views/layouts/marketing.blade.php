<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'VoxSign — Speak the Future. See It Signed.')</title>
<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#0B1020; --ink-2:#111834; --paper:#FBFAF7; --surface:#FFFFFF;
    --voice:#FF6A3D; --sign:#12B3A6; --muted:#5D6473; --line:#E7E4DC;
    --ink-line:rgba(255,255,255,.12); --ink-muted:#A9B0C2;
    --grad:linear-gradient(100deg,var(--voice),var(--sign));
    --display:'Google Sans',system-ui,sans-serif;
    --body:'Satoshi',system-ui,sans-serif;
  }
  *{box-sizing:border-box} html,body{margin:0}
  html{scroll-behavior:smooth}
  @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  body{font-family:var(--body);background:var(--paper);color:var(--ink);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3{font-family:var(--display);line-height:1.08;letter-spacing:-.02em;margin:0}
  a{color:inherit;text-decoration:none}
  .vx-wrap{max-width:1120px;margin:0 auto;padding:0 24px}
  .vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  /* Floating pill nav */
  .vx-nav-shell{position:sticky;top:12px;z-index:50;padding:0 16px}
  .vx-nav{position:relative;display:flex;align-items:center;gap:18px;max-width:1120px;margin:0 auto;padding:10px 14px 10px 16px;
          background:rgba(255,255,255,.84);backdrop-filter:blur(14px);border:1px solid var(--line);
          border-radius:999px;box-shadow:0 12px 34px -22px rgba(11,16,32,.45)}
  .vx-logo-link{display:flex;align-items:center}
  .vx-logo{height:30px;width:auto;display:block}
  .vx-nav-links{margin-left:10px;display:flex;gap:22px;font-size:14.5px;color:var(--muted);flex-wrap:wrap}
  .vx-nav-links a:hover{color:var(--ink)}
  .vx-nav-cta{margin-left:auto}
  .vx-nav-cta .vx-btn{padding:10px 20px}
  .vx-nav-toggle{display:none;background:none;border:1.5px solid var(--line);border-radius:999px;padding:8px 12px;font-size:18px;cursor:pointer;margin-left:auto}
  @media(max-width:860px){
    .vx-nav-links{display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;flex-direction:column;
                  background:var(--surface);border:1px solid var(--line);border-radius:20px;
                  box-shadow:0 18px 40px -20px rgba(11,16,32,.4);padding:16px 24px;gap:16px;margin:0}
    .vx-nav-links.open{display:flex}
    .vx-nav-cta{display:none}
    .vx-nav-toggle{display:block}
  }

  .vx-section{padding:clamp(48px,8vw,88px) 0;border-bottom:1px solid var(--line)}
  .vx-section:last-of-type{border-bottom:0}
  .vx-band{background:linear-gradient(180deg,var(--ink) 0%,var(--ink-2) 100%);color:#fff;border-bottom:0}
  .vx-band .vx-eyebrow{color:var(--sign)}
  .vx-band .vx-lead{color:#aeb4c2}
  .vx-band .vx-btn-ghost{color:#fff;border-color:var(--ink-line)}
  .vx-band .vx-btn-ghost:hover{border-color:#fff}
  .vx-section:nth-of-type(even):not(.vx-band){background:linear-gradient(180deg,var(--paper) 0%,#F5F3EC 100%)}

  .vx-eyebrow{display:inline-block;font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--voice);font-weight:700;margin-bottom:14px;text-transform:uppercase;background:rgba(255,106,61,.1);border:1px solid rgba(255,106,61,.25);border-radius:999px;padding:5px 14px}
  .vx-band .vx-eyebrow{background:rgba(18,179,166,.14);border-color:rgba(18,179,166,.35)}
  .vx-h1{font-size:clamp(34px,5.8vw,64px);font-weight:800;line-height:1.04;max-width:720px;margin:0 0 18px}
  .vx-h1 .vx-flow{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
  .vx-hero{position:relative;overflow-x:hidden;overflow-y:visible;margin-top:-66px;
           padding-top:calc(clamp(48px,8vw,88px) + 96px)}
  .vx-hero-glow{position:absolute;inset:0;z-index:0;pointer-events:none;
    background:
      radial-gradient(640px 420px at 12% 12%, rgba(255,106,61,.24), transparent 70%),
      radial-gradient(700px 460px at 85% 6%, rgba(18,179,166,.22), transparent 70%)}
  .vx-hero-texture{position:absolute;inset:0;z-index:0;pointer-events:none;opacity:.5;
    background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);
    background-size:44px 44px;
    -webkit-mask-image:radial-gradient(85% 75% at 45% 25%,#000 30%,transparent 100%);
    mask-image:radial-gradient(85% 75% at 45% 25%,#000 30%,transparent 100%)}
  .vx-hero .vx-wrap{position:relative;z-index:1}
  .vx-h1 .vx-flow{background-size:200% 100%;animation:vxFlowShift 6s ease-in-out infinite}
  @keyframes vxFlowShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
  @media(prefers-reduced-motion:reduce){.vx-h1 .vx-flow{animation:none}}
  .vx-h2{font-size:clamp(26px,3.8vw,40px);font-weight:700;margin:0 0 6px}
  .vx-lead{color:var(--muted);max-width:620px;font-size:17px}
  .vx-sec-head{margin-bottom:clamp(28px,4vw,44px)}

  .vx-btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--ink);color:#fff;border:1.5px solid var(--ink);border-radius:999px;padding:12px 22px;cursor:pointer;
          transition:transform .15s ease,box-shadow .2s ease}
  .vx-btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px rgba(11,16,32,.5)}
  .vx-btn-grad{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--grad);color:#fff;border:0;border-radius:999px;padding:13px 24px;cursor:pointer;
          box-shadow:0 10px 26px -12px rgba(255,106,61,.55);transition:transform .15s ease,box-shadow .2s ease}
  .vx-btn-grad:hover{transform:translateY(-2px);box-shadow:0 16px 34px -12px rgba(18,179,166,.5)}
  .vx-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
                background:transparent;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:12px 22px;cursor:pointer}
  .vx-btn-ghost:hover{border-color:var(--ink)}
  @media(prefers-reduced-motion:reduce){.vx-btn,.vx-btn-grad{transition:none}.vx-btn:hover,.vx-btn-grad:hover{transform:none}}

  .vx-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}
  .vx-grid-team{max-width:960px;grid-template-columns:repeat(2,1fr)}
  @media(min-width:720px){.vx-grid-team{grid-template-columns:repeat(4,1fr)}}
  .vx-grid-team .vx-card{padding:14px}
  @media(max-width:480px){.vx-grid-team{gap:10px}.vx-grid-team .vx-card{padding:10px}.vx-grid-team .vx-card h3{font-size:14px}.vx-grid-team .vx-card p{font-size:12px}}
  .vx-card{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:22px;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease}
  .vx-card::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:var(--grad);transform:scaleX(0);transform-origin:left;transition:transform .25s ease}
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
  .vx-quote::before{content:"";position:absolute;top:0;left:0;bottom:0;width:3px;background:var(--grad)}
  .vx-quote p{margin:0 0 12px;font-size:16px}
  .vx-quote cite{color:var(--muted);font-size:13px;font-style:normal}

  .vx-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:inherit;margin-bottom:12px}
  .vx-input:focus{border-color:var(--sign);outline:none}
  .vx-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--ink);margin:0 0 6px}
  .vx-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .vx-status{background:#E9F7F5;border:1px solid var(--sign);color:#0B1020;padding:12px 16px;margin-bottom:16px;border-radius:12px;font-size:15px}

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
      <svg class="vx-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 32" fill="none" role="img" aria-label="VoxSign">
        <defs>
          <linearGradient id="vxLogoGrad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#FF6A3D"/>
            <stop offset="1" stop-color="#12B3A6"/>
          </linearGradient>
        </defs>
        <rect x="0" y="0" width="32" height="32" rx="9" fill="url(#vxLogoGrad)"/>
        <path d="M7 20c2.5-7 5-10.5 9-10.5s6.5 3.5 9 10.5" stroke="#FBFAF7" stroke-width="2.4" stroke-linecap="round" fill="none"/>
        <path d="M12 22.5v-6.2c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v4.2M15.6 20.5v-5.4c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v5.4M19.2 21v-4.6c0-1 .8-1.8 1.7-1.8s1.7.8 1.7 1.8v5.6c0 2.5-1.9 4.5-4.5 4.5h-1.6c-1.5 0-2.9-.7-3.8-1.9l-2.6-3.4a1.5 1.5 0 0 1 2.3-1.9l1.5 1.6" stroke="#FBFAF7" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        <text x="40" y="22" font-family="'Google Sans',system-ui,sans-serif" font-weight="600" font-size="18" fill="#0B1020">VoxSign</text>
      </svg>
    </a>
    <div class="vx-nav-links" id="vx-nav-links">
      <a href="#accessibility">Accessibility</a>
      <a href="#pearledu">Institutions</a>
      <a href="#team">Team</a>
      <a href="#contact">Contact</a>
    </div>
    <div class="vx-nav-cta"><a href="#contact" class="vx-btn">Talk to us</a></div>
    <button class="vx-nav-toggle" aria-label="Menu" aria-expanded="false" id="vx-nav-toggle">&#9776;</button>
  </div>
  </div>
  @if(session('status'))
    <div class="vx-wrap" style="padding-top:20px;position:relative;z-index:5"><div class="vx-status">{{ session('status') }}</div></div>
  @endif
  @yield('content')
  <div class="vx-footer">
    <div class="vx-footer-inner">
      <div class="vx-footer-brand">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 32" fill="none" role="img" aria-label="VoxSign">
          <defs>
            <linearGradient id="vxFooterLogoGrad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
              <stop offset="0" stop-color="#FF6A3D"/>
              <stop offset="1" stop-color="#12B3A6"/>
            </linearGradient>
          </defs>
          <rect x="0" y="0" width="32" height="32" rx="9" fill="url(#vxFooterLogoGrad)"/>
          <path d="M7 20c2.5-7 5-10.5 9-10.5s6.5 3.5 9 10.5" stroke="#FBFAF7" stroke-width="2.4" stroke-linecap="round" fill="none"/>
          <path d="M12 22.5v-6.2c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v4.2M15.6 20.5v-5.4c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v5.4M19.2 21v-4.6c0-1 .8-1.8 1.7-1.8s1.7.8 1.7 1.8v5.6c0 2.5-1.9 4.5-4.5 4.5h-1.6c-1.5 0-2.9-.7-3.8-1.9l-2.6-3.4a1.5 1.5 0 0 1 2.3-1.9l1.5 1.6" stroke="#FBFAF7" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          <text x="40" y="22" font-family="'Google Sans',system-ui,sans-serif" font-weight="600" font-size="18" fill="#fff">VoxSign</text>
        </svg>
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
  </script>
</body>
</html>
