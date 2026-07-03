<section class="vx-section vx-hero" style="padding-top:56px">
  <div class="vx-hero-glow" aria-hidden="true"></div>
  <div class="vx-wrap vx-reveal">
    <div class="vx-hero-copy">
      <div class="vx-eyebrow">AI &middot; Inclusion Technology</div>
      <h1 class="vx-h1">Technology built to <span class="vx-flow">include everyone.</span></h1>
      <p class="vx-lead">
        VoxSign is two things working toward one goal: <strong>VoxSign Accessibility</strong>, AI
        tools that give Deaf, hard-of-hearing, and speech-impaired people a voice in the room, and
        <strong>VoxSign Institutions</strong>, a school management platform for institutions.
      </p>
      <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="#contact" class="vx-btn">Talk to us</a>
        <a href="#accessibility" class="vx-btn-ghost">See both divisions ↓</a>
      </p>
    </div>
  </div>
  <div class="vx-hero-avatar-wrap" aria-hidden="true">
    <div id="vx-hero-avatar-3d" class="vx-hero-avatar-3d"></div>
  </div>
  <p class="vx-sr-only">Decorative full-length 3D figure of a person, shown for visual interest; drag horizontally to rotate it.</p>
</section>
<style>
  .vx-hero{min-height:clamp(480px,58vw,680px)}
  .vx-hero .vx-wrap{position:relative;z-index:2}
  .vx-hero-copy{max-width:600px}
  .vx-hero-avatar-wrap{position:absolute;right:0;top:0;bottom:0;width:min(460px,44vw);z-index:1}
  .vx-hero-avatar-3d{width:100%;height:100%}
  .vx-hero-avatar-3d canvas{display:block;touch-action:pan-y}
  @media(max-width:860px){
    .vx-hero{min-height:0}
    .vx-hero-avatar-wrap{display:none}
  }
</style>
<script type="module">
  import { mountAvatar } from '/js/vx-avatar-loader.js';
  if (!window.matchMedia('(max-width:860px)').matches) {
    mountAvatar({
      container: 'vx-hero-avatar-3d',
      mode: 'idle',
      frame: 'full',
      interactive: true,
      width: 420,
      height: 640,
      colorVars: ['--avatar-skin', '--avatar-skin-joint'],
      colorFallbacks: ['#C68863', '#A8714F']
    });
  }
</script>
