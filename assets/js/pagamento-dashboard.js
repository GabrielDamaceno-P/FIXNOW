/**
 * Modal de pagamento (PIX simulado): copiar código e feedback visual.
 */
(function () {
  const modalEl = document.getElementById('modalPagamento');
  if (!modalEl || typeof bootstrap === 'undefined') return;

  const modal = new bootstrap.Modal(modalEl);
  const pixInput = document.getElementById('pix-copia-cola');
  const btnCopy = document.getElementById('btn-copiar-pix');
  const toastEl = document.getElementById('toastPagamento');
  const formConfirmar = document.getElementById('form-confirmar-pagamento');

  document.querySelectorAll('[data-bs-open-pagamento]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = btn.getAttribute('data-pagamento-id');
      const valor = btn.getAttribute('data-valor') || '';
      const chamado = btn.getAttribute('data-chamado-id') || '';
      if (pixInput) {
        const fake =
          '00020126580014BR.GOV.BCB.PIX0136' +
          chamado +
          '520400005303986540' +
          valor.replace(/\D/g, '') +
          '5802BR5925FIX NOW6009SAO PAULO62070503***6304ABCD';
        pixInput.value = fake;
      }
      if (formConfirmar) {
        const hid = formConfirmar.querySelector('input[name="confirmar_pagamento_id"]');
        if (hid) hid.value = id || '';
      }
      const sub = modalEl.querySelector('[data-pagamento-resumo]');
      if (sub) {
        sub.textContent = 'Chamado #' + chamado + ' — ' + (valor ? 'Valor: ' + valor : '');
      }
      modal.show();
    });
  });

  function showCopyToast(ok) {
    if (!toastEl || typeof bootstrap === 'undefined') return;
    var t = new bootstrap.Toast(toastEl);
    toastEl.querySelector('.toast-body').textContent = ok
      ? 'Código PIX copiado.'
      : 'Selecione e copie manualmente (Ctrl+C).';
    t.show();
  }

  if (btnCopy && pixInput) {
    btnCopy.addEventListener('click', function () {
      var text = pixInput.value;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          showCopyToast(true);
        }).catch(function () {
          fallbackCopy();
        });
      } else {
        fallbackCopy();
      }

      function fallbackCopy() {
        pixInput.select();
        pixInput.setSelectionRange(0, 99999);
        var ok = false;
        try {
          ok = document.execCommand('copy');
        } catch (e) {
          ok = false;
        }
        showCopyToast(ok);
      }
    });
  }
})();
