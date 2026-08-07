<section class="vx-section vx-band">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">See it in motion</div>
    <h2 class="vx-h2" style="color:#fff">The idea, previewed.</h2>
    <p class="vx-lead" style="margin:10px 0 8px">
      <span style="display:inline-block;background:var(--sign);color:var(--ink);font-family:var(--display);font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:4px 10px;border-radius:999px">Concept preview</span>
    </p>
    <p class="vx-lead vx-sec-head">
      An early look at how hand-shape signing could look — illustrative, not a verified Ugandan Sign Language rendering.
    </p>
    <div class="vx-avatar-demo">
      <div id="vx-avatar-3d" class="vx-avatar-3d"></div>
      <p id="vx-avatar-caption" class="vx-avatar-caption" style="font-family:var(--display);font-weight:700;font-size:20px;margin-top:14px">How are you?</p>
      <p class="vx-sr-only">3D figure demonstrating basic hand-shape signing for the two phrases named in the caption above and below it.</p>
    </div>
  </div>
</section>
<style>
  .vx-avatar-demo{text-align:center}
  .vx-avatar-3d{position:relative;width:280px;height:320px;margin:0 auto}
  .vx-avatar-3d canvas{display:block;margin:0 auto}
  .vx-avatar-loading{position:absolute;inset:0;z-index:4;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;pointer-events:none}
  .vx-avatar-loading-silhouette{width:38%;aspect-ratio:1/2.2;border-radius:48% 48% 40% 40%;background:rgba(159,231,245,.28)}
  .vx-avatar-loading-bar{height:4px;width:160px;border-radius:999px;background:rgba(255,255,255,.18);overflow:hidden;margin:0 auto}
  .vx-avatar-loading-bar > i{display:block;height:100%;width:12%;background:var(--cyan,#9FE7F5)}
  .vx-avatar-loading-text{margin:8px 0 0;font-size:12px;color:rgba(255,255,255,.8)}
  .vx-avatar-fallback{display:flex;align-items:center;justify-content:center;height:100%}
  .vx-avatar-fallback .vx-hand{transition:opacity .5s ease}
  .vx-avatar-fallback .vx-hand-2{opacity:0}
  .vx-avatar-fallback.vx-phrase-2 .vx-hand-1{opacity:0}
  .vx-avatar-fallback.vx-phrase-2 .vx-hand-2{opacity:1}
  @media(prefers-reduced-motion:reduce){.vx-avatar-fallback .vx-hand{transition:none}}
</style>
<script type="module">
  import { mountAvatar } from '/js/vx-avatar-loader.js?v=4';
  mountAvatar({
    container: 'vx-avatar-3d',
    captionId: 'vx-avatar-caption',
    mode: 'cycle',
    interactive: true,
    width: 280,
    height: 320,
    colorVars: ['--avatar-skin', '--avatar-skin-joint'],
    colorFallbacks: ['#C68863', '#A8714F'],
    phrases: ['How are you?', 'What is your name?'],
    poseOrder: ['howAreYou', 'whatIsYourName'],
    poses: {
      howAreYou: {
        'mixamorig:RightArm': [-50, 0, 70],
        'mixamorig:RightForeArm': [-90, 0, 0],
        'mixamorig:RightHand': [0, 0, -10],
        'mixamorig:RightHandIndex1': [0, 0, -25], 'mixamorig:RightHandIndex2': [0, 0, -20], 'mixamorig:RightHandIndex3': [0, 0, -15],
        'mixamorig:RightHandMiddle1': [0, 0, -30], 'mixamorig:RightHandMiddle2': [0, 0, -25], 'mixamorig:RightHandMiddle3': [0, 0, -15],
        'mixamorig:RightHandRing1': [0, 0, -35], 'mixamorig:RightHandRing2': [0, 0, -25], 'mixamorig:RightHandRing3': [0, 0, -15],
        'mixamorig:RightHandPinky1': [0, 0, -35], 'mixamorig:RightHandPinky2': [0, 0, -25], 'mixamorig:RightHandPinky3': [0, 0, -15],
        'mixamorig:RightHandThumb1': [0, -10, -20], 'mixamorig:RightHandThumb2': [0, 0, -15], 'mixamorig:RightHandThumb3': [0, 0, -10],
        'mixamorig:Spine1': [0, -5, 0]
      },
      whatIsYourName: {
        'mixamorig:RightArm': [-80, 0, 60],
        'mixamorig:RightForeArm': [-15, 0, 0],
        'mixamorig:RightHand': [0, 0, 0],
        'mixamorig:RightHandIndex1': [0, 0, 0], 'mixamorig:RightHandIndex2': [0, 0, 0], 'mixamorig:RightHandIndex3': [0, 0, 0],
        'mixamorig:RightHandMiddle1': [0, 0, 0], 'mixamorig:RightHandMiddle2': [0, 0, 0], 'mixamorig:RightHandMiddle3': [0, 0, 0],
        'mixamorig:RightHandRing1': [0, 0, 0], 'mixamorig:RightHandRing2': [0, 0, 0], 'mixamorig:RightHandRing3': [0, 0, 0],
        'mixamorig:RightHandPinky1': [0, 0, 0], 'mixamorig:RightHandPinky2': [0, 0, 0], 'mixamorig:RightHandPinky3': [0, 0, 0],
        'mixamorig:RightHandThumb1': [0, -10, 0], 'mixamorig:RightHandThumb2': [0, 0, 0], 'mixamorig:RightHandThumb3': [0, 0, 0],
        'mixamorig:Spine1': [0, 5, 0]
      }
    }
  });
</script>
