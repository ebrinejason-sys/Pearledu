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
  var autoRotate = config.autoRotate !== false && !!config.interactive;
  var preset = FRAME_PRESETS[frame] || FRAME_PRESETS.upper;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var captionEl = config.captionId ? document.getElementById(config.captionId) : null;
  var phrases = config.phrases || [];
  var poses = config.poses || {};
  var poseOrder = config.poseOrder || Object.keys(poses);
  var modelUrl = config.modelUrl || '/models/avatar.glb';
  var colorVars = config.colorVars || ['--sign', '--voice'];
  var width = config.width || 280;
  var height = config.height || 320;
  var captionIndex = 0;
  var interactHost = container.closest('.vx-scroll-avatar-stage') || container.closest('.vx-hero-avatar-wrap') || container;

  function showLoading(percent) {
    var pct = typeof percent === 'number' ? Math.max(0, Math.min(100, Math.round(percent))) : null;
    var label = pct == null ? 'Loading figure…' : ('Loading figure… ' + pct + '%');
    var host = interactHost || container;
    var existing = host.querySelector('.vx-avatar-loading');
    if (!existing) {
      existing = document.createElement('div');
      existing.className = 'vx-avatar-loading';
      existing.setAttribute('role', 'status');
      existing.setAttribute('aria-live', 'polite');
      existing.innerHTML =
        '<div class="vx-avatar-loading-silhouette" aria-hidden="true"></div>' +
        '<div class="vx-avatar-loading-meta">' +
          '<div class="vx-avatar-loading-bar" aria-hidden="true"><i></i></div>' +
          '<p class="vx-avatar-loading-text"></p>' +
        '</div>';
      host.appendChild(existing);
    }
    existing.hidden = false;
    var bar = existing.querySelector('.vx-avatar-loading-bar > i');
    var text = existing.querySelector('.vx-avatar-loading-text');
    if (bar) bar.style.width = (pct == null ? 12 : pct) + '%';
    if (text) text.textContent = label;
  }

  function hideLoading() {
    var host = interactHost || container;
    var existing = host.querySelector('.vx-avatar-loading');
    if (existing) existing.remove();
  }

  showLoading(0);

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
          '<rect x="80" y="100" width="42" height="55" rx="18" fill="var(--voice, #FF6A3D)"/>' +
          '<rect x="90" y="40" width="18" height="70" rx="9" fill="var(--voice, #FF6A3D)"/>' +
          '<rect x="60" y="105" width="24" height="16" rx="8" fill="var(--voice, #FF6A3D)"/>' +
          '<rect x="118" y="105" width="24" height="16" rx="8" fill="var(--voice, #FF6A3D)"/>' +
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

  Promise.all([
    import('three'),
    import('three/addons/loaders/GLTFLoader.js'),
    import('three/addons/loaders/DRACOLoader.js')
  ])
    .then(function (mods) { initScene(mods[0], mods[1].GLTFLoader, mods[2].DRACOLoader); })
    .catch(function () { showFallback(); });

  function initScene(THREE, GLTFLoader, DRACOLoader) {
    var w = container.clientWidth || width;
    var h = container.clientHeight || height;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(32, w / h, 0.1, 100);

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
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
      renderer.toneMappingExposure = 1.12;
    }
    renderer.domElement.setAttribute('aria-hidden', 'true');
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    scene.add(new THREE.HemisphereLight(0xfff0e6, 0x4a5a6e, 1.05));
    var key = new THREE.DirectionalLight(0xfff6ee, 1.35);
    key.position.set(2.4, 4.2, 2.8);
    scene.add(key);
    var fill = new THREE.DirectionalLight(0xa8c8ff, 0.45);
    fill.position.set(-3, 1.5, -1.2);
    scene.add(fill);
    var rim = new THREE.DirectionalLight(0xffffff, 0.4);
    rim.position.set(-0.5, 2.5, -3.5);
    scene.add(rim);

    var dracoLoader = new DRACOLoader();
    dracoLoader.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.7/');
    dracoLoader.preload();

    var loader = new GLTFLoader();
    loader.setDRACOLoader(dracoLoader);
    loader.load(
      modelUrl,
      function (gltf) {
        hideLoading();
        onModelLoaded(gltf, THREE, scene, camera, renderer);
        dracoLoader.dispose();
      },
      function (event) {
        if (event && event.total) {
          showLoading((event.loaded / event.total) * 100);
        } else if (event && event.loaded) {
          // Indeterminate but advancing feel for servers without Content-Length
          showLoading(Math.min(92, 12 + (event.loaded / 650000) * 80));
        }
      },
      function () {
        hideLoading();
        showFallback();
      }
    );

    window.addEventListener('resize', function () {
      var nw = container.clientWidth || width;
      var nh = container.clientHeight || height;
      camera.aspect = nw / nh;
      camera.updateProjectionMatrix();
      renderer.setSize(nw, nh);
    });
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

  function applyRealisticSkin(THREE, root, renderer, scene, camera) {
    var texLoader = new THREE.TextureLoader();
    var pending = 0;
    var maps = {};

    function loadMap(key, url, isColor) {
      pending += 1;
      maps[key] = texLoader.load(url, function (tex) {
        tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
        tex.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy ? renderer.capabilities.getMaxAnisotropy() : 4);
        if (isColor) {
          tex.repeat.set(1.15, 1.15);
          if ('colorSpace' in tex && THREE.SRGBColorSpace) tex.colorSpace = THREE.SRGBColorSpace;
          else if ('encoding' in tex && THREE.sRGBEncoding) tex.encoding = THREE.sRGBEncoding;
        } else {
          tex.repeat.set(4, 4);
        }
        tex.needsUpdate = true;
        pending -= 1;
        if (pending === 0) renderer.render(scene, camera);
      }, undefined, function () { pending -= 1; });
    }

    loadMap('body', '/models/textures/skin-body.png', true);
    loadMap('face', '/models/textures/skin-face.png', true);
    loadMap('lips', '/models/textures/skin-lips.png', true);
    loadMap('rough', '/models/textures/skin-roughness.png', false);

    function skinMat(mapKey, tintHex, roughness) {
      var Mat = THREE.MeshPhysicalMaterial || THREE.MeshStandardMaterial;
      var opts = {
        map: maps[mapKey],
        color: new THREE.Color(tintHex),
        roughness: roughness,
        metalness: 0,
        roughnessMap: maps.rough,
        flatShading: false
      };
      if (THREE.MeshPhysicalMaterial) {
        opts.sheen = 0.55;
        opts.sheenRoughness = 0.62;
        opts.sheenColor = new THREE.Color(0xb86a4a);
        opts.clearcoat = 0.08;
        opts.clearcoatRoughness = 0.5;
      }
      var mat = new Mat(opts);
      mat.name = 'Skin-' + mapKey;
      return mat;
    }

    function solidMat(hex, roughness, extras) {
      var Mat = THREE.MeshStandardMaterial;
      var opts = Object.assign({
        color: new THREE.Color(hex),
        roughness: roughness,
        metalness: 0
      }, extras || {});
      return new Mat(opts);
    }

    root.traverse(function (node) {
      if (!node.isMesh || !node.material) return;
      var mats = Array.isArray(node.material) ? node.material : [node.material];
      var next = mats.map(function (mat) {
        if (!mat) return solidMat(0xc99574, 0.55);
        var name = (mat.name || '') + ' ' + (node.name || '');

        if (/Lips/i.test(name)) return skinMat('lips', 0xc97878, 0.4);
        if (/Face|Head/i.test(name)) return skinMat('face', 0xd4a07a, 0.48);
        if (/Body|Arms|Legs|EyeSocket|Fingernail|Toenail/i.test(name)) return skinMat('body', 0xc99574, 0.55);
        if (/Cornea/i.test(name)) {
          return solidMat(0xffffff, 0.05, { transparent: true, opacity: 0.12, depthWrite: false });
        }
        if (/Pupil/i.test(name)) return solidMat(0x140e0c, 0.25);
        if (/Iris/i.test(name)) return solidMat(0x3a2418, 0.35);
        if (/Sclera/i.test(name)) return solidMat(0xf3efe8, 0.3);
        if (/Hair|Eyelash/i.test(name)) return solidMat(0x1c1612, 0.72);
        if (/Shirt/i.test(name)) return solidMat(0x2f6f86, 0.82);
        if (/Boxer/i.test(name)) return solidMat(0x1e2a36, 0.88);
        if (/Material\.002|Tear/i.test(name)) return solidMat(0xe8f4f8, 0.2, { transparent: true, opacity: 0.35 });
        // Unknown mesh parts: keep a warm skin default so nothing stays plastic white
        if (!mat.map) return skinMat('body', 0xc99574, 0.55);
        return mat;
      });
      node.material = Array.isArray(node.material) ? next : next[0];
      node.frustumCulled = true;
    });
  }

  function findBone(root, names) {
    for (var i = 0; i < names.length; i++) {
      var b = root.getObjectByName(names[i]);
      if (b) return b;
    }
    return null;
  }

  function onModelLoaded(gltf, THREE, scene, camera, renderer) {
    var root = gltf.scene;
    scene.add(root);
    fitCameraToModel(THREE, root, camera);
    applyRealisticSkin(THREE, root, renderer, scene, camera);

    var bones = {
      spine: findBone(root, ['chestUpper', 'chestUpper(drv)', 'chestLower', 'abdomenUpper(drv)', 'mixamorig:Spine1']),
      arm: findBone(root, ['rCollar', 'rCollar(drv)', 'rForearmBend', 'mixamorig:RightArm']),
      hand: findBone(root, ['rHand', 'mixamorig:RightHand'])
    };
    var restQuats = {};
    Object.keys(bones).forEach(function (key) {
      if (bones[key]) restQuats[key] = bones[key].quaternion.clone();
    });

    var dragState = { active: false, yaw: 0 };
    if (interactive) {
      enableDragRotate(renderer, root, dragState, interactHost);
    }

    if (mode === 'idle') {
      runIdle(THREE, bones, restQuats, renderer, scene, camera, root, dragState);
    } else {
      runCycle(THREE, root, restQuats, renderer, scene, camera, dragState);
    }
  }

  function enableDragRotate(renderer, root, dragState, host) {
    var dragging = false;
    var lastX = 0;
    var el = renderer.domElement;
    var surface = host || el;
    el.style.touchAction = 'none';
    surface.style.cursor = 'grab';
    surface.style.touchAction = 'none';

    function markInteracted() {
      if (host && host.classList) host.classList.add('has-interacted');
    }

    function pointerDown(e) {
      dragging = true;
      dragState.active = true;
      lastX = e.clientX;
      surface.style.cursor = 'grabbing';
      if (host && host.classList) host.classList.add('is-dragging');
      try { surface.setPointerCapture(e.pointerId); } catch (err) {}
      e.preventDefault();
    }
    function pointerMove(e) {
      if (!dragging) return;
      var deltaX = e.clientX - lastX;
      lastX = e.clientX;
      dragState.yaw += deltaX * 0.01;
      root.rotation.y = dragState.yaw;
      markInteracted();
    }
    function pointerUp() {
      dragging = false;
      dragState.active = false;
      surface.style.cursor = 'grab';
      if (host && host.classList) host.classList.remove('is-dragging');
    }

    surface.addEventListener('pointerdown', pointerDown);
    surface.addEventListener('pointermove', pointerMove);
    surface.addEventListener('pointerup', pointerUp);
    surface.addEventListener('pointercancel', pointerUp);
  }

  function runIdle(THREE, bones, restQuats, renderer, scene, camera, root, dragState) {
    if (reduceMotion) { renderer.render(scene, camera); return; }
    var start = performance.now();
    function tick(now) {
      var t = (now - start) / 1000;
      if (bones.spine && restQuats.spine) {
        bones.spine.quaternion.copy(restQuats.spine).multiply(deg(THREE, Math.sin(t * 0.55) * 2.2, Math.sin(t * 0.35) * 1.4, 0));
      }
      if (bones.arm && restQuats.arm) {
        bones.arm.quaternion.copy(restQuats.arm).multiply(deg(THREE, Math.sin(t * 0.45 + 1) * 2.5, 0, Math.sin(t * 0.3) * 1.5));
      }
      if (autoRotate && !dragState.active && !reduceMotion) {
        dragState.yaw += 0.0022;
        root.rotation.y = dragState.yaw;
      }
      renderer.render(scene, camera);
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function runCycle(THREE, root, restQuats, renderer, scene, camera, dragState) {
    // Pose cycle still expects Mixamo-style bone maps from config; fall back to idle motion if empty
    if (!poseOrder.length) {
      runIdle(THREE, {}, restQuats, renderer, scene, camera, root, dragState);
      return;
    }

    var boneNames = Object.keys(poses[poseOrder[0]] || {});
    var bones = {};
    boneNames.forEach(function (name) {
      var b = root.getObjectByName(name);
      if (b) bones[name] = b;
    });

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
          if (target) bones[name].quaternion.slerpQuaternions(fromPose[name], target, e);
        });
        if (t >= 1) { phase = 'hold'; phaseStart = now; }
      }
      if (autoRotate && !dragState.active) {
        dragState.yaw += 0.0015;
        root.rotation.y = dragState.yaw;
      }
      renderer.render(scene, camera);
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
}
