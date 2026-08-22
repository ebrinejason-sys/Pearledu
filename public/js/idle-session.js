(function () {
  var lifetime = parseInt(document.querySelector('meta[name="idle-lifetime"]') && document.querySelector('meta[name="idle-lifetime"]').content, 10);
  var warning = parseInt(document.querySelector('meta[name="idle-warning"]') && document.querySelector('meta[name="idle-warning"]').content, 10);
  var heartbeatUrl = document.querySelector('meta[name="idle-heartbeat"]') && document.querySelector('meta[name="idle-heartbeat"]').content;
  var loginUrl = document.querySelector('meta[name="idle-login"]') && document.querySelector('meta[name="idle-login"]').content;
  var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
  if (!lifetime || !heartbeatUrl || !loginUrl) {
    return;
  }
  warning = warning > 0 ? warning : 120;

  var lastActivity = Date.now();
  var lastHeartbeat = 0;
  var signingOut = false;
  var dialog = document.getElementById('idle-session-dialog');
  var countdown = document.getElementById('idle-session-countdown');
  var stayBtn = document.getElementById('idle-session-stay');
  var leaveBtn = document.getElementById('idle-session-leave');

  function remaining() {
    return Math.max(0, lifetime * 1000 - (Date.now() - lastActivity));
  }

  function token() {
    return (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || csrf;
  }

  function post(url) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: '{}'
    });
  }

  function heartbeat() {
    var now = Date.now();
    if (now - lastHeartbeat < 20000) {
      return Promise.resolve(true);
    }
    lastHeartbeat = now;
    return post(heartbeatUrl).then(function (res) {
      if (res.status === 401) {
        signOut(true);
        return false;
      }
      return res.ok;
    }).catch(function () {
      return false;
    });
  }

  function signOut(alreadyExpired) {
    if (signingOut) {
      return;
    }
    signingOut = true;
    var form = document.getElementById('idle-session-logout');
    if (form && !alreadyExpired) {
      form.submit();
      return;
    }
    window.location.href = loginUrl;
  }

  function showWarning(msLeft) {
    if (!dialog) {
      return;
    }
    dialog.hidden = false;
    dialog.setAttribute('open', '');
    if (countdown) {
      countdown.textContent = String(Math.max(1, Math.ceil(msLeft / 1000)));
    }
  }

  function hideWarning() {
    if (!dialog) {
      return;
    }
    dialog.hidden = true;
    dialog.removeAttribute('open');
  }

  function noteActivity() {
    lastActivity = Date.now();
    hideWarning();
    heartbeat();
  }

  ['pointerdown', 'keydown', 'click', 'touchstart'].forEach(function (evt) {
    document.addEventListener(evt, function () {
      if (dialog && !dialog.hidden && evt === 'keydown') {
        return;
      }
      noteActivity();
    }, { passive: true });
  });

  if (stayBtn) {
    stayBtn.addEventListener('click', function () {
      noteActivity();
    });
  }
  if (leaveBtn) {
    leaveBtn.addEventListener('click', function () {
      signOut(false);
    });
  }

  setInterval(function () {
    if (signingOut || document.hidden) {
      return;
    }
    var left = remaining();
    if (left <= 0) {
      signOut(false);
      return;
    }
    if (left <= warning * 1000) {
      showWarning(left);
    }
  }, 1000);
})();
