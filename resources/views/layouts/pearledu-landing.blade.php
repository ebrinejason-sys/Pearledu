<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'PearlEdu — School management, without the spreadsheets.')</title>
<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#0B1020; --ink-2:#111834; --paper:#FBFAF7; --surface:#FFFFFF;
    --copper:#C8763B; --copper-deep:#B5652F; --sign:#12B3A6; --muted:#5D6473; --line:#E7E4DC;
    --ink-line:rgba(255,255,255,.12); --ink-muted:#A9B0C2;
    --grad:linear-gradient(100deg,var(--copper),var(--sign));
    --display:'Google Sans',system-ui,sans-serif;
    --body:'Satoshi',system-ui,sans-serif;
  }
  *{box-sizing:border-box} html,body{margin:0}
  html{scroll-behavior:smooth}
  @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  body{font-family:var(--body);background:var(--paper);color:var(--ink);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3,h4{font-family:var(--display);line-height:1.1;letter-spacing:-.02em;margin:0}
  a{color:inherit;text-decoration:none}
  .pe-wrap{max-width:1120px;margin:0 auto;padding:0 24px}
  .pe-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  /* Floating pill nav */
  .pe-nav-shell{position:sticky;top:12px;z-index:50;padding:0 16px}
  .pe-nav{display:flex;align-items:center;gap:16px;max-width:1120px;margin:0 auto;padding:10px 14px 10px 12px;
          background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border:1px solid var(--line);
          border-radius:999px;box-shadow:0 12px 34px -22px rgba(11,16,32,.45)}
  .pe-brand{display:flex;align-items:center;gap:10px;padding-left:4px}
  .pe-brand img{height:32px;width:32px}
  .pe-brand-text{display:flex;flex-direction:column;line-height:1.12}
  .pe-brand-name{font-family:var(--display);font-weight:700;font-size:16px}
  .pe-brand-tagline{font-size:10.5px;color:var(--muted)}
  .pe-nav-links{margin-left:8px;display:flex;gap:20px;font-size:14.5px;color:var(--muted)}
  .pe-nav-links a:hover{color:var(--ink)}
  .pe-nav-cta{margin-left:auto;display:flex;gap:8px;align-items:center}
  @media(max-width:760px){.pe-nav-links{display:none}}

  .pe-btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--ink);color:#fff;border:1.5px solid var(--ink);border-radius:999px;padding:12px 22px;cursor:pointer;
          transition:transform .15s ease,box-shadow .2s ease}
  .pe-btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px rgba(11,16,32,.5)}
  .pe-btn-grad{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--grad);color:#fff;border:0;border-radius:999px;padding:13px 24px;cursor:pointer;
          box-shadow:0 10px 26px -12px rgba(200,118,59,.55);transition:transform .15s ease,box-shadow .2s ease}
  .pe-btn-grad:hover{transform:translateY(-2px);box-shadow:0 16px 34px -12px rgba(18,179,166,.5)}
  .pe-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
                background:transparent;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:12px 22px;cursor:pointer}
  .pe-btn-ghost:hover{border-color:var(--ink)}
  .pe-band .pe-btn-ghost{color:#fff;border-color:var(--ink-line)}
  .pe-band .pe-btn-ghost:hover{border-color:#fff}
  @media(prefers-reduced-motion:reduce){.pe-btn,.pe-btn-grad{transition:none}.pe-btn:hover,.pe-btn-grad:hover{transform:none}}

  .pe-section{padding:clamp(52px,8vw,96px) 0}
  .pe-band{background:linear-gradient(180deg,var(--ink) 0%,var(--ink-2) 100%);color:#fff}
  .pe-band .pe-lead{color:var(--ink-muted)}
  .pe-tint{background:linear-gradient(180deg,var(--paper) 0%,#F4F1E9 100%)}

  .pe-eyebrow{display:inline-block;font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--copper-deep);font-weight:700;margin-bottom:14px;text-transform:uppercase;background:rgba(181,101,47,.1);border:1px solid rgba(181,101,47,.25);border-radius:999px;padding:5px 14px}
  .pe-band .pe-eyebrow{color:var(--sign);background:rgba(18,179,166,.14);border-color:rgba(18,179,166,.35)}
  .pe-h1{font-size:clamp(34px,5.6vw,60px);font-weight:800;line-height:1.04;max-width:760px;margin:0 0 18px}
  .pe-h1 .pe-flow{background:var(--grad);background-size:200% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;animation:peFlow 6s ease-in-out infinite}
  @keyframes peFlow{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
  @media(prefers-reduced-motion:reduce){.pe-h1 .pe-flow{animation:none}}
  .pe-h2{font-size:clamp(24px,3.6vw,38px);font-weight:700;margin:0 0 6px}
  .pe-lead{color:var(--muted);max-width:640px;font-size:17px}
  .pe-sec-head{margin-bottom:clamp(28px,4vw,44px)}

  .pe-hero{position:relative;overflow:hidden;margin-top:-68px;padding-top:calc(clamp(52px,8vw,96px) + 92px)}
  .pe-hero-glow{position:absolute;inset:0;z-index:0;pointer-events:none;
    background:
      radial-gradient(640px 420px at 12% 8%, rgba(200,118,59,.22), transparent 70%),
      radial-gradient(700px 460px at 88% 4%, rgba(18,179,166,.2), transparent 70%)}
  .pe-hero-grid{position:absolute;inset:0;z-index:0;pointer-events:none;opacity:.5;
    background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);
    background-size:44px 44px;
    -webkit-mask-image:radial-gradient(80% 70% at 50% 20%,#000 30%,transparent 100%);
    mask-image:radial-gradient(80% 70% at 50% 20%,#000 30%,transparent 100%)}
  .pe-hero .pe-wrap{position:relative;z-index:1}

  .pe-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .pe-card{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:22px;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease}
  .pe-card::before{content:"";position:absolute;top:0;left:0;right:0;height:2px;background:var(--grad);transform:scaleX(0);transform-origin:left;transition:transform .25s ease}
  .pe-card:hover::before{transform:scaleX(1)}
  .pe-card:hover{transform:translateY(-3px);box-shadow:0 12px 24px -16px rgba(11,16,32,.25)}
  @media(prefers-reduced-motion:reduce){.pe-card{transition:none}.pe-card:hover{transform:none}.pe-card::before{transition:none}}
  .pe-card-icon{display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;
    background:rgba(18,179,166,.12);color:var(--sign);margin-bottom:14px}
  .pe-card-icon svg{width:22px;height:22px}
  .pe-card h3{margin:0 0 6px;font-size:17px;font-weight:700}
  .pe-card p{margin:0;color:var(--muted);font-size:14px}

  /* Fake-browser product mockups */
  .pe-mock{background:var(--surface);color:var(--ink);border:1px solid var(--line);border-radius:18px;overflow:hidden;
           box-shadow:0 30px 60px -30px rgba(11,16,32,.35)}
  .pe-mock-bar{display:flex;align-items:center;gap:6px;padding:10px 14px;border-bottom:1px solid var(--line);background:#F6F4EE}
  .pe-mock-bar i{width:10px;height:10px;border-radius:50%;background:#D9D4C8;display:block}
  .pe-mock-bar i:first-child{background:#F2B8B5}
  .pe-mock-bar i:nth-child(2){background:#F2DCA0}
  .pe-mock-bar i:nth-child(3){background:#B7E1CD}
  .pe-mock-url{margin-left:8px;flex:1;max-width:260px;background:#fff;border:1px solid var(--line);border-radius:999px;
               font-size:11px;color:var(--muted);padding:3px 12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .pe-mock-body{padding:18px}
  .pe-mock-row{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;font-size:12.5px}
  .pe-mock-row:nth-child(even){background:#F8F6F1}
  .pe-mock-dot{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-family:var(--display);font-weight:700;font-size:10px;color:#fff;flex-shrink:0}
  .pe-mock-chip{margin-left:auto;font-family:var(--display);font-weight:700;font-size:10px;letter-spacing:.04em;text-transform:uppercase;border-radius:999px;padding:3px 10px}
  .pe-chip-ok{background:rgba(18,179,166,.14);color:#0B7A70}
  .pe-chip-warn{background:rgba(200,118,59,.16);color:#96521F}
  .pe-mock-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
  .pe-mock-stat{background:#F8F6F1;border:1px solid var(--line);border-radius:12px;padding:10px 12px}
  .pe-mock-stat b{display:block;font-family:var(--display);font-size:17px}
  .pe-mock-stat span{font-size:10.5px;color:var(--muted)}
  .pe-mock-bars{display:flex;align-items:flex-end;gap:10px;height:110px;padding:6px 4px 0}
  .pe-mock-bars i{flex:1;border-radius:6px 6px 2px 2px;background:linear-gradient(180deg,var(--sign),#0E8F85);display:block}
  .pe-mock-bars i.alt{background:linear-gradient(180deg,var(--copper),var(--copper-deep))}
  .pe-mock-labels{display:flex;gap:10px;padding:6px 4px 0;font-size:10px;color:var(--muted)}
  .pe-mock-labels span{flex:1;text-align:center}
  .pe-bubble{max-width:78%;border-radius:14px;padding:9px 13px;font-size:12.5px;margin-bottom:8px}
  .pe-bubble-in{background:#F1EEE6;border:1px solid var(--line)}
  .pe-bubble-out{background:var(--ink);color:#fff;margin-left:auto}

  /* Feature deep-dive rows */
  .pe-feature-row{display:grid;grid-template-columns:1fr 1fr;gap:clamp(24px,5vw,64px);align-items:center;padding:clamp(28px,4vw,44px) 0}
  .pe-feature-row + .pe-feature-row{border-top:1px solid var(--line)}
  .pe-feature-row h3{font-size:clamp(20px,2.6vw,27px);margin:0 0 10px}
  .pe-feature-row p{color:var(--muted);margin:0;font-size:15.5px}
  .pe-feature-copy .pe-card-icon{margin-bottom:16px}
  @media(max-width:820px){
    .pe-feature-row{grid-template-columns:1fr}
    .pe-feature-row .pe-feature-mock{order:2}
  }

  /* Pricing */
  .pe-pricing-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));align-items:stretch}
  .pe-price-card{position:relative;display:flex;flex-direction:column;background:var(--surface);border:1px solid var(--line);
                 border-radius:22px;padding:28px 26px;transition:transform .2s ease,box-shadow .2s ease}
  .pe-price-card:hover{transform:translateY(-4px);box-shadow:0 18px 40px -22px rgba(11,16,32,.35)}
  @media(prefers-reduced-motion:reduce){.pe-price-card{transition:none}.pe-price-card:hover{transform:none}}
  .pe-price-card--hot{background:linear-gradient(180deg,var(--ink) 0%,var(--ink-2) 100%);color:#fff;border-color:transparent;
                      box-shadow:0 26px 54px -26px rgba(11,16,32,.55)}
  .pe-price-card--hot .pe-price-amount,.pe-price-card--hot h3{color:#fff}
  .pe-price-card--hot .pe-price-tagline,.pe-price-card--hot .pe-price-period{color:var(--ink-muted)}
  .pe-price-card--hot li{color:#E7EAF3}
  .pe-price-badge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);white-space:nowrap;
                  background:var(--grad);color:#fff;font-family:var(--display);font-weight:700;font-size:11px;
                  letter-spacing:.08em;text-transform:uppercase;border-radius:999px;padding:5px 14px}
  .pe-price-card h3{font-size:19px;margin:0 0 4px}
  .pe-price-tagline{color:var(--muted);font-size:13.5px;margin:0 0 18px;min-height:20px}
  .pe-price-amount{font-family:var(--display);font-weight:800;font-size:clamp(26px,3vw,34px);letter-spacing:-.02em}
  .pe-price-period{color:var(--muted);font-size:13px;margin:2px 0 18px}
  .pe-price-card ul{list-style:none;margin:0 0 22px;padding:0;display:flex;flex-direction:column;gap:9px;flex:1}
  .pe-price-card li{display:flex;gap:9px;align-items:flex-start;font-size:14px;color:var(--muted)}
  .pe-price-card li svg{width:16px;height:16px;flex-shrink:0;margin-top:3px;color:var(--sign)}

  /* FAQ */
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

  .pe-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:inherit;margin-bottom:12px}
  .pe-input:focus{border-color:var(--sign);outline:none}
  .pe-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--ink);margin:0 0 6px}
  .pe-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .pe-status{background:#E9F7F5;border:1px solid var(--sign);color:#0B1020;padding:12px 16px;margin-bottom:16px;border-radius:12px;font-size:15px}

  .pe-footer{background:var(--ink);color:#c7cdda;padding:56px 24px 28px}
  .pe-footer-inner{max-width:1120px;margin:0 auto}
  .pe-footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:8px}
  .pe-footer-brand img{height:30px;width:30px}
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
        <img src="{{ asset('images/brand/logo.svg') }}" alt="">
        <span class="pe-brand-text">
          <span class="pe-brand-name">PearlEdu</span>
          <span class="pe-brand-tagline">By VoxSign Technologies</span>
        </span>
      </a>
      <div class="pe-nav-links">
        <a href="#how-it-works">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
        <a href="#onboard">Onboard</a>
      </div>
      <div class="pe-nav-cta"><a href="{{ url('/login') }}" class="pe-btn">Login</a></div>
    </div>
  </div>
  @if(session('status'))
    <div class="pe-wrap" style="padding-top:20px;position:relative;z-index:5"><div class="pe-status">{{ session('status') }}</div></div>
  @endif
  @yield('content')
  <div class="pe-footer">
    <div class="pe-footer-inner">
      <div class="pe-footer-brand">
        <img src="{{ asset('images/brand/logo.svg') }}" alt="">
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
  </script>
</body>
</html>
