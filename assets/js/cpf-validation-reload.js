/**
 * Validação de CPF em tempo real, bloqueio de envio inválido e recarga após mensagem de sucesso.
 */
(function () {
  'use strict';

  function onlyDigits(s) {
    return String(s || '').replace(/\D/g, '');
  }

  function validarCpf(d) {
    if (d.length !== 11 || /^(\d)\1{10}$/.test(d)) return false;
    for (var t = 9; t < 11; t++) {
      var s = 0;
      for (var c = 0; c < t; c++) s += parseInt(d.charAt(c), 10) * (t + 1 - c);
      var r = ((10 * s) % 11) % 10;
      if (parseInt(d.charAt(t), 10) !== r) return false;
    }
    return true;
  }

  function maskCpf(el) {
    el.addEventListener('input', function () {
      var d = onlyDigits(el.value).slice(0, 11);
      var out = '';
      if (d.length > 0) out = d.slice(0, 3);
      if (d.length > 3) out += '.' + d.slice(3, 6);
      if (d.length > 6) out += '.' + d.slice(6, 9);
      if (d.length > 9) out += '-' + d.slice(9, 11);
      el.value = out;
      el.dispatchEvent(new Event('cpfchange', { bubbles: true }));
    });
  }

  function setFeedback(form, ok, msg) {
    var inp = form.querySelector('.js-cpf');
    var fb = form.querySelector('.js-cpf-feedback');
    if (!inp) return;
    inp.classList.toggle('is-invalid', !ok);
    inp.classList.toggle('is-valid', ok && onlyDigits(inp.value).length === 11);
    if (fb) fb.textContent = msg || '';
  }

  function bindCpfForms() {
    document.querySelectorAll('form.js-form-cpf').forEach(function (form) {
      var inp = form.querySelector('.js-cpf');
      if (!inp) return;
      maskCpf(inp);

      inp.addEventListener('cpfchange', function () {
        var d = onlyDigits(inp.value);
        if (d.length === 0) {
          setFeedback(form, true, '');
        } else if (d.length < 11) {
          setFeedback(form, false, 'CPF incompleto.');
        } else if (!validarCpf(d)) {
          setFeedback(form, false, 'CPF inválido.');
        } else {
          setFeedback(form, true, '');
        }
      });

      form.addEventListener('submit', function (e) {
        if (!inp) return;
        var d = onlyDigits(inp.value);
        if (d.length !== 11 || !validarCpf(d)) {
          e.preventDefault();
          setFeedback(form, false, d.length < 11 ? 'Informe o CPF completo.' : 'CPF inválido.');
        }
      });
    });
  }

  function bindFlashReload() {
    document.querySelectorAll('.js-flash-reload').forEach(function (el) {
      var ms = parseInt(el.getAttribute('data-reload-ms') || '0', 10);
      if (!ms || ms < 500) return;
      setTimeout(function () {
        window.location.href = window.location.pathname;
      }, ms);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindCpfForms();
    bindFlashReload();
  });
})();
