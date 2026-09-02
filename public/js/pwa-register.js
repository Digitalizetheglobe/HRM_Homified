(function () {
  if (!('serviceWorker' in navigator)) {
    return;
  }

  window.addEventListener('load', function () {
    var meta = document.querySelector('meta[name="app-base"]');
    var base = (meta && meta.getAttribute('content') ? meta.getAttribute('content') : '').replace(/\/$/, '');
    var swUrl = (base || '') + '/serviceworker.js?v=4';

    navigator.serviceWorker.register(swUrl).then(function (registration) {
      if (registration.waiting) {
        registration.waiting.postMessage({ type: 'SKIP_WAITING' });
      }
      registration.addEventListener('updatefound', function () {
        var installing = registration.installing;
        if (!installing) return;
        installing.addEventListener('statechange', function () {
          if (installing.state === 'installed') {
            installing.postMessage({ type: 'SKIP_WAITING' });
          }
        });
      });
    }).catch(function () {});
  });
})();
