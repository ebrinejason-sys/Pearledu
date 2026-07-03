export function runPreloader(container, opts) {
  var onDone = (opts && opts.onDone) || function () {};
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var STAGGER_MS = 35;
  var REVEAL_MS = 250;
  var HOLD_MS = 350;
  var FADE_MS = 400;

  var lines = Array.prototype.slice.call(container.querySelectorAll('line[data-index]'));
  if (!lines.length) { onDone(); return; }

  function lineLength(line) {
    var x1 = parseFloat(line.getAttribute('x1'));
    var x2 = parseFloat(line.getAttribute('x2'));
    return Math.abs(x2 - x1);
  }

  lines.forEach(function (line) {
    var len = lineLength(line);
    line.style.strokeDasharray = String(len);
    line.style.strokeDashoffset = String(len);
    line.style.transition = 'none';
  });

  if (reduceMotion) {
    lines.forEach(function (line) { line.style.strokeDashoffset = '0'; });
    fadeOut();
    return;
  }

  var maxAbsIndex = lines.reduce(function (max, line) {
    return Math.max(max, Math.abs(parseInt(line.getAttribute('data-index'), 10)));
  }, 0);

  requestAnimationFrame(function () {
    lines.forEach(function (line) {
      var idx = Math.abs(parseInt(line.getAttribute('data-index'), 10));
      var delay = idx * STAGGER_MS;
      line.style.transition = 'stroke-dashoffset ' + REVEAL_MS + 'ms ease-out ' + delay + 'ms';
      line.style.strokeDashoffset = '0';
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
