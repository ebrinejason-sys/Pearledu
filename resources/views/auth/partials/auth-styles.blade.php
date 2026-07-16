<style>
  .vx-auth-split{display:flex;min-height:100vh}
  .vx-auth-panel{flex:0 0 44%;max-width:480px;min-width:340px;background:var(--sidebar);color:var(--sidebar-ink);display:flex;flex-direction:column;justify-content:center;padding:48px;gap:28px}
  .vx-auth-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:20px;color:#fff}
  .vx-logo{display:block;flex-shrink:0;height:var(--vx-logo-h,36px);width:auto}
  .vx-auth-brand span b{opacity:.8}
  .vx-auth-card h1{margin:0 0 12px;font-size:26px;color:#fff}
  .vx-auth-lead{margin:0 0 18px;font-size:14px;line-height:1.55;color:rgba(255,255,255,.78)}
  .vx-auth-status{background:rgba(66,158,189,.18);border:1px solid rgba(159,231,245,.35);color:#fff;padding:10px 12px;border-radius:var(--radius);font-size:14px;margin-bottom:14px}
  .vx-auth-card label{display:block;color:var(--sidebar-ink);font-size:13px;margin:12px 0 4px}
  .vx-auth-card input{display:block;box-sizing:border-box;width:100%;padding:12px;border-radius:var(--radius);font:inherit;font-size:16px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);color:#fff}
  .vx-auth-card input::placeholder{color:rgba(255,255,255,.5)}
  .vx-auth-remember{display:flex;align-items:center;gap:8px;font-size:13px}
  .vx-auth-remember input{width:auto}
  .vx-auth-links{margin:16px 0 0;font-size:13px}
  .vx-auth-links a{color:#fff;text-decoration:underline;text-underline-offset:3px}
  .vx-auth-divider{margin:22px 0 16px;border:0;border-top:1px solid rgba(255,255,255,.14)}
  .vx-auth-card .btn{width:100%;min-height:48px;margin-top:16px;padding:12px 16px;background:var(--accent);color:var(--ink);border:0;border-radius:var(--radius);font:inherit;font-weight:700;font-size:15px;letter-spacing:.2px;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,.15),0 4px 12px rgba(0,0,0,.12);transition:background-color .15s ease,box-shadow .15s ease,transform .05s ease}
  .vx-auth-card .btn:hover{background:color-mix(in srgb,var(--accent) 88%,#fff)}
  .vx-auth-card .btn:active{transform:translateY(1px);box-shadow:0 1px 2px rgba(0,0,0,.15)}
  .vx-auth-card .btn:focus-visible{outline:2px solid #fff;outline-offset:2px}
  .vx-auth-card .btn-ghost{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.28);box-shadow:none}
  .vx-auth-card .btn-ghost:hover{background:rgba(255,255,255,.08)}
  .vx-auth-card .btn-link{display:inline;width:auto;margin:0;padding:0;background:none;border:0;box-shadow:none;color:rgba(255,255,255,.85);font:inherit;font-size:13px;font-weight:600;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
  .vx-auth-card .btn-link:hover{color:#fff}
  .vx-auth-card .err{color:#FFD3D3;font-size:13px;margin-top:6px}
  .vx-auth-card p{margin:0 0 14px;font-size:14px;line-height:1.55;color:rgba(255,255,255,.78)}
  .vx-auth-card code{display:inline-block;padding:2px 8px;border-radius:6px;background:rgba(0,0,0,.25);color:#9FE7F5;font-size:12px;word-break:break-all}
  .vx-auth-qr{display:inline-flex;align-items:center;justify-content:center;margin:4px 0 14px;padding:14px;background:#fff;border-radius:calc(var(--radius) + 4px);box-shadow:0 8px 24px rgba(0,0,0,.18)}
  .vx-auth-qr svg{display:block;width:180px;height:180px}
  .vx-auth-manual{margin:0 0 16px;font-size:13px;line-height:1.55;color:rgba(255,255,255,.72)}
  .vx-auth-alt{margin-top:18px;border-top:1px solid rgba(255,255,255,.14);padding-top:14px}
  .vx-auth-alt details{margin-top:10px}
  .vx-auth-alt summary{cursor:pointer;color:rgba(255,255,255,.9);font-size:13px;font-weight:600}
  .vx-auth-alt summary:hover{color:#fff}
  .vx-auth-codes{list-style:none;margin:0 0 18px;padding:0;display:grid;gap:8px}
  .vx-auth-codes li{padding:10px 12px;border-radius:var(--radius);background:rgba(0,0,0,.22);border:1px solid rgba(255,255,255,.12)}
  .vx-auth-codes code{background:transparent;padding:0;color:#fff;font-size:14px;letter-spacing:.04em}
  .vx-auth-stage{
    flex:1;display:flex;align-items:center;justify-content:center;padding:48px;
    background-color:var(--bg);
    background-image:none;
    position:relative;overflow:hidden;
  }
  .vx-auth-stage::before{
    content:"";position:absolute;width:640px;height:640px;border-radius:50%;
    background:var(--accent-soft);opacity:.55;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;
  }
  .vx-stage-inner{position:relative;max-width:400px;width:100%;text-align:center;animation:vx-stage-in .5s ease both}
  .vx-illustration-card{
    background:var(--surface);border-radius:calc(var(--radius) + 6px);padding:28px;
    box-shadow:0 24px 48px -16px rgba(19,68,58,.28),0 2px 6px rgba(19,68,58,.08);
    border-top:3px solid var(--accent);
  }
  .vx-login-illustration{display:block;max-width:100%;width:100%;height:auto}
  .vx-stage-copy{margin:22px 6px 0;font-size:14px;line-height:1.6;color:var(--muted)}
  .vx-stage-copy strong{display:block;margin-bottom:4px;font-size:16px;color:var(--ink)}
  @keyframes vx-stage-in{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
  @media(prefers-reduced-motion:reduce){.vx-stage-inner{animation:none}}
  @media(max-width:860px){
    .vx-auth-split{flex-direction:column;min-height:auto}
    .vx-auth-panel{flex:none;max-width:none;min-width:0;padding:28px 18px 36px}
    .vx-auth-brand{font-size:18px}
    .vx-auth-card h1{font-size:22px}
    .vx-auth-stage{display:none}
  }
</style>
