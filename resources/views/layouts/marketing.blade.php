<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'VoxSign — Speak the Future. See It Signed.')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#0B1020; --paper:#FBFAF7; --surface:#FFFFFF;
    --voice:#FF6A3D; --sign:#12B3A6; --muted:#5D6473; --line:#E7E4DC;
    --grad:linear-gradient(100deg,var(--voice),var(--sign));
    --display:'Bricolage Grotesque',system-ui,sans-serif;
    --body:'Atkinson Hyperlegible',system-ui,sans-serif;
  }
  *{box-sizing:border-box} html,body{margin:0}
  html{scroll-behavior:smooth}
  @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  body{font-family:var(--body);background:var(--paper);color:var(--ink);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3{font-family:var(--display);line-height:1.08;letter-spacing:-.02em;margin:0}
  a{color:inherit;text-decoration:none}
  .vx-wrap{max-width:1120px;margin:0 auto;padding:0 24px}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  .vx-nav{position:sticky;top:0;z-index:50;display:flex;align-items:center;gap:20px;padding:16px 24px;
          background:rgba(251,250,247,.86);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
  .vx-nav img{height:28px;display:block}
  .vx-nav-links{margin-left:14px;display:flex;gap:22px;font-size:15px;color:var(--muted);flex-wrap:wrap}
  .vx-nav-links a:hover{color:var(--ink)}
  .vx-nav-cta{margin-left:auto}

  .vx-section{padding:72px 0;border-bottom:1px solid var(--line)}
  .vx-section:last-of-type{border-bottom:0}
  .vx-band{background:var(--ink);color:#fff}
  .vx-band .vx-eyebrow{color:var(--sign)}
  .vx-band .vx-lead{color:#aeb4c2}

  .vx-eyebrow{font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--voice);font-weight:700;margin-bottom:14px;text-transform:uppercase}
  .vx-h1{font-size:clamp(32px,5.5vw,56px);font-weight:800;line-height:1.05;max-width:680px;margin:0 0 16px}
  .vx-h1 .vx-flow{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
  .vx-h2{font-size:clamp(24px,3.6vw,36px);font-weight:700;margin:0 0 6px}
  .vx-lead{color:var(--muted);max-width:620px;font-size:17px}

  .vx-btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--ink);color:#fff;border:1.5px solid var(--ink);border-radius:999px;padding:12px 22px;cursor:pointer;
          transition:transform .15s ease,box-shadow .2s ease}
  .vx-btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px rgba(11,16,32,.5)}
  .vx-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
                background:transparent;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:12px 22px;cursor:pointer}
  .vx-btn-ghost:hover{border-color:var(--ink)}

  .vx-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .vx-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px}
  .vx-card img{width:100%;aspect-ratio:1;object-fit:cover;margin-bottom:12px;border-radius:12px}
  .vx-card h3{margin:0 0 6px;font-size:18px;font-weight:700}
  .vx-card p{margin:0;color:var(--muted);font-size:14px}

  .vx-steps{display:flex;gap:22px;flex-wrap:wrap}
  .vx-step{flex:1;min-width:180px}
  .vx-step-n{font-family:var(--display);font-size:24px;font-weight:800;color:var(--voice)}
  .vx-step h4{margin:8px 0;font-size:17px}
  .vx-step p{margin:0;color:var(--muted);font-size:14px}

  .vx-partner-text{color:var(--muted);font-size:13px;border:1px dashed var(--line);padding:10px 14px;border-radius:10px}
  .vx-quote{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:16px}
  .vx-quote p{margin:0 0 12px;font-size:16px}
  .vx-quote cite{color:var(--muted);font-size:13px;font-style:normal}

  .vx-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:inherit;margin-bottom:12px}
  .vx-input:focus{border-color:var(--sign);outline:none}
  .vx-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--ink);margin:0 0 6px}
  .vx-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .vx-status{background:#E9F7F5;border:1px solid var(--sign);color:#0B1020;padding:12px 16px;margin-bottom:16px;border-radius:12px;font-size:15px}

  .vx-footer{background:var(--ink);color:#c7cdda;padding:32px 24px;font-size:13px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px}
  .vx-footer a{color:#c7cdda}.vx-footer a:hover{color:#fff}
  @media(max-width:640px){.vx-footer{flex-direction:column}}

  .vx-reveal{opacity:0;transform:translateY(14px);transition:opacity .6s ease,transform .6s ease}
  .vx-reveal.in{opacity:1;transform:none}
  @media(prefers-reduced-motion:reduce){.vx-reveal{opacity:1;transform:none;transition:none}}
</style>
</head>
<body>
  <div class="vx-nav">
    <a href="{{ url('/') }}"><img src="{{ asset('images/voxsign/voxsign-logo.png') }}" alt="VoxSign"></a>
    <div class="vx-nav-links">
      <a href="#pearledu">Institutions</a>
      <a href="#accessibility">Accessibility</a>
      <a href="#team">Team</a>
      <a href="#contact">Contact</a>
    </div>
    <div class="vx-nav-cta"><a href="#contact" class="vx-btn">Talk to us</a></div>
  </div>
  @if(session('status'))
    <div class="vx-wrap" style="padding-top:20px"><div class="vx-status">{{ session('status') }}</div></div>
  @endif
  @yield('content')
  <div class="vx-footer">
    <span>&copy; {{ date('Y') }} VoxSign, Uganda</span>
    <span>+256 770 680769 &middot; voxsign3@gmail.com</span>
    <a href="https://pearledu.{{ config('tenancy.base_domain') }}">PearlEdu — school management →</a>
  </div>
  <script>
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, {threshold: .12});
    document.querySelectorAll('.vx-reveal').forEach(function(el){ io.observe(el); });
  </script>
</body>
</html>
