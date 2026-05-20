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

    let files = [];

    function syncInput() {
      const dt = new DataTransfer();
      files.forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
    }

    function render() {
      cont.innerHTML = '';
      if (files.length === 0) { box.classList.add('d-none'); return; }
      box.classList.remove('d-none');
      files.forEach(function (f, idx) {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'position:relative;display:inline-block;';

        const url = URL.createObjectURL(f);
        const img = document.createElement('img');
        img.src = url;
        img.className = 'img-thumbnail';
        img.style.cssText = 'max-height:100px;max-width:120px;object-fit:cover;display:block;';
        img.onload = function () { URL.revokeObjectURL(url); };

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = '×';
        btn.title = 'Remover foto';
        btn.style.cssText = 'position:absolute;top:3px;right:3px;width:20px;height:20px;border-radius:50%;border:none;background:#dc3545;color:#fff;font-size:1rem;line-height:1;cursor:pointer;padding:0;display:flex;align-items:center;justify-content:center;';
        btn.addEventListener('click', function () {
          files.splice(idx, 1);
          syncInput();
          render();
        });

        wrap.appendChild(img);
        wrap.appendChild(btn);
        cont.appendChild(wrap);
      });
    }

    input.addEventListener('change', function () {
      if (!input.files || input.files.length === 0) return;
      Array.from(input.files).forEach(function (f) {
        if (f.type.match(/^image\//) && files.length < 6) files.push(f);
      });
      syncInput();
      render();
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
})();
