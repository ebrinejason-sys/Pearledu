{{-- Scroll-guided avatar experiment. Roll back: set $vxScrollAvatar = false in home.blade.php --}}
<div id="vx-scroll-avatar" class="vx-scroll-avatar" aria-hidden="false">
  <div id="vx-scroll-avatar-stage" class="vx-scroll-avatar-stage">
    <div class="vx-scroll-avatar-glow" aria-hidden="true"></div>
    <div id="vx-hero-avatar-3d" class="vx-scroll-avatar-canvas" role="img" aria-label="Interactive guide figure. Drag to rotate. Scroll to move through the page."></div>
    <p class="vx-scroll-avatar-hint" aria-hidden="true">Scroll to explore · Drag to turn</p>
  </div>
</div>
<p class="vx-sr-only">A decorative 3D guide figure moves across sections as you scroll. Drag horizontally to rotate it.</p>
<style>
  .vx-scroll-avatar{position:fixed;inset:0;z-index:24;pointer-events:none}
  .vx-scroll-avatar-stage{
    position:absolute;left:78%;top:6%;
    width:min(440px,42vw);height:min(660px,82vh);
    transform:translate(-50%,0) scale(1);
    transform-origin:center top;
    pointer-events:auto;cursor:grab;touch-action:none;
    will-change:left,top,transform,opacity;
  }
  .vx-scroll-avatar-stage:active{cursor:grabbing}
  .vx-scroll-avatar-glow{
    position:absolute;left:50%;bottom:6%;transform:translateX(-50%);
    width:72%;aspect-ratio:1;border-radius:50%;pointer-events:none;
    background:radial-gradient(circle,rgba(159,231,245,.26) 0%,rgba(66,158,189,.1) 55%,transparent 72%);
  }
  .vx-scroll-avatar-canvas{position:relative;width:100%;height:100%;z-index:1}
  .vx-scroll-avatar-canvas canvas{display:block;width:100%!important;height:100%!important;touch-action:none}
  .vx-scroll-avatar-hint{
    position:absolute;left:50%;bottom:10px;transform:translateX(-50%);z-index:2;
    margin:0;padding:6px 12px;border-radius:999px;font-size:12px;font-family:var(--display);
    letter-spacing:.03em;white-space:nowrap;
    color:rgba(255,255,255,.9);background:rgba(5,63,92,.5);
    border:1px solid rgba(159,231,245,.35);pointer-events:none;
    transition:opacity .35s ease;
  }
  .vx-scroll-avatar-stage.has-interacted .vx-scroll-avatar-hint,
  .vx-scroll-avatar-stage.is-dragging .vx-scroll-avatar-hint{opacity:0}
  .vx-avatar-loading{position:absolute;inset:0;z-index:4;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;pointer-events:none}
  .vx-avatar-loading-silhouette{width:42%;aspect-ratio:1/2.35;border-radius:48% 48% 40% 40%;
    background:linear-gradient(180deg,rgba(159,231,245,.35),rgba(66,158,189,.18));
    animation:vx-avatar-pulse 1.2s ease-in-out infinite}
  .vx-avatar-loading-meta{width:min(220px,70%);text-align:center}
  .vx-avatar-loading-bar{height:4px;border-radius:999px;background:rgba(255,255,255,.18);overflow:hidden}
  .vx-avatar-loading-bar > i{display:block;height:100%;width:12%;border-radius:inherit;background:var(--cyan,#9FE7F5);transition:width .2s ease}
  .vx-avatar-loading-text{margin:10px 0 0;font-size:12px;letter-spacing:.04em;color:rgba(5,63,92,.75);font-family:var(--display)}
  html[data-theme="dark"] .vx-avatar-loading-text{color:rgba(255,255,255,.82)}
  @keyframes vx-avatar-pulse{0%,100%{opacity:.55;transform:scale(.98)}50%{opacity:1;transform:scale(1)}}
  @media (max-width:860px){
    .vx-scroll-avatar{display:none}
  }
  .vx-scroll-avatar-stage.is-static-hero{
    left:78%!important;top:6%!important;transform:translate(-50%,0) scale(1)!important;opacity:1!important;
  }
</style>
<script type="module">
  import { mountAvatar } from '/js/vx-avatar-loader.js?v=6';
  import { startAvatarScrollGuide } from '/js/vx-avatar-scroll-guide.js?v=2';

  if (!window.matchMedia('(max-width:860px)').matches && window.__VX_SCROLL_AVATAR__ !== false) {
    mountAvatar({
      container: 'vx-hero-avatar-3d',
      mode: 'idle',
      frame: 'full',
      interactive: true,
      autoRotate: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
      width: 440,
      height: 660,
    });
    startAvatarScrollGuide({ stage: '#vx-scroll-avatar-stage' });
  }
</script>
