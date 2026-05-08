/**
 * Painel admin: filtro nas tabelas e confirmação ao alterar status do chamado.
 */
(function () {
  'use strict';

  function filterTable(input) {
    var sel = input.getAttribute('data-filter-target');
    if (!sel) return;
    var table = document.querySelector(sel);
    if (!table) return;
    var q = input.value.toLowerCase().trim();
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      var text = tr.textContent.toLowerCase();
      tr.style.display = !q || text.indexOf(q) !== -1 ? '' : 'none';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-admin-filter').forEach(function (inp) {
      inp.addEventListener('input', function () {
        filterTable(inp);
      });
    });

    document.querySelectorAll('form.js-admin-status-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        var sel = form.querySelector('select[name="status"]');
        var msg = sel ? 'Alterar status do chamado para "' + sel.value + '"?' : 'Salvar alteração?';
        if (!window.confirm(msg)) {
          e.preventDefault();
        }
      });
    });
  });
})();
