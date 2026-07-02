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
    <div class="vx-avatar-demo">
      <div id="vx-avatar-3d" class="vx-avatar-3d"></div>
      <p id="vx-avatar-caption" class="vx-avatar-caption" style="font-family:var(--display);font-weight:700;font-size:20px;margin-top:14px">How are you?</p>
      <p class="vx-sr-only">3D figure demonstrating basic hand-shape signing for the two phrases named in the caption above and below it.</p>
    </div>
  </div>
</section>
<style>
  .vx-avatar-demo{text-align:center}
  .vx-avatar-3d{width:280px;height:320px;margin:0 auto}
  .vx-avatar-3d canvas{display:block;margin:0 auto}
  .vx-avatar-fallback{display:flex;align-items:center;justify-content:center;height:100%}
  .vx-avatar-fallback .vx-hand{transition:opacity .5s ease}
  .vx-avatar-fallback .vx-hand-2{opacity:0}
  .vx-avatar-fallback.vx-phrase-2 .vx-hand-1{opacity:0}
  .vx-avatar-fallback.vx-phrase-2 .vx-hand-2{opacity:1}
  @media(prefers-reduced-motion:reduce){.vx-avatar-fallback .vx-hand{transition:none}}
</style>
<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.170.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.170.0/examples/jsm/"
  }
}
</script>
<script type="module">
(function () {
  var container = document.getElementById('vx-avatar-3d');
  if (!container) return;

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var caption = document.getElementById('vx-avatar-caption');
  var phrases = ['How are you?', 'What is your name?'];
  var captionIndex = 0;

  function showFallback() {
    container.innerHTML =
      '<svg class="vx-avatar-fallback-svg" viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Hand-shape illustration">' +
        '<g class="vx-hand vx-hand-1">' +
          '<rect x="80" y="90" width="40" height="60" rx="18" fill="var(--sign)"/>' +
          '<rect x="60" y="50" width="16" height="55" rx="8" fill="var(--sign)" transform="rotate(-8 68 78)"/>' +
          '<rect x="80" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>' +
          '<rect x="102" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>' +
          '<rect x="124" y="45" width="16" height="60" rx="8" fill="var(--sign)" transform="rotate(8 132 75)"/>' +
          '<rect x="55" y="95" width="30" height="15" rx="7" fill="var(--sign)" transform="rotate(-35 70 102)"/>' +
        '</g>' +
        '<g class="vx-hand vx-hand-2">' +
          '<rect x="80" y="100" width="42" height="55" rx="18" fill="var(--voice)"/>' +
          '<rect x="90" y="40" width="18" height="70" rx="9" fill="var(--voice)"/>' +
          '<rect x="60" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>' +
          '<rect x="118" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>' +
        '</g>' +
      '</svg>';
    container.classList.add('vx-avatar-fallback');
    if (caption) caption.textContent = phrases[0];
    if (!reduceMotion) {
      setInterval(function () {
        captionIndex = (captionIndex + 1) % phrases.length;
        if (caption) caption.textContent = phrases[captionIndex];
        container.classList.toggle('vx-phrase-2', captionIndex === 1);
      }, 3200);
    }
  }

  if (!window.WebGLRenderingContext) { showFallback(); return; }

  Promise.all([import('three'), import('three/addons/loaders/GLTFLoader.js')])
    .then(function (mods) { initScene(mods[0], mods[1].GLTFLoader); })
    .catch(function () { showFallback(); });

  function initScene(THREE, GLTFLoader) {
    var width = container.clientWidth || 280;
    var height = 320;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(32, width / height, 0.1, 100);
    camera.position.set(0, 1.4, 3.4);
    camera.lookAt(0, 1.1, 0);

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch (e) {
      showFallback();
      return;
    }
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.domElement.setAttribute('aria-hidden', 'true');
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 0.7));
    var key = new THREE.DirectionalLight(0xffffff, 0.9);
    key.position.set(2, 3, 2);
    scene.add(key);

    function getCssVar(name, fallback) {
      var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return v || fallback;
    }

    var loader = new GLTFLoader();
    loader.load(
      '/models/avatar.glb',
      function (gltf) { onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar, container, caption, phrases, reduceMotion); },
      undefined,
      function () { showFallback(); }
    );
  }

  function onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar, container, caption, phrases, reduceMotion) {
    var root = gltf.scene;
    root.scale.setScalar(1.55);
    root.position.set(0, -1.15, 0);
    scene.add(root);

    var bodyColor = new THREE.Color(getCssVar('--sign', '#12B3A6'));
    var jointColor = new THREE.Color(getCssVar('--voice', '#FF6A3D'));
    root.traverse(function (node) {
      if (node.isMesh && node.material) {
        node.material = node.material.clone();
        var isJoint = (node.material.name || '').indexOf('Joint') !== -1;
        node.material.color.copy(isJoint ? jointColor : bodyColor);
      }
    });

    var boneNames = [
      'mixamorig:RightArm', 'mixamorig:RightForeArm', 'mixamorig:RightHand',
      'mixamorig:RightHandIndex1', 'mixamorig:RightHandIndex2', 'mixamorig:RightHandIndex3',
      'mixamorig:RightHandMiddle1', 'mixamorig:RightHandMiddle2', 'mixamorig:RightHandMiddle3',
      'mixamorig:RightHandRing1', 'mixamorig:RightHandRing2', 'mixamorig:RightHandRing3',
      'mixamorig:RightHandPinky1', 'mixamorig:RightHandPinky2', 'mixamorig:RightHandPinky3',
      'mixamorig:RightHandThumb1', 'mixamorig:RightHandThumb2', 'mixamorig:RightHandThumb3',
      'mixamorig:Spine1'
    ];
    var bones = {};
    boneNames.forEach(function (name) {
      var b = root.getObjectByName(name);
      if (b) bones[name] = b;
    });

    var restQuats = {};
    Object.keys(bones).forEach(function (name) { restQuats[name] = bones[name].quaternion.clone(); });

    function deg(x, y, z) {
      return new THREE.Quaternion().setFromEuler(new THREE.Euler(
        THREE.MathUtils.degToRad(x), THREE.MathUtils.degToRad(y), THREE.MathUtils.degToRad(z)
      ));
    }

    var poses = {
      howAreYou: {
        'mixamorig:RightArm': deg(-50, 0, 70),
        'mixamorig:RightForeArm': deg(-90, 0, 0),
        'mixamorig:RightHand': deg(0, 0, -10),
        'mixamorig:RightHandIndex1': deg(0, 0, -25), 'mixamorig:RightHandIndex2': deg(0, 0, -20), 'mixamorig:RightHandIndex3': deg(0, 0, -15),
        'mixamorig:RightHandMiddle1': deg(0, 0, -30), 'mixamorig:RightHandMiddle2': deg(0, 0, -25), 'mixamorig:RightHandMiddle3': deg(0, 0, -15),
        'mixamorig:RightHandRing1': deg(0, 0, -35), 'mixamorig:RightHandRing2': deg(0, 0, -25), 'mixamorig:RightHandRing3': deg(0, 0, -15),
        'mixamorig:RightHandPinky1': deg(0, 0, -35), 'mixamorig:RightHandPinky2': deg(0, 0, -25), 'mixamorig:RightHandPinky3': deg(0, 0, -15),
        'mixamorig:RightHandThumb1': deg(0, -10, -20), 'mixamorig:RightHandThumb2': deg(0, 0, -15), 'mixamorig:RightHandThumb3': deg(0, 0, -10),
        'mixamorig:Spine1': deg(0, -5, 0)
      },
      whatIsYourName: {
        'mixamorig:RightArm': deg(-80, 0, 60),
        'mixamorig:RightForeArm': deg(-15, 0, 0),
        'mixamorig:RightHand': deg(0, 0, 0),
        'mixamorig:RightHandIndex1': deg(0, 0, 0), 'mixamorig:RightHandIndex2': deg(0, 0, 0), 'mixamorig:RightHandIndex3': deg(0, 0, 0),
        'mixamorig:RightHandMiddle1': deg(0, 0, 0), 'mixamorig:RightHandMiddle2': deg(0, 0, 0), 'mixamorig:RightHandMiddle3': deg(0, 0, 0),
        'mixamorig:RightHandRing1': deg(0, 0, 0), 'mixamorig:RightHandRing2': deg(0, 0, 0), 'mixamorig:RightHandRing3': deg(0, 0, 0),
        'mixamorig:RightHandPinky1': deg(0, 0, 0), 'mixamorig:RightHandPinky2': deg(0, 0, 0), 'mixamorig:RightHandPinky3': deg(0, 0, 0),
        'mixamorig:RightHandThumb1': deg(0, -10, 0), 'mixamorig:RightHandThumb2': deg(0, 0, 0), 'mixamorig:RightHandThumb3': deg(0, 0, 0),
        'mixamorig:Spine1': deg(0, 5, 0)
      }
    };
    var poseOrder = ['howAreYou', 'whatIsYourName'];

    function applyPoseStatic(pose) {
      Object.keys(pose).forEach(function (name) { if (bones[name]) bones[name].quaternion.copy(pose[name]); });
    }

    if (reduceMotion) {
      applyPoseStatic(poses[poseOrder[0]]);
      if (caption) caption.textContent = phrases[0];
      renderer.render(scene, camera);
      return;
    }

    var HOLD_MS = 3200;
    var TRANSITION_MS = 600;
    var poseIdx = 0;
    var phase = 'hold';
    var phaseStart = performance.now();
    var fromPose = clonePose(bones);
    var toPose = poses[poseOrder[0]];
    applyPoseStatic(toPose);
    if (caption) caption.textContent = phrases[0];

    function clonePose(boneMap) {
      var out = {};
      Object.keys(boneMap).forEach(function (name) { out[name] = boneMap[name].quaternion.clone(); });
      return out;
    }

    function easeInOutQuad(t) { return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; }

    function tick(now) {
      var elapsed = now - phaseStart;
      if (phase === 'hold' && elapsed >= HOLD_MS) {
        phase = 'transition';
        phaseStart = now;
        fromPose = clonePose(bones);
        poseIdx = (poseIdx + 1) % poseOrder.length;
        toPose = poses[poseOrder[poseIdx]];
        if (caption) caption.textContent = phrases[poseIdx];
      } else if (phase === 'transition') {
        var t = Math.min(1, (now - phaseStart) / TRANSITION_MS);
        var e = easeInOutQuad(t);
        Object.keys(bones).forEach(function (name) {
          var target = toPose[name] || restQuats[name];
          bones[name].quaternion.slerpQuaternions(fromPose[name], target, e);
        });
        if (t >= 1) { phase = 'hold'; phaseStart = now; }
      }
      renderer.render(scene, camera);
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
})();
</script>
