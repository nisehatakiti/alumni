(function () {
  'use strict';

  function roots() {
    return document.querySelectorAll('.alumni-homepage-sns-window-sns_x');
  }

  function render(root) {
    if (!window.twttr || !window.twttr.widgets || typeof window.twttr.widgets.load !== 'function') {
      return;
    }

    window.twttr.widgets.load(root);
  }

  function boot() {
    roots().forEach(function (root) {
      if (window.twttr && typeof window.twttr.ready === 'function') {
        window.twttr.ready(function () {
          render(root);
        });
      } else {
        render(root);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
}());
