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
    var foto       = modalEl.querySelector('[data-cliente-modal-foto]');
    var nome       = modalEl.querySelector('[data-cliente-modal-nome]');
    var tel        = modalEl.querySelector('[data-cliente-modal-tel]');
    var end        = modalEl.querySelector('[data-cliente-modal-end]');
    var resumo     = modalEl.querySelector('[data-cliente-modal-resumo]');
    var carouselEl = document.getElementById('carouselProblema');
    var inner      = modalEl.querySelector('[data-cliente-modal-problema-inner]');
    var btnPrev    = document.getElementById('btnCarouselPrev');
    var btnNext    = document.getElementById('btnCarouselNext');
    var counter    = document.getElementById('carouselProblemaCounter');
    var carousel   = carouselEl ? new bootstrap.Carousel(carouselEl, { ride: false, wrap: true }) : null;

    function buildCarousel(fotos) {
      inner.innerHTML = '';
      if (!fotos || fotos.length === 0) {
        var div = document.createElement('div');
        div.className = 'carousel-item active';
        var img = document.createElement('img');
        img.src = fallbackSvg('Sem foto do problema');
        img.className = 'd-block w-100 fn-profile-problem-photo';
        img.alt = 'Sem foto';
        div.appendChild(img);
        inner.appendChild(div);
        btnPrev.style.display = 'none';
        btnNext.style.display = 'none';
        counter.style.display = 'none';
        return;
      }
      fotos.forEach(function (path, i) {
        var div = document.createElement('div');
        div.className = 'carousel-item' + (i === 0 ? ' active' : '');
        var img = document.createElement('img');
        img.src = '../../' + path;
        img.className = 'd-block w-100 fn-profile-problem-photo';
        img.alt = 'Foto ' + (i + 1);
        img.onerror = function () { img.src = fallbackSvg('Imagem indisponível'); };
        div.appendChild(img);
        inner.appendChild(div);
      });
      var multiple = fotos.length > 1;
      btnPrev.style.display = multiple ? '' : 'none';
      btnNext.style.display = multiple ? '' : 'none';
      if (multiple) {
        counter.style.display = '';
        counter.textContent = '1 / ' + fotos.length;
        carouselEl.addEventListener('slid.bs.carousel', function (e) {
          counter.textContent = (e.to + 1) + ' / ' + fotos.length;
        }, { once: false });
      } else {
        counter.style.display = 'none';
      }
    }

    document.querySelectorAll('.js-open-cliente-perfil').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var clienteFoto = btn.getAttribute('data-cliente-foto') || '';
        var fotosRaw    = btn.getAttribute('data-problema-fotos') || '[]';
        var fotos       = [];
        try { fotos = JSON.parse(fotosRaw); } catch (e) { fotos = []; }

        foto.src = clienteFoto ? '../../' + clienteFoto : fallbackSvg('Sem foto do cliente');
        foto.onerror = function () { foto.src = fallbackSvg('Foto indisponível'); };
        nome.textContent   = btn.getAttribute('data-cliente-nome') || 'Cliente';
        tel.textContent    = btn.getAttribute('data-cliente-telefone') || 'Não informado';
        end.textContent    = btn.getAttribute('data-cliente-endereco') || 'Não informado';
        resumo.textContent = btn.getAttribute('data-resumo') || 'Sem resumo informado.';

        buildCarousel(fotos);
        if (carousel) carousel.to(0);
        modal.show();
      });
    });
  });
})();
