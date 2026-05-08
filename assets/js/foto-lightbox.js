/**
 * Modal simples para visualizar fotos (perfil, chamado, etc.).
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('modalFotoFixnow');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    var modal = new bootstrap.Modal(modalEl);
    var img = modalEl.querySelector('[data-fn-foto-img]');
    var titulo = modalEl.querySelector('[data-fn-foto-titulo]');

    document.querySelectorAll('.js-open-foto').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var src = btn.getAttribute('data-foto') || '';
        var lab = btn.getAttribute('data-titulo') || 'Foto';
        if (!src || !img) return;
        img.src = src;
        img.alt = lab;
        if (titulo) titulo.textContent = lab;
        modal.show();
      });
    });
  });
})();
