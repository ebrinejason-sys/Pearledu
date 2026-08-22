export function runPreloader(container, opts) {
  var onDone = (opts && opts.onDone) || function () {};
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var STAGGER_MS = 35;
  var REVEAL_MS = 250;
  var HOLD_MS = 350;
  var FADE_MS = 400;

  var marks = Array.prototype.slice.call(container.querySelectorAll('[data-index]'));
  if (!marks.length) { onDone(); return; }

  function isStrokeLine(el) {
    return el.tagName && el.tagName.toLowerCase() === 'line';
  }

  function lineLength(line) {
    var x1 = parseFloat(line.getAttribute('x1'));
    var x2 = parseFloat(line.getAttribute('x2'));
    return Math.abs(x2 - x1);
  }

  marks.forEach(function (el) {
    el.style.transition = 'none';
    if (isStrokeLine(el)) {
      var len = lineLength(el);
      el.style.strokeDasharray = String(len);
      el.style.strokeDashoffset = String(len);
    } else {
      el.style.opacity = '0';
    }
  });

  if (reduceMotion) {
    marks.forEach(function (el) {
      if (isStrokeLine(el)) {
        el.style.strokeDashoffset = '0';
      } else {
        el.style.opacity = '1';
      }
    });
    fadeOut();
    return;
  }

  var maxAbsIndex = marks.reduce(function (max, el) {
    return Math.max(max, Math.abs(parseInt(el.getAttribute('data-index'), 10)));
  }, 0);

  requestAnimationFrame(function () {
    marks.forEach(function (el) {
      var idx = Math.abs(parseInt(el.getAttribute('data-index'), 10));
      var delay = idx * STAGGER_MS;
      if (isStrokeLine(el)) {
        el.style.transition = 'stroke-dashoffset ' + REVEAL_MS + 'ms ease-out ' + delay + 'ms';
        el.style.strokeDashoffset = '0';
      } else {
        el.style.transition = 'opacity ' + REVEAL_MS + 'ms ease-out ' + delay + 'ms';
        el.style.opacity = '1';
      }
    });
  });

  var totalMs = maxAbsIndex * STAGGER_MS + REVEAL_MS + HOLD_MS;
  setTimeout(fadeOut, totalMs);

  function fadeOut() {
    container.style.transition = 'opacity ' + FADE_MS + 'ms ease';
    container.style.opacity = '0';
    setTimeout(onDone, reduceMotion ? 0 : FADE_MS);
  }
}
