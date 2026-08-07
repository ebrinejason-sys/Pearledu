/**
 * Flexible skeletal motion for Genesis-style (or Mixamo) avatars.
 * Scroll guide writes window.__VX_AVATAR_MOTION__; this controller reads it each frame.
 *
 * Pose keys are logical aliases resolved against whatever bones exist in the GLB,
 * so swapping a better-skinned model later keeps working without rewriting call sites.
 */
var BONE_ALIASES = {
  hip: ['hip', 'hip(drv)', 'Hips', 'mixamorig:Hips'],
  abdomen: ['abdomenUpper', 'abdomenUpper(drv)', 'abdomenLower', 'spine', 'mixamorig:Spine'],
  chest: ['chestUpper', 'chestUpper(drv)', 'chestLower', 'mixamorig:Spine1', 'mixamorig:Spine2'],
  neck: ['neckUpper', 'neckUpper(drv)', 'neckLower', 'mixamorig:Neck'],
  head: ['head', 'head(drv)', 'mixamorig:Head'],
  lCollar: ['lCollar', 'lCollar(drv)', 'lShldrBend', 'mixamorig:LeftShoulder', 'mixamorig:LeftArm'],
  rCollar: ['rCollar', 'rCollar(drv)', 'rShldrBend', 'mixamorig:RightShoulder', 'mixamorig:RightArm'],
  lForearm: ['lForearmBend', 'lForearmTwist', 'mixamorig:LeftForeArm'],
  rForearm: ['rForearmBend', 'rForearmTwist', 'mixamorig:RightForeArm'],
  lHand: ['lHand', 'mixamorig:LeftHand'],
  rHand: ['rHand', 'mixamorig:RightHand'],
  lThigh: ['lThighBend', 'lThighTwist', 'mixamorig:LeftUpLeg'],
  rThigh: ['rThighBend', 'rThighTwist', 'mixamorig:RightUpLeg']
};

/** Section poses: degrees relative to bind/rest. Kept modest so unskinned body meshes still look OK. */
var SECTION_POSES = {
  hero: {
    chest: [4, 6, 0],
    neck: [0, 8, 0],
    head: [0, 6, 0],
    rCollar: [-12, 8, 18],
    rForearm: [-20, 0, 8],
    rHand: [0, 0, -12],
    lCollar: [4, -4, -6]
  },
  pearledu: {
    chest: [2, -10, 0],
    neck: [0, -8, 0],
    head: [0, -6, 0],
    lCollar: [-16, -10, -22],
    lForearm: [-28, 0, -10],
    lHand: [0, 10, 8],
    rCollar: [6, 4, 8]
  },
  accessibility: {
    chest: [6, 4, 0],
    neck: [4, 0, 0],
    head: [6, 0, 0],
    rCollar: [-28, 0, 32],
    rForearm: [-55, 10, 0],
    rHand: [10, 0, -18],
    lCollar: [-10, 0, -14],
    lForearm: [-20, 0, 0]
  },
  preview: {
    chest: [8, 0, 0],
    neck: [0, 0, 0],
    head: [2, 0, 0],
    rCollar: [-18, 12, 24],
    rForearm: [-35, 0, 6],
    lCollar: [-18, -12, -24],
    lForearm: [-35, 0, -6]
  },
  'how-it-works': {
    chest: [3, 8, 0],
    neck: [0, 10, 0],
    head: [0, 8, 0],
    rCollar: [-22, 5, 20],
    rForearm: [-40, 15, 0],
    rHand: [0, -8, -10],
    lCollar: [2, -2, -4]
  },
  team: {
    chest: [5, -4, 0],
    neck: [0, -4, 0],
    head: [0, -4, 0],
    rCollar: [-30, 0, 40],
    rForearm: [-10, 0, 10],
    rHand: [0, 0, -20],
    lCollar: [-8, 0, -10]
  },
  contact: {
    chest: [4, 0, 0],
    neck: [6, 0, 0],
    head: [8, 0, 0],
    rCollar: [-14, 0, 16],
    rForearm: [-25, 0, 0],
    rHand: [0, 0, 8],
    lCollar: [-14, 0, -16],
    lForearm: [-25, 0, 0]
  },
  idle: {
    chest: [2, 0, 0],
    neck: [0, 0, 0],
    head: [0, 0, 0],
    rCollar: [0, 0, 4],
    lCollar: [0, 0, -4]
  }
};

function resolveBones(root) {
  var out = {};
  Object.keys(BONE_ALIASES).forEach(function (key) {
    var names = BONE_ALIASES[key];
    for (var i = 0; i < names.length; i++) {
      var b = root.getObjectByName(names[i]);
      if (b) { out[key] = b; break; }
    }
  });
  return out;
}

function degEuler(THREE, x, y, z) {
  return new THREE.Euler(
    THREE.MathUtils.degToRad(x || 0),
    THREE.MathUtils.degToRad(y || 0),
    THREE.MathUtils.degToRad(z || 0),
    'XYZ'
  );
}

function lerpPose(a, b, t) {
  var out = {};
  var keys = {};
  Object.keys(a || {}).forEach(function (k) { keys[k] = 1; });
  Object.keys(b || {}).forEach(function (k) { keys[k] = 1; });
  Object.keys(keys).forEach(function (k) {
    var A = a[k] || [0, 0, 0];
    var B = b[k] || [0, 0, 0];
    out[k] = [
      A[0] + (B[0] - A[0]) * t,
      A[1] + (B[1] - A[1]) * t,
      A[2] + (B[2] - A[2]) * t
    ];
  });
  return out;
}

export function createAvatarMotion(THREE, root, options) {
  options = options || {};
  var bones = resolveBones(root);
  var rest = {};
  Object.keys(bones).forEach(function (k) {
    rest[k] = bones[k].quaternion.clone();
  });
  var baseY = root.position.y;
  var current = Object.assign({}, SECTION_POSES.idle);
  var tmpQ = new THREE.Quaternion();
  var tmpE = new THREE.Euler();

  // Public channel — scroll guide / page scripts can write here anytime
  if (!window.__VX_AVATAR_MOTION__) {
    window.__VX_AVATAR_MOTION__ = {
      pose: 'hero',
      nextPose: 'hero',
      blend: 1,
      velocity: 0,
      side: 1,
      energy: 0.35
    };
  }

  function readMotion() {
    return window.__VX_AVATAR_MOTION__ || { pose: 'idle', blend: 1, velocity: 0, side: 1, energy: 0.3 };
  }

  function targetPoseFromMotion(m) {
    var a = SECTION_POSES[m.pose] || SECTION_POSES.idle;
    var b = SECTION_POSES[m.nextPose || m.pose] || a;
    return lerpPose(a, b, typeof m.blend === 'number' ? m.blend : 1);
  }

  function applyBoneOffsets(poseOffsets, breath, swing) {
    Object.keys(bones).forEach(function (key) {
      var bone = bones[key];
      if (!bone || !rest[key]) return;
      var o = poseOffsets[key] || [0, 0, 0];
      var x = o[0];
      var y = o[1];
      var z = o[2];

      if (key === 'chest' || key === 'abdomen') {
        x += breath * (key === 'chest' ? 2.2 : 1.4);
      }
      if (key === 'rCollar') {
        z += swing;
        x += swing * 0.35;
      }
      if (key === 'lCollar') {
        z -= swing;
        x += swing * 0.35;
      }
      if (key === 'rForearm') x += swing * 0.8;
      if (key === 'lForearm') x += swing * 0.8;
      if (key === 'head' || key === 'neck') {
        y += Math.sin(breath * 0.7) * 1.2;
      }

      tmpE.set(
        THREE.MathUtils.degToRad(x),
        THREE.MathUtils.degToRad(y),
        THREE.MathUtils.degToRad(z),
        'XYZ'
      );
      tmpQ.setFromEuler(tmpE);
      bone.quaternion.copy(rest[key]).multiply(tmpQ);
    });
  }

  function tick(now, dragState) {
    var t = now / 1000;
    var m = readMotion();
    var target = targetPoseFromMotion(m);

    // Soft pursuit so pose changes stay flexible / non-snappy
    Object.keys(target).forEach(function (k) {
      var cur = current[k] || [0, 0, 0];
      var tgt = target[k];
      current[k] = [
        cur[0] + (tgt[0] - cur[0]) * 0.1,
        cur[1] + (tgt[1] - cur[1]) * 0.1,
        cur[2] + (tgt[2] - cur[2]) * 0.1
      ];
    });

    var energy = typeof m.energy === 'number' ? m.energy : 0.35;
    var speed = Math.min(1.6, Math.abs(m.velocity || 0) / 900);
    var breath = Math.sin(t * (1.1 + energy)) * (1 + speed * 0.5);
    var swing = Math.sin(t * (1.4 + speed * 2.2)) * (3 + speed * 10) * (m.side || 1);

    applyBoneOffsets(current, breath, swing);

    // Whole-rig liveliness (works even when body mesh is unskinned)
    var bob = Math.sin(t * (2.2 + speed * 3)) * (0.012 + speed * 0.045);
    var sway = Math.sin(t * 0.7) * 0.015;
    root.position.y = baseY + bob;
    root.rotation.z = sway * 0.4 + (m.side || 1) * speed * 0.04;
    root.rotation.x = -speed * 0.03;

    if (options.autoRotate && dragState && !dragState.active) {
      dragState.yaw += 0.0018 + speed * 0.004;
      root.rotation.y = dragState.yaw;
    }
  }

  return {
    bones: bones,
    rest: rest,
    tick: tick,
    setPose: function (name) {
      window.__VX_AVATAR_MOTION__.pose = name;
      window.__VX_AVATAR_MOTION__.nextPose = name;
      window.__VX_AVATAR_MOTION__.blend = 1;
    },
    availableBones: Object.keys(bones)
  };
}

export { SECTION_POSES, BONE_ALIASES };
