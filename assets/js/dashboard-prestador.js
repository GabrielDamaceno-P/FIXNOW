/**
 * Painel prestador: confirmação ao aceitar chamado.
 */
(function () {
  'use strict';

  function fallbackSvg(label) {
    var safe = encodeURIComponent(label || 'Sem foto');
    return (
      'data:image/svg+xml;utf8,' +
      '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="700">' +
      '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">' +
      '<stop offset="0%" stop-color="%23edf2ff"/><stop offset="100%" stop-color="%23dbe7ff"/>' +
      '</linearGradient></defs>' +
      '<rect width="100%" height="100%" fill="url(%23g)"/>' +
      '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23677489" font-family="Arial" font-size="28">' +
      safe +
      '</text></svg>'
    );
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.js-confirm-aceitar').forEach(function (form) {
      form.addEventListener(
        'submit',
        function (e) {
          if (!window.confirm('Deseja aceitar este chamado e ficar responsável pelo atendimento?')) {
            e.preventDefault();
            e.stopImmediatePropagation();
          }
        },
        true
      );
    });

    document.querySelectorAll('form.js-confirm-negar').forEach(function (form) {
      form.addEventListener(
        'submit',
        function (e) {
          if (!window.confirm('Deseja negar este serviço? O chamado será encerrado e o cliente notificado.')) {
            e.preventDefault();
            e.stopImmediatePropagation();
          }
        },
        true
      );
    });

    var modalEl = document.getElementById('modalClientePerfil');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    var modal = new bootstrap.Modal(modalEl);
    var foto = modalEl.querySelector('[data-cliente-modal-foto]');
    var nome = modalEl.querySelector('[data-cliente-modal-nome]');
    var tel = modalEl.querySelector('[data-cliente-modal-tel]');
    var end = modalEl.querySelector('[data-cliente-modal-end]');
    var resumo = modalEl.querySelector('[data-cliente-modal-resumo]');
    var problema = modalEl.querySelector('[data-cliente-modal-problema]');

    document.querySelectorAll('.js-open-cliente-perfil').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var clienteFoto = btn.getAttribute('data-cliente-foto') || '';
        var problemaFoto = btn.getAttribute('data-problema-foto') || '';
        foto.src = clienteFoto ? '../../' + clienteFoto : fallbackSvg('Sem foto do cliente');
        problema.src = problemaFoto ? '../../' + problemaFoto : fallbackSvg('Sem foto do problema');
        nome.textContent = btn.getAttribute('data-cliente-nome') || 'Cliente';
        tel.textContent = btn.getAttribute('data-cliente-telefone') || 'Não informado';
        end.textContent = btn.getAttribute('data-cliente-endereco') || 'Não informado';
        resumo.textContent = btn.getAttribute('data-resumo') || 'Sem resumo informado.';
        foto.onerror = function () {
          foto.src = fallbackSvg('Foto indisponível');
        };
        problema.onerror = function () {
          problema.src = fallbackSvg('Imagem indisponível');
        };
        modal.show();
      });
    });
  });
})();
