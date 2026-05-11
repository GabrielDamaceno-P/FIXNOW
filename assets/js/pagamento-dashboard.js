/**
 * Modal de pagamento simulado — PIX, Cartão, Dinheiro.
 */
(function () {
  var modalEl = document.getElementById('modalPagamento');
  if (!modalEl || typeof bootstrap === 'undefined') return;

  var modal         = new bootstrap.Modal(modalEl);
  var pixInput      = document.getElementById('pix-copia-cola');
  var btnCopy       = document.getElementById('btn-copiar-pix');
  var formConfirmar = document.getElementById('form-confirmar-pagamento');
  var metodHidden   = document.getElementById('pag-metodo-hidden');
  var valorDisplay  = document.getElementById('pag-valor-display');
  var chamadoRef    = document.getElementById('pag-chamado-ref');
  var qrContainer   = document.getElementById('pix-qrcode');

  var pendingPixCode = '';

  // Sincroniza aba selecionada com campo hidden
  modalEl.querySelectorAll('#pag-tabs [data-bs-toggle="pill"]').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function () {
      var map = { 'tab-pix': 'PIX', 'tab-cartao': 'Cartão', 'tab-dinheiro': 'Dinheiro' };
      if (metodHidden) metodHidden.value = map[btn.getAttribute('data-bs-target').slice(1)] || 'PIX';
    });
  });

  // Abre modal ao clicar em "Pagar"
  document.querySelectorAll('[data-bs-open-pagamento]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id      = this.getAttribute('data-pagamento-id') || '';
      var valor   = this.getAttribute('data-valor') || '';
      var chamado = this.getAttribute('data-chamado-id') || '';

      if (valorDisplay) valorDisplay.textContent = valor || 'R$ —';
      if (chamadoRef)   chamadoRef.textContent   = '#' + chamado;

      var cents = valor.replace(/\D/g, '') || '000';
      pendingPixCode =
        '00020126580014BR.GOV.BCB.PIX0136' + chamado.padStart(8, '0') +
        '52040000530398654' + cents.length + cents +
        '5802BR5925FIX NOW6009SAO PAULO62070503***6304' +
        Math.random().toString(16).slice(2, 6).toUpperCase();

      if (pixInput) pixInput.value = pendingPixCode;

      if (formConfirmar) {
        var hid = formConfirmar.querySelector('input[name="confirmar_pagamento_id"]');
        if (hid) hid.value = id;
      }

      // Reseta para aba PIX
      var pixTab = document.getElementById('tab-pix-btn');
      if (pixTab) bootstrap.Tab.getOrCreateInstance(pixTab).show();
      if (metodHidden) metodHidden.value = 'PIX';

      // Limpa QR anterior enquanto abre
      if (qrContainer) qrContainer.innerHTML = '';

      modal.show();
    });
  });

  // Gera QR Code após o modal estar completamente visível
  modalEl.addEventListener('shown.bs.modal', function () {
    if (!qrContainer || typeof QRCode === 'undefined' || !pendingPixCode) return;
    qrContainer.innerHTML = '';
    new QRCode(qrContainer, {
      text: pendingPixCode,
      width: 148,
      height: 148,
      colorDark: '#111111',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });
  });

  // Copiar código PIX
  if (btnCopy && pixInput) {
    btnCopy.addEventListener('click', function () {
      var text = pixInput.value;

      function feedback() {
        btnCopy.classList.replace('btn-outline-secondary', 'btn-success');
        if (btnCopy.querySelector('i')) btnCopy.querySelector('i').className = 'bi bi-check';
        setTimeout(function () {
          btnCopy.classList.replace('btn-success', 'btn-outline-secondary');
          if (btnCopy.querySelector('i')) btnCopy.querySelector('i').className = 'bi bi-clipboard';
        }, 1800);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(feedback).catch(function () {
          pixInput.select(); try { document.execCommand('copy'); feedback(); } catch(e) {}
        });
      } else {
        pixInput.select(); pixInput.setSelectionRange(0, 99999);
        try { document.execCommand('copy'); feedback(); } catch(e) {}
      }
    });
  }

  // Máscara número do cartão
  var cartaoNum = document.getElementById('cartao-numero');
  if (cartaoNum) {
    cartaoNum.addEventListener('input', function () {
      var v = cartaoNum.value.replace(/\D/g, '').slice(0, 16);
      cartaoNum.value = v.replace(/(.{4})/g, '$1 ').trim();
    });
  }

  // Máscara validade MM/AA
  var cartaoVal = document.getElementById('cartao-validade');
  if (cartaoVal) {
    cartaoVal.addEventListener('input', function () {
      var v = cartaoVal.value.replace(/\D/g, '').slice(0, 4);
      if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
      cartaoVal.value = v;
    });
  }

  // Nome em maiúsculas
  var cartaoNome = document.getElementById('cartao-nome');
  if (cartaoNome) {
    cartaoNome.addEventListener('input', function () {
      var pos = cartaoNome.selectionStart;
      cartaoNome.value = cartaoNome.value.toUpperCase();
      cartaoNome.setSelectionRange(pos, pos);
    });
  }
})();
