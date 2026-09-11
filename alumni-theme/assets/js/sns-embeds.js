(function () {
  'use strict';

  function roots() {
    return document.querySelectorAll('.alumni-homepage-sns-window-sns_x');
  }

  function render() {
    if (!window.twttr || !window.twttr.widgets || typeof window.twttr.widgets.load !== 'function') {
      return false;
    }

    roots().forEach(function (root) {
      window.twttr.widgets.load(root);
    });

    return true;
  }

  function loadSdk(done) {
    if (window.twttr && window.twttr.widgets && typeof window.twttr.widgets.load === 'function') {
      done();
      return;
    }

    var existing = document.getElementById('alumni-x-widgets');
    if (existing) {
      existing.addEventListener('load', done, { once: true });
      return;
    }

    var script = document.createElement('script');
    script.id = 'alumni-x-widgets';
    script.async = true;
    script.src = 'https://platform.x.com/widgets.js';
    script.charset = 'utf-8';
    script.addEventListener('load', done, { once: true });
    document.head.appendChild(script);
  }

  function boot() {
    if (!roots().length) {
      return;
    }

    if (render()) {
      return;
    }

    loadSdk(function () {
      var attempts = 0;
      var timer = window.setInterval(function () {
        attempts += 1;
        if (render() || attempts >= 40) {
          window.clearInterval(timer);
        }
      }, 100);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }

  window.addEventListener('load', boot);
}());
