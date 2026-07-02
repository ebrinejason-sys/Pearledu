<section class="vx-section vx-band">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">See it in motion</div>
    <h2 class="vx-h2" style="color:#fff">The idea, previewed.</h2>
    <p class="vx-lead" style="margin:10px 0 8px">
      <span style="display:inline-block;background:var(--sign);color:#0B1020;font-family:var(--display);font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:4px 10px;border-radius:999px">Concept preview</span>
    </p>
    <p class="vx-lead vx-sec-head">
      An early look at how hand-shape signing could look — illustrative, not a verified Ugandan Sign Language rendering.
    </p>
    <div class="vx-avatar-demo" data-phrases="How are you?,What is your name?">
      <svg viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Hand-shape illustration">
        <g class="vx-hand vx-hand-1">
          <rect x="80" y="90" width="40" height="60" rx="18" fill="var(--sign)"/>
          <rect x="60" y="50" width="16" height="55" rx="8" fill="var(--sign)" transform="rotate(-8 68 78)"/>
          <rect x="80" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>
          <rect x="102" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>
          <rect x="124" y="45" width="16" height="60" rx="8" fill="var(--sign)" transform="rotate(8 132 75)"/>
          <rect x="55" y="95" width="30" height="15" rx="7" fill="var(--sign)" transform="rotate(-35 70 102)"/>
        </g>
        <g class="vx-hand vx-hand-2">
          <rect x="80" y="100" width="42" height="55" rx="18" fill="var(--voice)"/>
          <rect x="90" y="40" width="18" height="70" rx="9" fill="var(--voice)"/>
          <rect x="60" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>
          <rect x="118" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>
        </g>
      </svg>
      <p class="vx-avatar-caption" style="font-family:var(--display);font-weight:700;font-size:20px;margin-top:14px">How are you?</p>
    </div>
  </div>
</section>
<style>
  .vx-avatar-demo{text-align:center}
  .vx-hand{transition:opacity .5s ease}
  .vx-hand-2{opacity:0}
  .vx-avatar-demo.vx-phrase-2 .vx-hand-1{opacity:0}
  .vx-avatar-demo.vx-phrase-2 .vx-hand-2{opacity:1}
  @media(prefers-reduced-motion:reduce){.vx-hand{transition:none}}
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
      demo.classList.toggle('vx-phrase-2', i === 1);
    }, 3200);
  })();
</script>
