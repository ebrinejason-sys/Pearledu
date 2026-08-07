var FRAME_PRESETS = {
  upper: { heightFraction: 0.62, padding: 1.5 },
  full: { heightFraction: 1, padding: 1.3 }
};

export function mountAvatar(config) {
  var container = typeof config.container === 'string' ? document.getElementById(config.container) : config.container;
  if (!container) return;

  var mode = config.mode || 'cycle';
  var frame = config.frame || 'upper';
  var interactive = !!config.interactive;
  var preset = FRAME_PRESETS[frame] || FRAME_PRESETS.upper;
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
    var h = container.clientHeight || height;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(32, w / h, 0.1, 100);

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch (e) {
      showFallback();
      return;
    }
    renderer.setSize(w, h);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    if ('outputColorSpace' in renderer && THREE.SRGBColorSpace) {
      renderer.outputColorSpace = THREE.SRGBColorSpace;
    } else if ('outputEncoding' in renderer && THREE.sRGBEncoding) {
      renderer.outputEncoding = THREE.sRGBEncoding;
    }
    if (THREE.ACESFilmicToneMapping != null) {
      renderer.toneMapping = THREE.ACESFilmicToneMapping;
      renderer.toneMappingExposure = 1.05;
    }
    renderer.domElement.setAttribute('aria-hidden', 'true');
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    scene.add(new THREE.HemisphereLight(0xfff2e8, 0x6b7c8f, 0.85));
    var key = new THREE.DirectionalLight(0xfff5ea, 1.15);
    key.position.set(2.2, 3.4, 2.4);
    scene.add(key);
    var fill = new THREE.DirectionalLight(0xb8d4ff, 0.35);
    fill.position.set(-2.5, 1.2, -1.5);
    scene.add(fill);
    var rim = new THREE.DirectionalLight(0xffffff, 0.25);
    rim.position.set(0, 2, -3);
    scene.add(rim);

    var loader = new GLTFLoader();
    loader.load(
      modelUrl,
      function (gltf) { onModelLoaded(gltf, THREE, scene, camera, renderer); },
      undefined,
      function () { showFallback(); }
    );
  }

  function deg(THREE, x, y, z) {
    return new THREE.Quaternion().setFromEuler(new THREE.Euler(
      THREE.MathUtils.degToRad(x), THREE.MathUtils.degToRad(y), THREE.MathUtils.degToRad(z)
    ));
  }

  function fitCameraToModel(THREE, root, camera) {
    var box = new THREE.Box3().setFromObject(root);
    var center = box.getCenter(new THREE.Vector3());
    root.position.x -= center.x;
    root.position.z -= center.z;
    root.position.y -= box.min.y;

    box.setFromObject(root);
    var size = box.getSize(new THREE.Vector3());
    center = box.getCenter(new THREE.Vector3());

    var targetHeight = size.y * preset.heightFraction;
    var fitCenterY = frame === 'full' ? center.y : box.max.y - targetHeight / 2;

    var vFov = camera.fov * (Math.PI / 180);
    var hFov = 2 * Math.atan(Math.tan(vFov / 2) * camera.aspect);
    var distanceForHeight = (targetHeight / 2) / Math.tan(vFov / 2);
    var distanceForWidth = (size.x / 2) / Math.tan(hFov / 2);
    var distance = Math.max(distanceForHeight, distanceForWidth) * preset.padding;

    camera.position.set(center.x, fitCenterY, distance);
    camera.lookAt(center.x, fitCenterY, center.z);
    camera.near = Math.max(0.01, distance / 100);
    camera.far = distance * 10;
    camera.updateProjectionMatrix();
  }

  function applyRealisticSkin(THREE, root) {
    var texLoader = new THREE.TextureLoader();
    var bodyMap = texLoader.load('/models/textures/skin-body.png');
    var faceMap = texLoader.load('/models/textures/skin-face.png');
    var lipsMap = texLoader.load('/models/textures/skin-lips.png');
    var roughMap = texLoader.load('/models/textures/skin-roughness.png');
    [bodyMap, faceMap, lipsMap, roughMap].forEach(function (tex) {
      tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
      tex.repeat.set(2, 2);
      tex.anisotropy = 4;
      if ('colorSpace' in tex && THREE.SRGBColorSpace) {
        if (tex !== roughMap) tex.colorSpace = THREE.SRGBColorSpace;
      } else if ('encoding' in tex && THREE.sRGBEncoding) {
        if (tex !== roughMap) tex.encoding = THREE.sRGBEncoding;
      }
    });
    roughMap.repeat.set(3, 3);

    var skinNames = /^(Face|Body|Arms|Legs|Head|Lips|Fingernails|Toenails|EyeSocket)/i;
    var eyeNames = /^(Pupils|Irises|Sclera|Cornea)/i;
    var hairNames = /Hair|Eyelash/i;
    var clothNames = /Shirt|Boxer|Material\.002/i;

    function skinMat(map, tint, roughness) {
      var Mat = THREE.MeshPhysicalMaterial || THREE.MeshStandardMaterial;
      var opts = {
        map: map,
        color: new THREE.Color(tint),
        roughness: roughness,
        metalness: 0,
        roughnessMap: roughMap,
        envMapIntensity: 0.35
      };
      if (THREE.MeshPhysicalMaterial) {
        opts.sheen = 0.45;
        opts.sheenRoughness = 0.55;
        opts.sheenColor = new THREE.Color(0xc47a5a);
        opts.clearcoat = 0.06;
        opts.clearcoatRoughness = 0.55;
      }
      return new Mat(opts);
    }

    root.traverse(function (node) {
      if (!node.isMesh || !node.material) return;
      var mats = Array.isArray(node.material) ? node.material : [node.material];
      var next = mats.map(function (mat) {
        var name = mat.name || node.name || '';
        if (skinNames.test(name)) {
          if (/Lips/i.test(name)) return skinMat(lipsMap, 0xf0c2b4, 0.42);
          if (/Face|Head/i.test(name)) return skinMat(faceMap, 0xf3d0b8, 0.48);
          if (/Nail/i.test(name)) {
            var nail = skinMat(bodyMap, 0xe8c4b0, 0.35);
            if (nail.clearcoat != null) { nail.clearcoat = 0.35; nail.clearcoatRoughness = 0.25; }
            return nail;
          }
          return skinMat(bodyMap, 0xedc6a8, 0.52);
        }
        if (eyeNames.test(name)) {
          var eye = mat.clone();
          eye.metalness = 0;
          if (/Cornea/i.test(name)) {
            eye.transparent = true;
            eye.opacity = 0.15;
            eye.roughness = 0.05;
          } else if (/Pupils/i.test(name)) {
            eye.color = new THREE.Color(0x1a120e);
            eye.roughness = 0.2;
          } else if (/Irises/i.test(name)) {
            eye.color = new THREE.Color(0x3d2a1f);
            eye.roughness = 0.35;
          } else {
            eye.color = new THREE.Color(0xf5f2ec);
            eye.roughness = 0.25;
          }
          return eye;
        }
        if (hairNames.test(name)) {
          var hair = mat.clone();
          hair.color = new THREE.Color(0x2a221c);
          hair.roughness = 0.65;
          hair.metalness = 0;
          return hair;
        }
        if (clothNames.test(name)) {
          var cloth = mat.clone();
          if (!cloth.map) cloth.color = new THREE.Color(0x4a5d6a);
          cloth.roughness = 0.85;
          cloth.metalness = 0;
          return cloth;
        }
        return mat;
      });
      node.material = Array.isArray(node.material) ? next : next[0];
      node.castShadow = false;
      node.receiveShadow = false;
    });
  }

  function onModelLoaded(gltf, THREE, scene, camera, renderer) {
    var root = gltf.scene;
    scene.add(root);
    fitCameraToModel(THREE, root, camera);
    applyRealisticSkin(THREE, root);

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

    if (interactive) { enableDragRotate(renderer, root); }

    if (mode === 'idle') {
      runIdle(THREE, bones, restQuats, renderer, scene, camera);
    } else {
      runCycle(THREE, bones, restQuats, renderer, scene, camera);
    }
  }

  function enableDragRotate(renderer, root) {
    var dragging = false;
    var lastX = 0;
    var baseY = root.rotation.y;
    var el = renderer.domElement;
    el.style.touchAction = 'pan-y';
    el.style.cursor = 'grab';

    function pointerDown(e) {
      dragging = true;
      lastX = e.clientX;
      baseY = root.rotation.y;
      el.style.cursor = 'grabbing';
      el.setPointerCapture(e.pointerId);
    }
    function pointerMove(e) {
      if (!dragging) return;
      var deltaX = e.clientX - lastX;
      root.rotation.y = baseY + deltaX * 0.012;
    }
    function pointerUp() {
      dragging = false;
      el.style.cursor = 'grab';
    }

    el.addEventListener('pointerdown', pointerDown);
    el.addEventListener('pointermove', pointerMove);
    el.addEventListener('pointerup', pointerUp);
    el.addEventListener('pointerleave', pointerUp);
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
