/**
 * Solicitar serviço: prévia da foto, contador e opção prestadoras.
 */
(function () {
  'use strict';

  const wrapPrestadoras = document.getElementById('wrap-prestadora-mulher');
  const chkPrestadoras = document.getElementById('chk-prestadora-mulher');

  function bindFotoPreview() {
    const input = document.getElementById('foto-input');
    const box   = document.getElementById('foto-preview-box');
    const cont  = document.getElementById('foto-preview-imgs');
    if (!input || !box || !cont) return;
    input.addEventListener('change', function () {
      cont.innerHTML = '';
      if (!input.files || input.files.length === 0) {
        box.classList.add('d-none');
        return;
      }
      box.classList.remove('d-none');
      Array.from(input.files).slice(0, 6).forEach(function (f) {
        if (!f.type.match(/^image\//)) return;
        const url = URL.createObjectURL(f);
        const img = document.createElement('img');
        img.src = url;
        img.className = 'img-thumbnail';
        img.style.cssText = 'max-height:100px;max-width:120px;object-fit:cover;';
        img.onload = function () { URL.revokeObjectURL(url); };
        cont.appendChild(img);
      });
    });
  }

  function bindContadorDescricao() {
    const ta = document.querySelector('textarea[name="descricao"]');
    const out = document.getElementById('descricao-contador');
    if (!ta || !out) return;
    const max = 2000;
    function atualizar() {
      const n = ta.value.length;
      out.textContent = n + ' / ' + max + ' caracteres';
      if (n > max) {
        out.classList.add('text-danger');
      } else {
        out.classList.remove('text-danger');
      }
    }
    ta.setAttribute('maxlength', String(max));
    ta.addEventListener('input', atualizar);
    atualizar();
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (wrapPrestadoras && chkPrestadoras) {
      const genero = (document.body.dataset.clienteGenero || '').trim();
      if (genero !== 'Feminino') {
        wrapPrestadoras.classList.add('d-none');
        chkPrestadoras.checked = false;
        chkPrestadoras.disabled = true;
      }
    }

    bindFotoPreview();
    bindContadorDescricao();
  });
})();
