export function mountAvatar(config) {
  var container = typeof config.container === 'string' ? document.getElementById(config.container) : config.container;
  if (!container) return;

  var mode = config.mode || 'cycle';
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var captionEl = config.captionId ? document.getElementById(config.captionId) : null;
  var phrases = config.phrases || [];
  var poses = config.poses || {};
  var poseOrder = config.poseOrder || Object.keys(poses);
  var modelUrl = config.modelUrl || '/models/avatar.glb';
  var colorVars = config.colorVars || ['--sign', '--voice'];
  var colorFallbacks = config.colorFallbacks || ['#12B3A6', '#FF6A3D'];
  var width = config.width || 280;
  var height = config.height || 320;
  var captionIndex = 0;

  function buildFallbackSvg() {
    return '<svg class="vx-avatar-fallback-svg" viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Hand-shape illustration">' +
        '<g class="vx-hand vx-hand-1">' +
          '<rect x="80" y="90" width="40" height="60" rx="18" fill="var(' + colorVars[0] + ')"/>' +
          '<rect x="60" y="50" width="16" height="55" rx="8" fill="var(' + colorVars[0] + ')" transform="rotate(-8 68 78)"/>' +
          '<rect x="80" y="35" width="16" height="65" rx="8" fill="var(' + colorVars[0] + ')"/>' +
          '<rect x="102" y="35" width="16" height="65" rx="8" fill="var(' + colorVars[0] + ')"/>' +
          '<rect x="124" y="45" width="16" height="60" rx="8" fill="var(' + colorVars[0] + ')" transform="rotate(8 132 75)"/>' +
          '<rect x="55" y="95" width="30" height="15" rx="7" fill="var(' + colorVars[0] + ')" transform="rotate(-35 70 102)"/>' +
        '</g>' +
        '<g class="vx-hand vx-hand-2">' +
          '<rect x="80" y="100" width="42" height="55" rx="18" fill="var(' + colorVars[1] + ')"/>' +
          '<rect x="90" y="40" width="18" height="70" rx="9" fill="var(' + colorVars[1] + ')"/>' +
          '<rect x="60" y="105" width="24" height="16" rx="8" fill="var(' + colorVars[1] + ')"/>' +
          '<rect x="118" y="105" width="24" height="16" rx="8" fill="var(' + colorVars[1] + ')"/>' +
        '</g>' +
      '</svg>';
  }

  function showFallback() {
    container.innerHTML = buildFallbackSvg();
    container.classList.add('vx-avatar-fallback');
    if (captionEl && phrases.length) {
      captionEl.textContent = phrases[0];
      if (!reduceMotion) {
        setInterval(function () {
          captionIndex = (captionIndex + 1) % phrases.length;
          captionEl.textContent = phrases[captionIndex];
          container.classList.toggle('vx-phrase-2', captionIndex === 1);
        }, 3200);
      }
    }
  }

  if (!window.WebGLRenderingContext) { showFallback(); return; }

  Promise.all([import('three'), import('three/addons/loaders/GLTFLoader.js')])
    .then(function (mods) { initScene(mods[0], mods[1].GLTFLoader); })
    .catch(function () { showFallback(); });

  function initScene(THREE, GLTFLoader) {
    var w = container.clientWidth || width;
    var h = height;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(32, w / h, 0.1, 100);
    camera.position.set(0, 1.4, 3.4);
    camera.lookAt(0, 1.1, 0);

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch (e) {
      showFallback();
      return;
    }
    renderer.setSize(w, h);
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
      modelUrl,
      function (gltf) { onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar); },
      undefined,
      function () { showFallback(); }
    );
  }

  function deg(THREE, x, y, z) {
    return new THREE.Quaternion().setFromEuler(new THREE.Euler(
      THREE.MathUtils.degToRad(x), THREE.MathUtils.degToRad(y), THREE.MathUtils.degToRad(z)
    ));
  }

  function onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar) {
    var root = gltf.scene;
    root.scale.setScalar(1.55);
    root.position.set(0, -1.15, 0);
    scene.add(root);

    var bodyColor = new THREE.Color(getCssVar(colorVars[0], colorFallbacks[0]));
    var jointColor = new THREE.Color(getCssVar(colorVars[1], colorFallbacks[1]));
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

    if (mode === 'idle') {
      runIdle(THREE, bones, restQuats, renderer, scene, camera);
    } else {
      runCycle(THREE, bones, restQuats, renderer, scene, camera);
    }
  }

  function runIdle(THREE, bones, restQuats, renderer, scene, camera) {
    if (reduceMotion) { renderer.render(scene, camera); return; }
    var start = performance.now();
    function tick(now) {
      var t = (now - start) / 1000;
      var spine = bones['mixamorig:Spine1'];
      if (spine && restQuats['mixamorig:Spine1']) {
        spine.quaternion.copy(restQuats['mixamorig:Spine1']).multiply(deg(THREE, Math.sin(t * 0.6) * 2.5, Math.sin(t * 0.4) * 1.5, 0));
      }
      var arm = bones['mixamorig:RightArm'];
      if (arm && restQuats['mixamorig:RightArm']) {
        arm.quaternion.copy(restQuats['mixamorig:RightArm']).multiply(deg(THREE, Math.sin(t * 0.5 + 1) * 3, 0, 0));
      }
      renderer.render(scene, camera);
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function runCycle(THREE, bones, restQuats, renderer, scene, camera) {
    var builtPoses = {};
    poseOrder.forEach(function (key) {
      var raw = poses[key] || {};
      var built = {};
      Object.keys(raw).forEach(function (boneName) {
        var t = raw[boneName];
        built[boneName] = deg(THREE, t[0], t[1], t[2]);
      });
      builtPoses[key] = built;
    });

    function applyPoseStatic(pose) {
      Object.keys(pose).forEach(function (name) { if (bones[name]) bones[name].quaternion.copy(pose[name]); });
    }

    if (reduceMotion) {
      applyPoseStatic(builtPoses[poseOrder[0]]);
      if (captionEl) captionEl.textContent = phrases[0];
      renderer.render(scene, camera);
      return;
    }

    var HOLD_MS = 3200;
    var TRANSITION_MS = 600;
    var poseIdx = 0;
    var phase = 'hold';
    var phaseStart = performance.now();

    function clonePose(boneMap) {
      var out = {};
      Object.keys(boneMap).forEach(function (name) { out[name] = boneMap[name].quaternion.clone(); });
      return out;
    }

    var fromPose = clonePose(bones);
    var toPose = builtPoses[poseOrder[0]];
    applyPoseStatic(toPose);
    if (captionEl) captionEl.textContent = phrases[0];

    function easeInOutQuad(t) { return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; }

    function tick(now) {
      var elapsed = now - phaseStart;
      if (phase === 'hold' && elapsed >= HOLD_MS) {
        phase = 'transition';
        phaseStart = now;
        fromPose = clonePose(bones);
        poseIdx = (poseIdx + 1) % poseOrder.length;
        toPose = builtPoses[poseOrder[poseIdx]];
        if (captionEl) captionEl.textContent = phrases[poseIdx];
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
}
