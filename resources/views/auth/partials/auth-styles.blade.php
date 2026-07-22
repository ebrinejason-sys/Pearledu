<style>
  .vx-auth-split{display:grid;grid-template-columns:1fr 1fr;min-height:100vh;min-height:100dvh}
  .vx-auth-panel{
    background:var(--sidebar);color:var(--sidebar-ink);
    display:flex;flex-direction:column;justify-content:center;align-items:center;
    padding:48px clamp(24px,4vw,56px);gap:22px;
  }
  .vx-auth-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:18px;color:var(--on-brand);text-decoration:none;width:100%;max-width:360px}
  .vx-logo{display:block;flex-shrink:0;height:var(--vx-logo-h,32px);width:auto}
  .vx-auth-brand span b{opacity:.8}
  .vx-auth-card{width:100%;max-width:360px}
  .vx-auth-card h1{margin:0 0 12px;font-size:24px;color:var(--on-brand);font-family:var(--font-display)}
  .vx-auth-lead{margin:0 0 18px;font-size:14px;line-height:1.55;color:color-mix(in srgb, var(--on-brand) 78%, transparent)}
  .vx-auth-status{background:color-mix(in srgb, var(--focus) 18%, transparent);border:1px solid color-mix(in srgb, var(--sidebar-ink) 35%, transparent);color:var(--on-brand);padding:10px 12px;border-radius:var(--radius);font-size:14px;margin-bottom:14px}
  .vx-auth-card label{display:block;color:var(--sidebar-ink);font-size:13px;margin:12px 0 4px}
  .vx-auth-card input{display:block;box-sizing:border-box;width:100%;padding:12px;border-radius:var(--radius);font:inherit;font-size:16px;background:var(--sidebar-hover);border:1px solid color-mix(in srgb, var(--on-brand) 18%, transparent);color:var(--on-brand)}
  .vx-auth-card input::placeholder{color:color-mix(in srgb, var(--on-brand) 50%, transparent)}
  .vx-auth-remember{display:flex;align-items:center;gap:8px;font-size:13px}
  .vx-auth-remember input{width:auto}
  .vx-auth-links{margin:16px 0 0;font-size:13px}
  .vx-auth-links a{color:var(--on-brand);text-decoration:underline;text-underline-offset:3px}
  .vx-auth-divider{margin:22px 0 16px;border:0;border-top:1px solid color-mix(in srgb, var(--on-brand) 14%, transparent)}
  .vx-auth-card .btn{width:100%;min-height:48px;margin-top:16px;padding:12px 16px;background:var(--accent);color:var(--accent-ink);border:0;border-radius:var(--radius);font:inherit;font-weight:700;font-size:15px;letter-spacing:.2px;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,.15),0 4px 12px rgba(0,0,0,.12);transition:background-color .15s ease,box-shadow .15s ease,transform .05s ease}
  .vx-auth-card .btn:hover{background:color-mix(in srgb,var(--accent) 88%,#fff)}
  .vx-auth-card .btn:active{transform:translateY(1px);box-shadow:0 1px 2px rgba(0,0,0,.15)}
  .vx-auth-card .btn:focus-visible{outline:2px solid var(--on-brand);outline-offset:2px}
  .vx-auth-card .btn-ghost{background:transparent;color:var(--on-brand);border:1.5px solid color-mix(in srgb, var(--on-brand) 28%, transparent);box-shadow:none}
  .vx-auth-card .btn-ghost:hover{background:var(--sidebar-hover)}
  .vx-auth-card .btn-link{display:inline;width:auto;margin:0;padding:0;background:none;border:0;box-shadow:none;color:color-mix(in srgb, var(--on-brand) 85%, transparent);font:inherit;font-size:13px;font-weight:600;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
  .vx-auth-card .btn-link:hover{color:var(--on-brand)}
  .vx-auth-card .err{color:#FFD3D3;font-size:13px;margin-top:6px}
  .vx-auth-card p{margin:0 0 14px;font-size:14px;line-height:1.55;color:color-mix(in srgb, var(--on-brand) 78%, transparent)}
  .vx-auth-card code{display:inline-block;padding:2px 8px;border-radius:6px;background:rgba(0,0,0,.25);color:var(--sidebar-ink);font-size:12px;word-break:break-all}
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
    display:flex;align-items:center;justify-content:center;padding:clamp(24px,5vw,56px);
    background-color:var(--bg);
    position:relative;overflow:hidden;
  }
  .vx-auth-stage::before{
    content:"";position:absolute;width:min(72vw,720px);height:min(72vw,720px);border-radius:50%;
    background:var(--accent-soft);opacity:.5;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;
  }
  .vx-stage-inner{
    position:relative;z-index:1;width:100%;max-width:560px;
    display:flex;flex-direction:column;align-items:center;text-align:center;
    animation:vx-stage-in .5s ease both;
  }
  /* Free illustration — no card chrome */
  .vx-login-illustration{
    display:block;width:100%;max-width:520px;height:auto;
    filter:drop-shadow(0 18px 40px rgba(19,68,58,.18));
  }
  .vx-stage-copy{margin:20px 8px 0;font-size:14px;line-height:1.6;color:var(--muted);max-width:36ch}
  .vx-stage-copy strong{display:block;margin-bottom:4px;font-size:17px;color:var(--ink)}
  @keyframes vx-stage-in{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
  @media(prefers-reduced-motion:reduce){.vx-stage-inner{animation:none}}

  /* Tablet / phone: form first, compact panel, illustration still visible below */
  @media(max-width:860px){
    .vx-auth-split{
      grid-template-columns:1fr;
      grid-template-rows:auto auto;
      min-height:auto;
    }
    .vx-auth-panel{
      padding:22px 18px 28px;
      justify-content:flex-start;
      min-height:0;
    }
    .vx-auth-card{max-width:none}
    .vx-auth-brand{font-size:17px}
    .vx-auth-card h1{font-size:22px;margin-bottom:8px}
    .vx-auth-card label{margin-top:10px}
    .vx-auth-card .btn{margin-top:14px}
    .vx-auth-stage{
      display:flex;
      padding:28px 18px 36px;
      min-height:0;
    }
    .vx-auth-stage::before{
      width:min(120vw,420px);height:min(120vw,420px);opacity:.4;
    }
    .vx-stage-inner{max-width:340px}
    .vx-login-illustration{max-width:280px}
    .vx-stage-copy{margin-top:14px;font-size:13px}
    .vx-stage-copy strong{font-size:15px}
  }

  @media(max-width:420px){
    .vx-auth-panel{padding:18px 16px 22px}
    .vx-auth-stage{padding:20px 16px 28px}
    .vx-login-illustration{max-width:240px}
  }
</style>
