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
    --ink:#0B1020; --paper:#FBFAF7; --surface:#FFFFFF;
    --copper:#B5652F; --sign:#12B3A6; --muted:#5D6473; --line:#E7E4DC;
    --grad:linear-gradient(100deg,var(--copper),var(--sign));
    --display:'Google Sans',system-ui,sans-serif;
    --body:'Satoshi',system-ui,sans-serif;
  }
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:var(--body);background:var(--paper);color:var(--ink);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3{font-family:var(--display);line-height:1.1;letter-spacing:-.02em;margin:0}
  a{color:inherit;text-decoration:none}
  .pe-wrap{max-width:1080px;margin:0 auto;padding:0 24px}
  .pe-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  .pe-nav{position:sticky;top:0;z-index:50;display:flex;align-items:center;gap:12px;padding:16px 24px;
          background:rgba(251,250,247,.9);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
  .pe-brand{display:flex;align-items:center;gap:10px}
  .pe-brand img{height:34px;width:34px}
  .pe-brand-text{display:flex;flex-direction:column;line-height:1.15}
  .pe-brand-name{font-family:var(--display);font-weight:700;font-size:17px}
  .pe-brand-tagline{font-size:11.5px;color:var(--muted)}
  .pe-nav-cta{margin-left:auto}

  .pe-btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--ink);color:#fff;border:1.5px solid var(--ink);border-radius:999px;padding:12px 22px;cursor:pointer;
          transition:transform .15s ease,box-shadow .2s ease}
  .pe-btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px rgba(11,16,32,.5)}
  .pe-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
                background:transparent;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:12px 22px;cursor:pointer}
  .pe-btn-ghost:hover{border-color:var(--ink)}

  .pe-section{padding:clamp(48px,8vw,80px) 0;border-bottom:1px solid var(--line)}
  .pe-section:last-of-type{border-bottom:0}
  .pe-eyebrow{display:inline-block;font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--copper);font-weight:700;margin-bottom:14px;text-transform:uppercase;background:rgba(181,101,47,.1);border:1px solid rgba(181,101,47,.25);border-radius:999px;padding:5px 14px}
  .pe-h1{font-size:clamp(30px,5vw,50px);font-weight:800;line-height:1.06;max-width:680px;margin:0 0 16px}
  .pe-h1 .pe-flow{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
  .pe-h2{font-size:clamp(22px,3.2vw,32px);font-weight:700;margin:0 0 6px}
  .pe-lead{color:var(--muted);max-width:600px;font-size:16.5px}

  .pe-hero{position:relative}
  .pe-hero-glow{position:absolute;inset:-10% -10% auto -10%;height:420px;z-index:0;pointer-events:none;
    background:radial-gradient(480px 300px at 15% 15%, rgba(181,101,47,.14), transparent 70%),
               radial-gradient(480px 300px at 85% 10%, rgba(18,179,166,.14), transparent 70%)}
  .pe-hero .pe-wrap{position:relative;z-index:1}

  .pe-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .pe-card{background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:22px}
  .pe-card-icon{display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;
    background:rgba(18,179,166,.12);color:var(--sign);margin-bottom:14px}
  .pe-card-icon svg{width:22px;height:22px}
  .pe-card h3{margin:0 0 6px;font-size:17px;font-weight:700}
  .pe-card p{margin:0;color:var(--muted);font-size:14px}

  .pe-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:inherit;margin-bottom:12px}
  .pe-input:focus{border-color:var(--sign);outline:none}
  .pe-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--ink);margin:0 0 6px}
  .pe-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .pe-status{background:#E9F7F5;border:1px solid var(--sign);color:#0B1020;padding:12px 16px;margin-bottom:16px;border-radius:12px;font-size:15px}

  .pe-footer{background:var(--ink);color:#c7cdda;padding:32px 24px;font-size:13px;text-align:center}
  .pe-footer a{color:#c7cdda}.pe-footer a:hover{color:#fff}

  @media(max-width:640px){
    .pe-nav-cta{display:none}
  }
</style>
@yield('head')
</head>
<body>
  <div class="pe-nav">
    <a href="{{ url('/') }}" class="pe-brand" aria-label="PearlEdu home">
      <img src="{{ asset('images/brand/logo.svg') }}" alt="">
      <span class="pe-brand-text">
        <span class="pe-brand-name">PearlEdu</span>
        <span class="pe-brand-tagline">By VoxSign Technologies</span>
      </span>
    </a>
    <div class="pe-nav-cta"><a href="{{ url('/login') }}" class="pe-btn">Login</a></div>
  </div>
  @if(session('status'))
    <div class="pe-wrap" style="padding-top:20px"><div class="pe-status">{{ session('status') }}</div></div>
  @endif
  @yield('content')
  <div class="pe-footer">
    &copy; {{ date('Y') }} PearlEdu, by VoxSign Technologies &middot; +256 770 680769 &middot; voxsign3@gmail.com
  </div>
</body>
</html>
