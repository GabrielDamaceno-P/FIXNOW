/**
 * Modal de avaliação do prestador após conclusão do chamado (estrelas interativas).
 */
(function () {
  'use strict';

  var modalEl = document.getElementById('modalAvaliacao');
  if (!modalEl || typeof bootstrap === 'undefined') return;

  var modal = new bootstrap.Modal(modalEl);
  var form = document.getElementById('form-avaliacao');
  if (!form) return;

  var resumo = modalEl.querySelector('[data-avaliacao-resumo]');
  var hiddenChamado = form.querySelector('input[name="avaliar_chamado_id"]');
  var hiddenNota = form.querySelector('input[name="nota_avaliacao"]');
  var starsRoot = form.querySelector('[data-fn-stars]');
  var legend = form.querySelector('[data-fn-stars-legend]');

  var labels = {
    1: 'Péssimo',
    2: 'Ruim',
    3: 'Regular',
    4: 'Bom',
    5: 'Excelente'
  };

  var selectedNota = 5;
  var hoverNota = 0;

  function starSvg() {
    var ns = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true');
    var p = document.createElementNS(ns, 'path');
    p.setAttribute(
      'd',
      'M12 2.6l2.9 6.1 6.6.6-5 4.4 1.5 6.4L12 17.2 5.9 20.1 7.4 13.7l-5-4.4 6.6-.6L12 2.6z'
    );
    p.setAttribute('fill', 'currentColor');
    svg.appendChild(p);
    return svg;
  }

  function paint(n) {
    if (!hiddenNota || !starsRoot) return;
    hiddenNota.value = String(n);
    starsRoot.classList.toggle('is-rated', n > 0);
    starsRoot.querySelectorAll('.fn-star-btn').forEach(function (btn, idx) {
      var on = idx < n;
      btn.classList.toggle('fn-star-on', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    if (legend) {
      legend.textContent = n > 0 ? labels[n] || '' : 'Toque nas estrelas para avaliar.';
    }
  }

  function bindStars() {
    if (!starsRoot || !hiddenNota) return;
    starsRoot.innerHTML = '';
    for (var i = 1; i <= 5; i++) {
      (function (n) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fn-star-btn';
        btn.setAttribute('aria-label', 'Nota ' + n + ' de 5');
        btn.appendChild(starSvg());
        btn.addEventListener('mouseenter', function () {
          hoverNota = n;
          paint(n);
        });
        btn.addEventListener('click', function () {
          selectedNota = n;
          hoverNota = n;
          paint(n);
        });
        starsRoot.appendChild(btn);
      })(i);
    }
    starsRoot.addEventListener('mouseleave', function () {
      hoverNota = 0;
      paint(selectedNota);
    });
    selectedNota = 5;
    paint(selectedNota);
  }

  bindStars();

  document.querySelectorAll('[data-bs-open-avaliacao]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var chamadoId = btn.getAttribute('data-chamado-id') || '';
      var tecnicoNome = btn.getAttribute('data-tecnico-nome') || 'Prestador';

      if (hiddenChamado) hiddenChamado.value = chamadoId;
      if (resumo) {
        resumo.textContent = 'Chamado #' + chamadoId + ' - Atendimento por ' + tecnicoNome + '.';
      }
      selectedNota = 5;
      hoverNota = 0;
      paint(5);
      modal.show();
    });
  });
})();
