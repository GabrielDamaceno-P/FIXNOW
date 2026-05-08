/**
 * Página inicial: scroll suave, animação leve nos cards e no mapa fake.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
      var id = a.getAttribute('href').slice(1);
      if (!id) return;
      a.addEventListener('click', function (e) {
        var target = document.getElementById(id);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    var cards = document.querySelectorAll('#como-funciona .card');
    if (cards.length && 'IntersectionObserver' in window) {
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.style.opacity = '1';
              entry.target.style.transform = 'translateY(0)';
              io.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.15 }
      );
      cards.forEach(function (card, i) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(16px)';
        card.style.transition = 'opacity 0.5s ease ' + i * 0.08 + 's, transform 0.5s ease ' + i * 0.08 + 's';
        io.observe(card);
      });
    }

    var fakeMap = document.querySelector('.fake-map');
    if (fakeMap) {
      fakeMap.addEventListener('mouseenter', function () {
        fakeMap.style.transform = 'scale(1.02)';
        fakeMap.style.transition = 'transform 0.35s ease';
      });
      fakeMap.addEventListener('mouseleave', function () {
        fakeMap.style.transform = 'scale(1)';
      });
    }

    // Atalho discreto para equipe: digitar "admin" abre o login administrativo.
    var adminSequence = '';
    var lastTypeAt = 0;
    document.addEventListener('keydown', function (event) {
      var key = (event.key || '').toLowerCase();
      if (!/^[a-z]$/.test(key)) return;

      var now = Date.now();
      if (now - lastTypeAt > 1800) adminSequence = '';
      lastTypeAt = now;

      adminSequence = (adminSequence + key).slice(-5);
      if (adminSequence === 'admin') {
        window.location.href = 'admin/index.php';
      }
    });
  });
})();
