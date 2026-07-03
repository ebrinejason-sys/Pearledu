<section class="vx-hero vx-section" style="padding-top:56px">
  <div class="vx-hero-glow" aria-hidden="true"></div>

  <div class="vx-wrap vx-reveal vx-hero-grid">
    {{-- ---------- copy ---------- --}}
    <div>
      <div class="vx-eyebrow">AI &middot; Inclusion Technology</div>
      <h1 class="vx-h1">Technology built to <span class="vx-flow">include everyone.</span></h1>
      <p class="vx-lead">
        VoxSign is two things working toward one goal: <strong>PearlEdu</strong>, a school
        management platform for institutions, and <strong>VoxSign Accessibility</strong>, AI
        tools that give Deaf, hard-of-hearing, and speech-impaired people a voice in the room.
      </p>
      <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="#contact" class="vx-btn">Talk to us</a>
        <a href="#pearledu" class="vx-btn-ghost">See both divisions ↓</a>
      </p>
    </div>

    {{-- ---------- scene ---------- --}}
    <div
      class="vx-hero-scene"
      id="vxHeroScene"
      role="button"
      tabindex="0"
      aria-label="Preview a signed phrase"
    >
      <div class="vx-hero-floor" aria-hidden="true"></div>

      <span class="vx-hero-hint" id="vxHeroHint">Tap to preview a signed phrase</span>

      <div class="vx-hero-hand-wrap" id="vxHeroHandWrap">
        <svg viewBox="0 0 400 420" class="vx-hero-hand" id="vxHeroHand">
          <defs>
            <radialGradient id="vxPalmGrad" cx="32%" cy="26%" r="85%">
              <stop offset="0%" stop-color="#7FE0D6"/>
              <stop offset="55%" stop-color="#12B3A6"/>
              <stop offset="100%" stop-color="#0C7F76"/>
            </radialGradient>
            <radialGradient id="vxFingerGrad" cx="30%" cy="18%" r="95%">
              <stop offset="0%" stop-color="#FFB699"/>
              <stop offset="55%" stop-color="#FF6A3D"/>
              <stop offset="100%" stop-color="#C94C25"/>
            </radialGradient>
          </defs>

          <g class="vx-palm-group">
            <rect x="130" y="260" width="140" height="140" rx="46" fill="url(#vxPalmGrad)"/>
            <ellipse cx="200" cy="200" rx="150" ry="130" fill="url(#vxPalmGrad)"/>
          </g>

          <g class="vx-finger" style="transform-origin:105px 84px; transform:rotate(-14deg); --rest:-14deg">
            <rect x="78" y="0" width="54" height="168" rx="27" fill="url(#vxFingerGrad)"/>
          </g>
          <g class="vx-finger" style="transform-origin:173px 58px; transform:rotate(-5deg); --rest:-5deg">
            <rect x="144" y="-40" width="58" height="196" rx="29" fill="url(#vxFingerGrad)"/>
          </g>
          <g class="vx-finger" style="transform-origin:239px 54px; transform:rotate(4deg); --rest:4deg">
            <rect x="210" y="-48" width="58" height="204" rx="29" fill="url(#vxFingerGrad)"/>
          </g>
          <g class="vx-finger" style="transform-origin:301px 63px; transform:rotate(15deg); --rest:15deg">
            <rect x="274" y="-28" width="54" height="182" rx="27" fill="url(#vxFingerGrad)"/>
          </g>
          <g class="vx-finger" style="transform-origin:78px 216px; transform:rotate(-46deg); --rest:-46deg">
            <rect x="52" y="152" width="52" height="128" rx="26" fill="url(#vxFingerGrad)"/>
          </g>
        </svg>
      </div>

      <div class="vx-hero-card vx-hero-card--listening">
        <span class="vx-hero-card-icon">
          <span class="vx-hero-bars" id="vxHeroBars">
            <span style="height:8px"></span>
            <span style="height:18px"></span>
            <span style="height:12px"></span>
            <span style="height:20px"></span>
          </span>
        </span>
        <span>
          <span class="vx-hero-card-title" id="vxHeroListenTitle">Listening</span>
          <span class="vx-hero-card-sub">Transcribing your voice</span>
        </span>
      </div>

      <div class="vx-hero-card vx-hero-card--bubble" id="vxHeroBubble">
        <span class="vx-hero-bubble-icon"></span>
        <span>
          <span class="vx-hero-card-title">&ldquo;Good morning&rdquo;</span>
          <span class="vx-hero-card-sub">Signed in 0.3s</span>
        </span>
      </div>
    </div>
  </div>
</section>

<style>
  .vx-hero-grid{display:grid;grid-template-columns:1fr;gap:56px;align-items:center}
  @media(min-width:960px){.vx-hero-grid{grid-template-columns:1fr 1fr;gap:40px}}

  .vx-hero-scene{position:relative;height:420px;cursor:pointer;user-select:none}
  @media(min-width:640px){.vx-hero-scene{height:480px}}
  @media(min-width:960px){.vx-hero-scene{height:560px}}

  .vx-hero-floor{position:absolute;bottom:24px;left:50%;transform:translateX(-50%);
    height:56px;width:288px;border-radius:999px;background:rgba(11,16,32,.2);filter:blur(20px)}

  .vx-hero-hint{pointer-events:none;position:absolute;bottom:8px;left:50%;transform:translateX(-50%);
    border-radius:999px;background:rgba(255,255,255,.85);padding:5px 14px;font-size:11px;font-weight:600;
    color:var(--muted);box-shadow:0 4px 10px -4px rgba(11,16,32,.25);transition:opacity .2s ease}
  .vx-hero-hint.is-hidden{opacity:0}

  .vx-hero-hand-wrap{position:absolute;inset:0;display:flex;align-items:flex-end;justify-content:center;
    transition:transform .2s ease}
  .vx-hero-hand{width:256px;filter:drop-shadow(0 26px 22px rgba(11,16,32,.28))}
  @media(min-width:640px){.vx-hero-hand{width:320px}}
  @media(min-width:960px){.vx-hero-hand{width:420px}}

  .vx-hero-card{position:absolute;display:flex;align-items:center;gap:12px;border-radius:22px;
    background:var(--surface);padding:14px 18px;box-shadow:0 16px 30px -12px rgba(11,16,32,.24)}
  .vx-hero-card--listening{right:0;top:24px}
  .vx-hero-card--bubble{bottom:96px;left:0;transition:all .3s ease;transform:translateY(8px);opacity:0}
  .vx-hero-card--bubble.is-visible{transform:translateY(0);opacity:1}

  .vx-hero-card-icon{display:flex;height:36px;width:36px;align-items:center;justify-content:center;
    border-radius:999px;background:rgba(18,179,166,.12)}
  .vx-hero-bars{display:flex;height:22px;align-items:flex-end;gap:3px}
  .vx-hero-bars span{width:3.5px;border-radius:2px;background:var(--sign)}
  .vx-hero-bars.is-active span{animation:vxBar 1s ease-in-out infinite}
  .vx-hero-bars span:nth-child(1){animation-delay:0s}
  .vx-hero-bars span:nth-child(2){animation-delay:.15s}
  .vx-hero-bars span:nth-child(3){animation-delay:.3s}
  .vx-hero-bars span:nth-child(4){animation-delay:.45s}
  @keyframes vxBar{0%,100%{transform:scaleY(.5)}50%{transform:scaleY(1)}}

  .vx-hero-card-title{display:block;font-size:13.5px;font-weight:800;color:var(--ink)}
  .vx-hero-card-sub{display:block;font-size:11.5px;color:var(--muted)}
  .vx-hero-bubble-icon{height:36px;width:36px;border-radius:999px;background:var(--grad)}

  @keyframes vxFingerWave{0%,100%{transform:rotate(var(--rest))}50%{transform:rotate(calc(var(--rest) - 9deg))}}
  @keyframes vxPalmBob{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-10px) rotate(-3deg)}}
  .vx-hero-hand.is-signing .vx-palm-group{animation:vxPalmBob .6s ease-in-out 2}
  .vx-hero-hand.is-signing .vx-finger{animation:vxFingerWave .6s ease-in-out 2}

  @media(prefers-reduced-motion:reduce){
    .vx-hero-bars span,.vx-hero-hand.is-signing .vx-palm-group,.vx-hero-hand.is-signing .vx-finger{animation:none}
  }
</style>

<script>
  (function () {
    var scene = document.getElementById('vxHeroScene');
    var handWrap = document.getElementById('vxHeroHandWrap');
    var hand = document.getElementById('vxHeroHand');
    var hint = document.getElementById('vxHeroHint');
    var bars = document.getElementById('vxHeroBars');
    var listenTitle = document.getElementById('vxHeroListenTitle');
    var bubble = document.getElementById('vxHeroBubble');
    if (!scene) return;

    var state = 'idle'; // idle -> listening -> signing -> idle

    scene.addEventListener('mousemove', function (e) {
      var rect = scene.getBoundingClientRect();
      var px = (e.clientX - rect.left) / rect.width - 0.5;
      var py = (e.clientY - rect.top) / rect.height - 0.5;
      var tx = px * 10;
      var ty = py * 8;
      handWrap.style.transform = 'translate(' + tx + 'px,' + ty + 'px) rotate(' + tx * 0.3 + 'deg)';
    });

    scene.addEventListener('mouseleave', function () {
      handWrap.style.transform = 'translate(0,0) rotate(0deg)';
    });

    function runDemo() {
      if (state !== 'idle') return;
      state = 'listening';
      hint.classList.add('is-hidden');
      bars.classList.add('is-active');
      listenTitle.textContent = 'Listening…';

      setTimeout(function () {
        state = 'signing';
        listenTitle.textContent = 'Transcribed';
        bars.classList.remove('is-active');
        hand.classList.add('is-signing');
        bubble.classList.add('is-visible');
      }, 1400);

      setTimeout(function () {
        state = 'idle';
        listenTitle.textContent = 'Listening';
        hand.classList.remove('is-signing');
        bubble.classList.remove('is-visible');
        hint.classList.remove('is-hidden');
      }, 1400 + 2600);
    }

    scene.addEventListener('click', runDemo);
    scene.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') runDemo();
    });
  })();
</script>
