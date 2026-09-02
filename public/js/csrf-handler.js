(function () {
  'use strict';

  function appUrl(path) {
    var meta = document.querySelector('meta[name="app-base"]');
    var base = meta && meta.getAttribute('content') ? meta.getAttribute('content') : '';
    base = base.replace(/\/$/, '');
    if (path.charAt(0) !== '/') {
      path = '/' + path;
    }
    return base + path;
  }

  var csrfUrl = appUrl('/csrf-token');

  function metaEl() {
    return document.querySelector('meta[name="csrf-token"]');
  }

  function getToken() {
    var el = metaEl();
    return el ? el.getAttribute('content') : '';
  }

  function setToken(token) {
    if (!token) return;
    var el = metaEl();
    if (el) {
      el.setAttribute('content', token);
    } else {
      el = document.createElement('meta');
      el.setAttribute('name', 'csrf-token');
      el.setAttribute('content', token);
      document.head.appendChild(el);
    }
    document.querySelectorAll('input[name="_token"]').forEach(function (input) {
      input.value = token;
    });
    if (window.jQuery) {
      window.jQuery.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': token }
      });
    }
  }

  function refreshToken() {
    return fetch(csrfUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      cache: 'no-store'
    })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (data) {
        if (data && data.token) {
          setToken(data.token);
          return data.token;
        }
        return getToken();
      })
      .catch(function () { return getToken(); });
  }

  function applyTokenToBody(body, token) {
    if (!body || !token) return body;
    if (typeof FormData !== 'undefined' && body instanceof FormData) {
      body.set('_token', token);
      return body;
    }
    if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) {
      body.set('_token', token);
      return body;
    }
    if (typeof body === 'string' && body.indexOf('_token=') !== -1) {
      return body.replace(/_token=[^&]*/, '_token=' + encodeURIComponent(token));
    }
    return body;
  }

  if (window.fetch) {
    var originalFetch = window.fetch;
    window.fetch = function (input, init) {
      init = init || {};
      var headers = new Headers(init.headers || {});
      var method = (init.method || (input && input.method) || 'GET').toUpperCase();
      var token = getToken();
      var isMutating = method !== 'GET' && method !== 'HEAD';
      if (isMutating) {
        if (token && !headers.has('X-CSRF-TOKEN')) {
          headers.set('X-CSRF-TOKEN', token);
        }
        if (!headers.has('X-Requested-With')) {
          headers.set('X-Requested-With', 'XMLHttpRequest');
        }
        init.body = applyTokenToBody(init.body, token);
      }
      init.headers = headers;
      init.credentials = init.credentials || 'same-origin';

      return originalFetch.call(this, input, init).then(function (response) {
        if (response.status !== 419 || init._csrfRetried || !isMutating) {
          return response;
        }
        init._csrfRetried = true;
        return refreshToken().then(function (fresh) {
          var retryHeaders = new Headers(init.headers || {});
          retryHeaders.set('X-CSRF-TOKEN', fresh);
          retryHeaders.set('X-Requested-With', 'XMLHttpRequest');
          init.headers = retryHeaders;
          init.body = applyTokenToBody(init.body, fresh);
          return originalFetch.call(window, input, init);
        });
      });
    };
  }

  function bindJQuery() {
    var $ = window.jQuery;
    if (!$ || $.csrfHandlerBound) return;
    $.csrfHandlerBound = true;

    $.ajaxSetup({
      headers: { 'X-CSRF-TOKEN': getToken() }
    });

    $(document).ajaxSend(function (event, jqXHR, settings) {
      var token = getToken();
      if (token) {
        jqXHR.setRequestHeader('X-CSRF-TOKEN', token);
      }
      if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('_token=') !== -1) {
        settings.data = settings.data.replace(/_token=[^&]*/, '_token=' + encodeURIComponent(token));
      } else if (settings.data && typeof settings.data === 'object' && !(settings.data instanceof FormData) && settings.data._token) {
        settings.data._token = token;
      } else if (typeof FormData !== 'undefined' && settings.data instanceof FormData) {
        settings.data.set('_token', token);
      }
    });

    $(document).ajaxError(function (event, xhr, settings) {
      if (!xhr || xhr.status !== 419 || settings._csrfRetried) {
        return;
      }
      settings._csrfRetried = true;
      event.preventDefault();
      refreshToken().then(function (token) {
        settings.headers = settings.headers || {};
        settings.headers['X-CSRF-TOKEN'] = token;
        if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('_token=') !== -1) {
          settings.data = settings.data.replace(/_token=[^&]*/, '_token=' + encodeURIComponent(token));
        } else if (settings.data && typeof settings.data === 'object' && !(settings.data instanceof FormData) && settings.data._token) {
          settings.data._token = token;
        } else if (typeof FormData !== 'undefined' && settings.data instanceof FormData) {
          settings.data.set('_token', token);
        }
        $.ajax(settings);
      });
    });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') return;
    var method = (form.getAttribute('method') || 'GET').toUpperCase();
    if (method === 'GET') return;
    var token = getToken();
    if (!token) return;
    var input = form.querySelector('input[name="_token"]');
    if (input) {
      input.value = token;
    }
  }, true);

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      refreshToken();
    }
  });

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      refreshToken();
    }
  });

  setInterval(refreshToken, 10 * 60 * 1000);

  if (window.jQuery) {
    bindJQuery();
  } else {
    window.addEventListener('load', bindJQuery);
  }
})();
