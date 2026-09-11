(function () {
  'use strict';

  function loadXWidgets() {
    if (!window.twttr || !window.twttr.widgets || typeof window.twttr.widgets.load !== 'function') {
      return false;
    }

    var roots = document.querySelectorAll('.alumni-homepage-sns-window-sns_x');
    roots.forEach(function (root) {
      window.twttr.widgets.load(root);
    });

    return true;
  }

  function boot() {
    if (loadXWidgets()) {
      return;
    }

    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts += 1;
      if (loadXWidgets() || attempts >= 40) {
        window.clearInterval(timer);
      }
    }, 250);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.addEventListener('load', loadXWidgets);
}());
