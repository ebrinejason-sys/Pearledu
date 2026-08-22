(function () {
  var userMeta = document.querySelector('meta[name="offline-user"]');
  var schoolMeta = document.querySelector('meta[name="offline-school"]');
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var loginMeta = document.querySelector('meta[name="idle-login"]');
  var userId = userMeta && userMeta.content ? userMeta.content : '';
  var schoolId = schoolMeta && schoolMeta.content ? schoolMeta.content : '';
  var loginUrl = loginMeta && loginMeta.content ? loginMeta.content : '/login';
  var dbName = 'pearledu-offline';
  var storeName = 'outbox';
  var flushing = false;

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
  }

  var banner = document.getElementById('offline-banner');

  function token() {
    return (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content)
      || (csrfMeta && csrfMeta.content)
      || '';
  }

  function setBanner(state, text) {
    if (!banner) {
      return;
    }
    if (!state) {
      banner.hidden = true;
      banner.removeAttribute('data-state');
      banner.textContent = '';
      return;
    }
    banner.hidden = false;
    banner.setAttribute('data-state', state);
    banner.textContent = text;
  }

  function refreshBanner(pending) {
    if (!navigator.onLine) {
      var extra = pending > 0 ? ' ' + pending + ' attendance/marks save(s) will sync when you are back online.' : ' Open Attendance or Marks once while online so this device can reuse the last roster.';
      setBanner('offline', 'You are offline.' + extra);
      return;
    }
    if (pending > 0) {
      setBanner('queued', pending + ' saved record(s) waiting to sync.');
      return;
    }
    setBanner(null);
  }

  function openDb() {
    return new Promise(function (resolve, reject) {
      if (!window.indexedDB) {
        reject(new Error('IndexedDB unavailable'));
        return;
      }
      var req = indexedDB.open(dbName, 1);
      req.onupgradeneeded = function () {
        var db = req.result;
        if (!db.objectStoreNames.contains(storeName)) {
          db.createObjectStore(storeName, { keyPath: 'id', autoIncrement: true });
        }
      };
      req.onsuccess = function () { resolve(req.result); };
      req.onerror = function () { reject(req.error); };
    });
  }

  function withStore(mode, fn) {
    return openDb().then(function (db) {
      return new Promise(function (resolve, reject) {
        var tx = db.transaction(storeName, mode);
        var store = tx.objectStore(storeName);
        var result = fn(store);
        tx.oncomplete = function () { resolve(result); };
        tx.onerror = function () { reject(tx.error); };
      });
    });
  }

  function allItems() {
    return openDb().then(function (db) {
      return new Promise(function (resolve, reject) {
        var tx = db.transaction(storeName, 'readonly');
        var req = tx.objectStore(storeName).getAll();
        req.onsuccess = function () { resolve(req.result || []); };
        req.onerror = function () { reject(req.error); };
      });
    });
  }

  function addItem(item) {
    return withStore('readwrite', function (store) {
      store.add(item);
    });
  }

  function removeItem(id) {
    return withStore('readwrite', function (store) {
      store.delete(id);
    });
  }

  function mine(items) {
    return items.filter(function (item) {
      return String(item.userId) === String(userId) && String(item.schoolId) === String(schoolId);
    });
  }

  function pendingCount() {
    if (!userId) {
      return Promise.resolve(0);
    }
    return allItems().then(function (items) { return mine(items).length; }).catch(function () { return 0; });
  }

  function serializeForm(form) {
    var data = new FormData(form);
    var params = new URLSearchParams();
    data.forEach(function (value, key) {
      params.append(key, value);
    });
    return params.toString();
  }

  function intercept(form) {
    form.addEventListener('submit', function (event) {
      if (!userId) {
        return;
      }
      event.preventDefault();
      var body = serializeForm(form);
      var action = form.getAttribute('action') || window.location.href;
      var item = {
        userId: userId,
        schoolId: schoolId,
        url: action,
        kind: form.getAttribute('data-offline-queue') || 'form',
        body: body,
        createdAt: Date.now()
      };

      function queued(message) {
        addItem(item).then(function () {
          return pendingCount();
        }).then(function (count) {
          if (!navigator.onLine) {
            refreshBanner(count);
          } else {
            setBanner('queued', message || 'Saved on this device. Syncing…');
            flush();
          }
        });
      }

      if (!navigator.onLine) {
        queued();
        return;
      }

      post(action, body).then(function (res) {
        if (res.ok || res.redirected) {
          window.location.reload();
          return;
        }
        if (res.status === 401 || res.status === 419) {
          queued('Sign in again to sync this save.');
          return;
        }
        queued('Could not reach the server. Saved on this device.');
      }).catch(function () {
        queued();
      });
    });
  }

  function post(url, body) {
    var params = new URLSearchParams(body);
    if (token()) {
      params.set('_token', token());
    }
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'text/html,application/json',
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        'X-CSRF-TOKEN': token(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: params.toString(),
      redirect: 'follow'
    });
  }

  function flush() {
    if (flushing || !navigator.onLine || !userId) {
      return Promise.resolve();
    }
    flushing = true;
    return allItems().then(function (items) {
      var queue = mine(items);
      var chain = Promise.resolve();
      queue.forEach(function (item) {
        chain = chain.then(function () {
          return post(item.url, item.body).then(function (res) {
            if (res.status === 401 || res.status === 419) {
              setBanner('error', 'Sign in to sync ' + queue.length + ' saved record(s).');
              window.setTimeout(function () {
                window.location.href = loginUrl;
              }, 1200);
              return Promise.reject(new Error('auth'));
            }
            if (res.ok || res.redirected || res.status === 302) {
              return removeItem(item.id);
            }
            return null;
          });
        });
      });
      return chain;
    }).then(function () {
      return pendingCount();
    }).then(function (count) {
      flushing = false;
      refreshBanner(count);
    }).catch(function () {
      flushing = false;
      pendingCount().then(refreshBanner);
    });
  }

  document.querySelectorAll('form[data-offline-queue]').forEach(intercept);

  window.addEventListener('online', function () {
    pendingCount().then(function (count) {
      refreshBanner(count);
      flush();
    });
  });
  window.addEventListener('offline', function () {
    pendingCount().then(refreshBanner);
  });

  pendingCount().then(function (count) {
    refreshBanner(count);
    if (navigator.onLine && count > 0) {
      flush();
    }
  });
})();
