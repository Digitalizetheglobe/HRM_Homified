(function (window, document) {
  var bar = null;

  function ensure() {
    if (bar) return bar;
    bar = document.createElement('div');
    bar.id = 'nprogress';
    bar.style.cssText = 'position:fixed;top:0;left:0;height:2px;width:0;background:#ea3538;z-index:1031;transition:width .2s ease;';
    document.body.appendChild(bar);
    return bar;
  }

  window.NProgress = {
    configure: function () { return this; },
    start: function () {
      ensure().style.width = '35%';
      return this;
    },
    done: function () {
      if (!bar) return this;
      bar.style.width = '100%';
      setTimeout(function () {
        if (bar && bar.parentNode) {
          bar.parentNode.removeChild(bar);
        }
        bar = null;
      }, 200);
      return this;
    },
    remove: function () {
      if (bar && bar.parentNode) {
        bar.parentNode.removeChild(bar);
      }
      bar = null;
    }
  };
})(window, document);
