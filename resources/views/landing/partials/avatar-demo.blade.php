<section class="vx-section vx-band">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">See it in motion</div>
    <h2 class="vx-h2" style="color:#fff">The idea, previewed.</h2>
    <p class="vx-lead" style="margin:10px 0 8px">
      <span style="display:inline-block;background:var(--sign);color:#0B1020;font-family:var(--display);font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:4px 10px;border-radius:999px">Concept preview</span>
    </p>
    <p class="vx-lead" style="margin-bottom:32px">
      An early look at how a signing avatar could move — illustrative, not a verified Ugandan Sign Language rendering.
    </p>
    <div class="vx-avatar-demo" data-phrases="How are you?,What is your name?">
      <svg viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Animated avatar preview">
        <circle cx="100" cy="52" r="26" fill="none" stroke="var(--sign)" stroke-width="5"/>
        <line x1="100" y1="78" x2="100" y2="140" stroke="var(--sign)" stroke-width="6" stroke-linecap="round"/>
        <g class="vx-arm-l" style="transform-origin:100px 92px">
          <line x1="100" y1="92" x2="60" y2="130" stroke="var(--voice)" stroke-width="6" stroke-linecap="round"/>
        </g>
        <g class="vx-arm-r" style="transform-origin:100px 92px">
          <line x1="100" y1="92" x2="140" y2="130" stroke="var(--voice)" stroke-width="6" stroke-linecap="round"/>
        </g>
      </svg>
      <p class="vx-avatar-caption" style="font-family:var(--display);font-weight:700;font-size:20px;margin-top:14px">How are you?</p>
    </div>
  </div>
</section>
<style>
  .vx-avatar-demo{text-align:center}
  .vx-arm-l{animation:vx-arm-l 2.4s ease-in-out infinite}
  .vx-arm-r{animation:vx-arm-r 2.4s ease-in-out infinite}
  @keyframes vx-arm-l{0%,100%{transform:rotate(0deg)}50%{transform:rotate(-35deg)}}
  @keyframes vx-arm-r{0%,100%{transform:rotate(0deg)}50%{transform:rotate(35deg)}}
  @media(prefers-reduced-motion:reduce){.vx-arm-l,.vx-arm-r{animation:none}}
</style>
<script>
  (function(){
    var demo = document.querySelector('.vx-avatar-demo');
    if (!demo) return;
    var phrases = demo.dataset.phrases.split(',');
    var caption = demo.querySelector('.vx-avatar-caption');
    var i = 0;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    setInterval(function(){
      i = (i + 1) % phrases.length;
      caption.textContent = phrases[i];
    }, 3200);
  })();
</script>
