<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'VoxSign — Speak the Future. See It Signed.')</title>
<style>
  :root{
    --vx-bg:#0A0A0A; --vx-surface:#141414; --vx-ink:#FFFFFF; --vx-muted:#B3B3B3;
    --vx-accent:#FFD400; --vx-accent-ink:#0A0A0A; --vx-line:#2A2A2A; --vx-radius:4px;
  }
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:system-ui,'Segoe UI',sans-serif;background:var(--vx-bg);color:var(--vx-ink);line-height:1.6}
  a{color:inherit;text-decoration:none}
  .vx-wrap{max-width:1100px;margin:0 auto;padding:0 28px}
  .vx-nav{display:flex;align-items:center;padding:18px 28px;border-bottom:2px solid var(--vx-accent)}
  .vx-nav img{height:28px;display:block}
  .vx-nav-links{margin-left:auto;display:flex;gap:20px;font-size:14px;color:var(--vx-muted);flex-wrap:wrap}
  .vx-nav-links a:hover{color:var(--vx-ink)}
  .vx-section{padding:48px 0;border-bottom:1px solid var(--vx-line)}
  .vx-eyebrow{font-size:12px;letter-spacing:.15em;color:var(--vx-accent);font-weight:700;margin-bottom:14px;text-transform:uppercase}
  .vx-h1{font-size:clamp(28px,5vw,40px);font-weight:900;line-height:1.15;max-width:620px;margin:0 0 14px}
  .vx-h2{font-size:22px;font-weight:900;margin:0 0 4px}
  .vx-lead{color:var(--vx-muted);max-width:560px;font-size:16px}
  .vx-btn{display:inline-block;background:var(--vx-accent);color:var(--vx-accent-ink);border:0;border-radius:var(--vx-radius);padding:12px 22px;font-weight:800;font-size:14px;cursor:pointer}
  .vx-btn-ghost{color:var(--vx-muted);border-bottom:1px solid var(--vx-line);padding-bottom:2px;font-size:14px}
  .vx-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
  .vx-card{background:var(--vx-surface);border:1px solid var(--vx-line);padding:16px}
  .vx-card img{width:100%;aspect-ratio:1;object-fit:cover;margin-bottom:10px;border-radius:var(--vx-radius)}
  .vx-card h3{margin:0 0 4px;font-size:15px;font-weight:800}
  .vx-card p{margin:0;color:var(--vx-muted);font-size:13px}
  .vx-steps{display:flex;gap:20px;flex-wrap:wrap}
  .vx-step{flex:1;min-width:160px}
  .vx-step-n{font-size:22px;font-weight:900;color:var(--vx-accent)}
  .vx-step h4{margin:6px 0;font-size:15px}
  .vx-step p{margin:0;color:var(--vx-muted);font-size:13px}
  .vx-partners{display:flex;gap:28px;flex-wrap:wrap;align-items:center}
  .vx-partners img{max-height:48px;filter:grayscale(1) brightness(2);opacity:.85}
  .vx-partner-text{color:var(--vx-muted);font-size:13px;border:1px dashed var(--vx-line);padding:10px 14px}
  .vx-quote{background:var(--vx-surface);border:1px solid var(--vx-line);padding:18px;margin-bottom:14px}
  .vx-quote p{margin:0 0 10px;font-size:14px}
  .vx-quote cite{color:var(--vx-muted);font-size:13px;font-style:normal}
  table.vx-table{width:100%;border-collapse:collapse;font-size:14px}
  table.vx-table th,table.vx-table td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--vx-line)}
  table.vx-table th{color:var(--vx-muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em}
  .vx-input{width:100%;padding:10px;border:1px solid var(--vx-line);border-radius:var(--vx-radius);background:var(--vx-surface);color:var(--vx-ink);font:inherit;margin-bottom:10px}
  .vx-label{display:block;font-size:13px;color:var(--vx-muted);margin:0 0 4px}
  .vx-err{color:#FF6B6B;font-size:13px;margin:-6px 0 10px}
  .vx-status{background:#1A2E1A;border:1px solid #2E5E2E;padding:10px 14px;margin-bottom:14px;font-size:14px}
  .vx-footer{padding:22px 28px;color:var(--vx-muted);font-size:12px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
  @media(max-width:640px){.vx-footer{flex-direction:column}}
</style>
</head>
<body>
  <div class="vx-nav">
    <a href="{{ url('/') }}"><img src="{{ asset('images/voxsign/voxsign-logo.png') }}" alt="VoxSign"></a>
    <div class="vx-nav-links">
      <a href="#how-it-works">How it works</a>
      <a href="#team">Team</a>
      <a href="#partners">Partners</a>
      <a href="#pricing">Pricing</a>
      <a href="#contact">Contact</a>
    </div>
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
</body>
</html>
