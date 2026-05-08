/**
 * Mapa Leaflet com rota simulada e marcador do técnico em movimento + ETA dinâmico.
 */
(function () {
  const el = document.getElementById('map');
  if (!el || typeof L === 'undefined') return;

  const latC = parseFloat(el.dataset.clienteLat) || -23.5505;
  const lngC = parseFloat(el.dataset.clienteLng) || -46.6333;
  const latT0 = parseFloat(el.dataset.tecnicoLat) || -23.544;
  const lngT0 = parseFloat(el.dataset.tecnicoLng) || -46.626;
  const nomeTec = el.dataset.tecnicoNome || 'Prestador';

  const cliente = L.latLng(latC, lngC);
  const inicioTec = L.latLng(latT0, lngT0);

  const map = L.map(el).setView(cliente, 14);

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  const route = [inicioTec, cliente];
  L.polyline(route, { color: '#ff7a00', weight: 5, opacity: 0.85 }).addTo(map);

  L.marker(cliente).addTo(map).bindPopup('Você (cliente)');

  const iconTec = L.divIcon({
    className: 'marker-tecnico-fixnow',
    html: '<span class="marker-tecnico-inner"></span>',
    iconSize: [28, 28],
    iconAnchor: [14, 14],
  });

  const markerTec = L.marker(inicioTec, { icon: iconTec })
    .addTo(map)
    .bindPopup(nomeTec + ' em deslocamento');

  const etaEl = document.getElementById('eta-dinamico');
  let minutosRestantes = 12;

  function interpola(t) {
    const lat = inicioTec.lat + (cliente.lat - inicioTec.lat) * t;
    const lng = inicioTec.lng + (cliente.lng - inicioTec.lng) * t;
    return L.latLng(lat, lng);
  }

  let t = 0;
  const duracaoMs = 18000;
  const inicio = performance.now();

  function tick(now) {
    const elapsed = now - inicio;
    t = Math.min(1, elapsed / duracaoMs);
    markerTec.setLatLng(interpola(t));
    minutosRestantes = Math.max(0, Math.round(12 * (1 - t)));
    if (etaEl) {
      etaEl.textContent =
        minutosRestantes > 0 ? minutosRestantes + ' minutos' : 'Chegando agora';
    }
    if (t < 1) {
      requestAnimationFrame(tick);
    } else {
      markerTec.setLatLng(cliente);
      markerTec.openPopup();
    }
  }

  requestAnimationFrame(tick);
  map.fitBounds(L.latLngBounds(route), { padding: [40, 40] });
})();
