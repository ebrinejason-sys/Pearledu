<section class="vx-section vx-hero vx-band">
  <div class="vx-wrap vx-reveal">
    <div class="vx-hero-copy">
      <div class="vx-eyebrow">AI &middot; Inclusion Technology</div>
      <h1 class="vx-h1">Technology built to <span class="vx-flow">include everyone.</span></h1>
      <p class="vx-lead">
        VoxSign is two things working toward one goal: <strong>VoxSign Accessibility</strong>, AI
        tools that give Deaf, hard-of-hearing, and speech-impaired people a voice in the room, and
        <strong>VoxSign Institutions</strong>, a school management platform for institutions.
      </p>
      <p class="vx-cta-row" style="margin-top:28px">
        <a href="#contact" class="vx-btn-grad">Talk to us</a>
        <a href="#accessibility" class="vx-btn-ghost">See both divisions ↓</a>
      </p>
    </div>
  </div>
  @unless(!empty($scrollAvatar))
  <div class="vx-hero-avatar-wrap">
    <div class="vx-hero-avatar-stage" aria-hidden="true"></div>
    <div id="vx-hero-avatar-3d" class="vx-hero-avatar-3d" role="img" aria-label="Interactive 3D figure. Drag horizontally to rotate."></div>
    <p class="vx-hero-avatar-hint" aria-hidden="true">Drag to rotate</p>
  </div>
  <p class="vx-sr-only">Decorative full-length 3D figure of a person; drag horizontally to rotate it.</p>
  @endunless
</section>
<style>
  .vx-hero{min-height:clamp(520px,60vw,720px)}
  .vx-hero .vx-wrap{position:relative;z-index:2;pointer-events:none}
  .vx-hero-copy{max-width:620px;pointer-events:auto}
  .vx-hero-copy strong{color:#fff}
  .vx-hero-avatar-wrap{position:absolute;right:0;top:0;bottom:0;width:min(520px,48vw);z-index:3;
    display:flex;flex-direction:column;align-items:center;justify-content:flex-end;
    pointer-events:auto;cursor:grab;touch-action:none}
  .vx-hero-avatar-wrap:active{cursor:grabbing}
  .vx-hero-avatar-stage{position:absolute;left:50%;bottom:8%;transform:translateX(-50%);width:78%;aspect-ratio:1;
    border-radius:50%;pointer-events:none;
    background:radial-gradient(circle,rgba(159,231,245,.28) 0%,rgba(66,158,189,.12) 55%,transparent 72%)}
  .vx-hero-avatar-3d{position:relative;width:100%;height:100%;z-index:1}
  .vx-hero-avatar-3d canvas{display:block;width:100%!important;height:100%!important;touch-action:none}
  .vx-avatar-loading{position:absolute;inset:0;z-index:4;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;pointer-events:none}
  .vx-avatar-loading-silhouette{width:42%;aspect-ratio:1/2.35;border-radius:48% 48% 40% 40%;
    background:linear-gradient(180deg,rgba(159,231,245,.35),rgba(66,158,189,.18));
    animation:vx-avatar-pulse 1.2s ease-in-out infinite}
  .vx-avatar-loading-meta{width:min(220px,70%);text-align:center}
  .vx-avatar-loading-bar{height:4px;border-radius:999px;background:rgba(255,255,255,.18);overflow:hidden}
  .vx-avatar-loading-bar > i{display:block;height:100%;width:12%;border-radius:inherit;background:var(--cyan,#9FE7F5);transition:width .2s ease}
  .vx-avatar-loading-text{margin:10px 0 0;font-size:12px;letter-spacing:.04em;color:rgba(255,255,255,.82);font-family:var(--display)}
  @keyframes vx-avatar-pulse{0%,100%{opacity:.55;transform:scale(.98)}50%{opacity:1;transform:scale(1)}}
  @media(prefers-reduced-motion:reduce){.vx-avatar-loading-silhouette{animation:none}}
  .vx-hero-avatar-hint{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);z-index:2;
    margin:0;padding:6px 12px;border-radius:999px;font-size:12px;font-family:var(--display);
    letter-spacing:.04em;color:rgba(255,255,255,.85);background:rgba(5,63,92,.45);
    border:1px solid rgba(159,231,245,.35);pointer-events:none;opacity:.9;
    transition:opacity .4s ease}
  .vx-hero-avatar-wrap.is-dragging .vx-hero-avatar-hint,
  .vx-hero-avatar-wrap.has-interacted .vx-hero-avatar-hint{opacity:0}
  @media(max-width:860px){
    .vx-hero{min-height:0}
    .vx-hero-avatar-wrap{display:none}
  }
  @media(prefers-reduced-motion:reduce){
    .vx-hero-avatar-hint{display:none}
  }
</style>
@unless(!empty($scrollAvatar))
<script type="module">
  import { mountAvatar } from '/js/vx-avatar-loader.js?v=5';
  if (!window.matchMedia('(max-width:860px)').matches) {
    mountAvatar({
      container: 'vx-hero-avatar-3d',
      mode: 'idle',
      frame: 'full',
      interactive: true,
      autoRotate: true,
      width: 480,
      height: 680,
    });
  }
</script>
@endunless
