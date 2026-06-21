if (typeof localStorage !== 'undefined' && localStorage.getItem('fn-theme') === 'dark') {
  document.documentElement.setAttribute('data-theme', 'dark');
}

(function () {
  var tag = document.querySelector('script[src*="main.js"]');
  if (!tag) return;
  var base = tag.src.replace(/\/js\/main\.js[\s\S]*$/, '/img/perfil/');

  function aplicarFallback(img) {
    if (!img || img.dataset.fnFallback) return;
    img.dataset.fnFallback = '1';
    var isCli = (img.src || '').indexOf('perfil_cli') !== -1;
    img.src = base + (isCli ? 'default-cliente.svg' : 'default-prestador.svg');
  }

  document.addEventListener('error', function (e) {
    if (e.target && e.target.tagName === 'IMG') aplicarFallback(e.target);
  }, true);

  function varrerImagens() {
    document.querySelectorAll('img[src]').forEach(function (img) {
      if (img.complete && img.naturalWidth === 0 && img.src && img.src !== window.location.href) {
        aplicarFallback(img);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', varrerImagens);
  } else {
    varrerImagens();
  }

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) varrerImagens();
  });
})();

(function () {
  'use strict';

  var SVG_MOON = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>';
  var SVG_SUN  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';

  function autoDismissAlerts() {
    document.querySelectorAll('main .alert:not(.alert-permanent)').forEach(function (el) {
      if (el.dataset.noAutoDismiss === '1') return;
      var ms = parseInt(el.dataset.dismissAfter || '7000', 10);
      setTimeout(function () {
        el.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px)';
        setTimeout(function () {
          el.remove();
        }, 480);
      }, ms);
    });
  }

  function highlightNav() {
    var path = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.navbar-nav .nav-link[href]').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href || href === '#') return;
      var file = href.split('/').pop().split('?')[0];
      if (file === path) {
        a.classList.add('active');
      } else {
        a.classList.remove('active');
      }
    });
  }

  function initTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });
  }

  function initLiveUpdate() {
    var intervalMs = parseInt(document.body.getAttribute('data-live-update-interval') || '0', 10);
    if (!intervalMs || intervalMs < 3000) return;
    setInterval(function () {
      var active = document.activeElement;
      var editing =
        active &&
        (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT');
      var modalOpen = document.querySelector('.modal.show');
      if (!editing && !modalOpen && !document.hidden) {
        window.location.reload();
      }
    }, intervalMs);
  }

  function initDarkMode() {
    var btn = document.createElement('button');
    btn.id = 'fn-dark-toggle';
    btn.setAttribute('aria-label', 'Alternar modo escuro');
    btn.title = 'Alternar modo escuro';

    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    btn.innerHTML = isDark ? SVG_SUN : SVG_MOON;

    document.body.appendChild(btn);

    btn.addEventListener('click', function () {
      var dark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (dark) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('fn-theme', 'light');
        btn.innerHTML = SVG_MOON;
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('fn-theme', 'dark');
        btn.innerHTML = SVG_SUN;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    autoDismissAlerts();
    highlightNav();
    initTooltips();
    initLiveUpdate();
    initDarkMode();
  });
})();
