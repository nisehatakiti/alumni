(function () {
  'use strict';

  function roots() {
    return document.querySelectorAll('.alumni-homepage-sns-window-sns_x');
  }

  function renderAll() {
    if (!window.twttr || !window.twttr.widgets || typeof window.twttr.widgets.load !== 'function') {
      return false;
    }

    roots().forEach(function (root) {
      window.twttr.widgets.load(root);
    });

    return true;
  }

  function waitForWidgets(attempt) {
    if (renderAll()) {
      return;
    }

    // widgets.js is an external SDK and can finish after the dependent
    // WordPress footer script. Retry briefly instead of giving up on the
    // first missing window.twttr check.
    if (attempt < 40) {
      window.setTimeout(function () {
        waitForWidgets(attempt + 1);
      }, 250);
    }
  }

  function boot() {
    if (window.twttr && typeof window.twttr.ready === 'function') {
      window.twttr.ready(function () {
        if (!renderAll()) {
          waitForWidgets(0);
        }
      });
    } else {
      waitForWidgets(0);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
}());
