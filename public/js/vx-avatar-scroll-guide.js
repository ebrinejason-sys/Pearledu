/**
 * Scroll-guided floating avatar (desktop experiment).
 * Disable by setting window.__VX_SCROLL_AVATAR__ = false before load,
 * or set $vxScrollAvatar = false in home.blade.php.
 *
 * Publishes window.__VX_AVATAR_MOTION__ so the skeletal controller can react.
 */
export function startAvatarScrollGuide(options) {
  var stage = typeof options.stage === 'string' ? document.querySelector(options.stage) : options.stage;
  if (!stage) return function () {};

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var desktop = window.matchMedia('(min-width: 861px)').matches;
  if (!desktop || reduceMotion || window.__VX_SCROLL_AVATAR__ === false) {
    stage.classList.add('is-static-hero');
    window.__VX_AVATAR_MOTION__ = { pose: 'hero', nextPose: 'hero', blend: 1, velocity: 0, side: 1, energy: 0.3 };
    return function () {};
  }

  var stops = (options.stops || [
    { sel: '.vx-hero', left: 78, top: 6, scale: 1, opacity: 1, pose: 'hero' },
    { sel: '#pearledu', left: 8, top: 16, scale: 0.82, opacity: 1, pose: 'pearledu' },
    { sel: '#accessibility', left: 80, top: 14, scale: 0.82, opacity: 1, pose: 'accessibility' },
    { sel: '#preview', left: 50, top: 12, scale: 0.9, opacity: 1, pose: 'preview' },
    { sel: '#how-it-works', left: 76, top: 18, scale: 0.74, opacity: 1, pose: 'how-it-works' },
    { sel: '#team', left: 12, top: 20, scale: 0.7, opacity: 1, pose: 'team' },
    { sel: '#contact', left: 82, top: 26, scale: 0.55, opacity: 0.88, pose: 'contact' }
  ]).map(function (stop) {
    return Object.assign({}, stop, { el: document.querySelector(stop.sel) });
  }).filter(function (stop) { return !!stop.el; });

  if (stops.length < 2) {
    stage.classList.add('is-static-hero');
    return function () {};
  }

  var state = {
    left: stops[0].left,
    top: stops[0].top,
    scale: stops[0].scale,
    opacity: stops[0].opacity,
    dragging: false,
    raf: 0,
    lastScrollY: window.scrollY || 0,
    lastScrollT: performance.now(),
    velocity: 0
  };

  stage.classList.add('is-guided');
  document.documentElement.classList.add('vx-has-scroll-avatar');

  function clamp(n, a, b) { return Math.max(a, Math.min(b, n)); }
  function lerp(a, b, t) { return a + (b - a) * t; }
  function ease(t) { return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; }

  function measure() {
    var scrollY = window.scrollY || window.pageYOffset;
    var viewMid = scrollY + window.innerHeight * 0.42;
    var points = stops.map(function (stop) {
      var rect = stop.el.getBoundingClientRect();
      var top = rect.top + scrollY;
      var mid = top + rect.height * 0.35;
      return { stop: stop, mid: mid };
    });

    if (viewMid <= points[0].mid) {
      return { from: points[0].stop, to: points[0].stop, t: 0 };
    }
    if (viewMid >= points[points.length - 1].mid) {
      var last = points[points.length - 1].stop;
      return { from: last, to: last, t: 1 };
    }

    for (var i = 0; i < points.length - 1; i++) {
      var a = points[i];
      var b = points[i + 1];
      if (viewMid >= a.mid && viewMid <= b.mid) {
        var t = (viewMid - a.mid) / Math.max(1, b.mid - a.mid);
        return { from: a.stop, to: b.stop, t: ease(clamp(t, 0, 1)) };
      }
    }
    return { from: points[0].stop, to: points[0].stop, t: 0 };
  }

  function publishMotion(m) {
    var side = (m.to.left - m.from.left) >= 0 ? 1 : -1;
    if (Math.abs(m.to.left - m.from.left) < 1) side = state.left > 50 ? 1 : -1;
    window.__VX_AVATAR_MOTION__ = {
      pose: m.from.pose || 'idle',
      nextPose: m.to.pose || m.from.pose || 'idle',
      blend: m.t,
      velocity: state.velocity,
      side: side,
      energy: 0.35 + Math.min(0.9, Math.abs(state.velocity) / 1200)
    };
  }

  function applyTarget(immediate) {
    if (state.dragging) return;
    var m = measure();
    var target = {
      left: lerp(m.from.left, m.to.left, m.t),
      top: lerp(m.from.top, m.to.top, m.t),
      scale: lerp(m.from.scale, m.to.scale, m.t),
      opacity: lerp(m.from.opacity || 1, m.to.opacity || 1, m.t)
    };

    if (immediate) {
      state.left = target.left;
      state.top = target.top;
      state.scale = target.scale;
      state.opacity = target.opacity;
    } else {
      state.left = lerp(state.left, target.left, 0.14);
      state.top = lerp(state.top, target.top, 0.14);
      state.scale = lerp(state.scale, target.scale, 0.14);
      state.opacity = lerp(state.opacity, target.opacity, 0.14);
    }

    stage.style.left = state.left + '%';
    stage.style.top = state.top + '%';
    stage.style.opacity = String(state.opacity);
    stage.style.transform = 'translate(-50%, 0) scale(' + state.scale + ')';
    publishMotion(m);
  }

  function tick(now) {
    var y = window.scrollY || 0;
    var dt = Math.max(0.016, (now - state.lastScrollT) / 1000);
    var rawV = (y - state.lastScrollY) / dt;
    state.velocity = state.velocity * 0.85 + rawV * 0.15;
    state.lastScrollY = y;
    state.lastScrollT = now;
    applyTarget(false);
    state.raf = requestAnimationFrame(tick);
  }

  function onScroll() {
    if (!state.raf) state.raf = requestAnimationFrame(tick);
  }

  stage.addEventListener('pointerdown', function () { state.dragging = true; });
  window.addEventListener('pointerup', function () { state.dragging = false; });
  window.addEventListener('pointercancel', function () { state.dragging = false; });
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', function () { applyTarget(true); }, { passive: true });

  applyTarget(true);
  state.raf = requestAnimationFrame(tick);

  return function stop() {
    cancelAnimationFrame(state.raf);
    state.raf = 0;
    window.removeEventListener('scroll', onScroll);
    document.documentElement.classList.remove('vx-has-scroll-avatar');
    stage.classList.remove('is-guided');
  };
}
