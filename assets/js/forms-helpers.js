/**
 * Formulários: mostrar/ocultar senha, máscaras BR, evitar envio duplo.
 */
(function () {
  'use strict';

  function bindPasswordToggle() {
    document.querySelectorAll('[data-action="toggle-password"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var group = btn.closest('.input-group');
        var inp = group ? group.querySelector('input[type="password"], input[type="text"]') : null;
        if (!inp || (inp.name !== 'senha' && inp.name !== 'confirmar_senha')) return;
        if (inp.type === 'password') {
          inp.type = 'text';
          btn.textContent = 'Ocultar';
          btn.setAttribute('aria-label', 'Ocultar senha');
        } else {
          inp.type = 'password';
          btn.textContent = 'Mostrar';
          btn.setAttribute('aria-label', 'Mostrar senha');
        }
      });
    });
  }

  function bindConfirmarSenha() {
    var senha = document.getElementById('campo-senha');
    var confirmar = document.getElementById('campo-confirmar-senha');
    if (!senha || !confirmar) return;
    var feedback = confirmar.closest('.col-md-6')
      ? confirmar.closest('.col-md-6').querySelector('.js-senha-feedback')
      : null;

    function validar() {
      if (confirmar.value === '') {
        confirmar.classList.remove('is-valid', 'is-invalid');
        if (feedback) feedback.textContent = '';
        return;
      }
      if (senha.value !== confirmar.value) {
        confirmar.classList.add('is-invalid');
        confirmar.classList.remove('is-valid');
        if (feedback) feedback.textContent = 'As senhas não conferem.';
      } else {
        confirmar.classList.add('is-valid');
        confirmar.classList.remove('is-invalid');
        if (feedback) feedback.textContent = '';
      }
    }

    confirmar.addEventListener('input', validar);
    senha.addEventListener('input', validar);
  }

  function onlyDigits(s) {
    return (s || '').replace(/\D/g, '');
  }

  function maskTelefone(el) {
    el.addEventListener('input', function () {
      var d = onlyDigits(el.value).slice(0, 11);
      var out = '';
      if (d.length > 0) out = '(' + d.slice(0, 2);
      if (d.length >= 2) out += ') ';
      if (d.length > 2) out += d.slice(2, d.length > 10 ? 7 : 6);
      if (d.length > 10) out += '-' + d.slice(7, 11);
      else if (d.length > 6) out += '-' + d.slice(6, 10);
      el.value = out;
    });
  }

  function maskCep(el) {
    el.addEventListener('input', function () {
      var d = onlyDigits(el.value).slice(0, 8);
      el.value = d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
    });
  }

  function bindMasks() {
    document.querySelectorAll('input[name="telefone"].js-mask').forEach(maskTelefone);
    document.querySelectorAll('input[name="cep"].js-mask:not(#js-cep)').forEach(maskCep);
  }

  function bindCepAutocomplete() {
    var cepInput = document.getElementById('js-cep');
    if (!cepInput) return;

    var status = document.querySelector('.js-cep-status');
    var fields = {
      logradouro: document.getElementById('js-logradouro'),
      bairro: document.getElementById('js-bairro'),
      cidade: document.getElementById('js-cidade'),
      estado: document.getElementById('js-estado')
    };

    function setStatus(msg, isError) {
      if (!status) return;
      status.textContent = msg;
      status.className = 'small mt-1 js-cep-status ' + (isError ? 'text-danger' : 'text-muted');
    }

    function preencherCampos(data) {
      if (fields.logradouro) fields.logradouro.value = data.logradouro || '';
      if (fields.bairro) fields.bairro.value = data.bairro || '';
      if (fields.cidade) fields.cidade.value = data.localidade || '';
      if (fields.estado) fields.estado.value = data.uf || '';

      var vazio = null;
      var keys = ['logradouro', 'bairro', 'cidade', 'estado'];
      for (var i = 0; i < keys.length; i++) {
        var f = fields[keys[i]];
        if (f && !f.value) { vazio = f; break; }
      }
      if (vazio) vazio.focus();
    }

    function limparCampos() {
      var keys = ['logradouro', 'bairro', 'cidade', 'estado'];
      for (var i = 0; i < keys.length; i++) {
        if (fields[keys[i]]) fields[keys[i]].value = '';
      }
    }

    cepInput.addEventListener('input', function () {
      var d = onlyDigits(cepInput.value).slice(0, 8);
      cepInput.value = d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;

      if (d.length < 8) {
        limparCampos();
        setStatus('', false);
        return;
      }

      setStatus('Buscando endereço...', false);

      fetch('https://viacep.com.br/ws/' + d + '/json/')
        .then(function (r) {
          if (!r.ok) throw new Error('network');
          return r.json();
        })
        .then(function (data) {
          if (data.erro) {
            limparCampos();
            setStatus('CEP não encontrado.', true);
            return;
          }
          preencherCampos(data);
          setStatus('', false);
        })
        .catch(function () {
          setStatus('Erro ao buscar CEP. Preencha manualmente.', true);
        });
    });
  }

  function bindGuardSubmit() {
    document.querySelectorAll('form.js-guard-submit').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (e.defaultPrevented) {
          return;
        }
        if (form.dataset.submitted === '1') {
          e.preventDefault();
          return;
        }
        form.dataset.submitted = '1';
        var btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (btn) {
          btn.disabled = true;
          var spin = document.createElement('span');
          spin.className = 'spinner-border spinner-border-sm ms-2';
          spin.setAttribute('role', 'status');
          spin.setAttribute('aria-hidden', 'true');
          if (!btn.querySelector('.spinner-border')) btn.appendChild(spin);
        }
      });
    });
  }

  function bindEmailTrim() {
    document.querySelectorAll('form input[type="email"]').forEach(function (inp) {
      inp.addEventListener('blur', function () {
        inp.value = inp.value.trim();
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindPasswordToggle();
    bindMasks();
    bindGuardSubmit();
    bindEmailTrim();
    bindConfirmarSenha();
    bindCepAutocomplete();
  });
})();
