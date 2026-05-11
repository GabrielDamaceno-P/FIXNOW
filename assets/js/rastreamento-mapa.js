/**
 * Mapa Leaflet — rastreamento real do prestador com geocodificação do endereço do serviço.
 */
(function () {
  const el = document.getElementById('map');
  if (!el || typeof L === 'undefined') return;

  const chamadoId       = parseInt(el.dataset.chamadoId, 10) || 0;
  const latServico      = el.dataset.latServico  !== '' ? parseFloat(el.dataset.latServico)  : null;
  const lngServico      = el.dataset.lngServico  !== '' ? parseFloat(el.dataset.lngServico)  : null;
  const enderecoServico = el.dataset.enderecoServico || '';
  const nomeTec         = el.dataset.tecnicoNome || 'Prestador';

  // Fallback (São Paulo) caso a geocodificação falhe
  const LAT_FB = -23.5505, LNG_FB = -46.6333;

  const map = L.map(el).setView([LAT_FB, LNG_FB], 13);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  const iconTec = L.divIcon({
    className: 'marker-tecnico-fixnow',
    html: '<span class="marker-tecnico-inner"></span>',
    iconSize: [28, 28],
    iconAnchor: [14, 14],
  });

  let markerCliente = null;
  let markerTec     = null;
  let polyRoute     = null;
  let clienteLatLng = null;

  const etaEl    = document.getElementById('eta-dinamico');
  const statusEl = document.getElementById('rastreamento-status');

  function setStatus(msg) {
    if (statusEl) statusEl.textContent = msg;
  }

  function distKm(a, b) {
    const R = 6371;
    const dLat = (b.lat - a.lat) * Math.PI / 180;
    const dLng = (b.lng - a.lng) * Math.PI / 180;
    const s = Math.sin(dLat / 2) ** 2
      + Math.cos(a.lat * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180)
      * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
  }

  function atualizar(dados) {
    if (!dados.ok || !clienteLatLng) return;

    if (!dados.a_caminho || dados.lat == null) {
      if (markerTec) { map.removeLayer(markerTec); markerTec = null; }
      if (polyRoute)  { map.removeLayer(polyRoute);  polyRoute  = null; }
      setStatus('Aguardando prestador iniciar deslocamento...');
      if (etaEl) etaEl.textContent = '—';
      map.setView(clienteLatLng, 15);
      return;
    }

    const tecPos = L.latLng(dados.lat, dados.lng);

    if (!markerTec) {
      markerTec = L.marker(tecPos, { icon: iconTec })
        .addTo(map)
        .bindPopup(nomeTec + ' em deslocamento');
    } else {
      markerTec.setLatLng(tecPos);
    }

    if (polyRoute) map.removeLayer(polyRoute);
    polyRoute = L.polyline([tecPos, clienteLatLng], {
      color: '#ff7a00', weight: 5, opacity: 0.85,
    }).addTo(map);

    const km = distKm(tecPos, clienteLatLng);
    const minutos = Math.round((km / 30) * 60);
    if (etaEl) {
      etaEl.textContent = minutos <= 1 ? 'Chegando agora' : minutos + ' min';
    }
    setStatus('Prestador a caminho!');
    map.fitBounds(L.latLngBounds([tecPos, clienteLatLng]), { padding: [50, 50] });
  }

  function poll() {
    if (!chamadoId) return;
    fetch('../api/rastreamento.php?chamado=' + chamadoId)
      .then(function (r) { return r.json(); })
      .then(atualizar)
      .catch(function () {});
  }

  function iniciarMapa(lat, lng) {
    clienteLatLng = L.latLng(lat, lng);
    markerCliente = L.marker(clienteLatLng)
      .addTo(map)
      .bindPopup('Local do serviço')
      .openPopup();
    map.setView(clienteLatLng, 15);

    setStatus('Carregando...');
    poll();
    setInterval(poll, 10000);
  }

  // Geocodifica o endereço do serviço via Nominatim
  function geocodificar(endereco, cb) {
    // Remove "CEP: XXXXX-XXX" pois confunde o Nominatim, e acrescenta Brasil
    const query = endereco.replace(/[-–]\s*CEP:?\s*[\d\-]+/gi, '').trim() + ', Brasil';
    const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q='
      + encodeURIComponent(query);

    console.log('[Rastreamento] Geocodificando:', query);

    fetch(url, { headers: { 'Accept-Language': 'pt-BR' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        console.log('[Rastreamento] Resultado Nominatim:', data);
        if (data && data.length > 0) {
          cb(parseFloat(data[0].lat), parseFloat(data[0].lon));
        } else {
          console.warn('[Rastreamento] Endereço não encontrado, usando fallback.');
          cb(LAT_FB, LNG_FB);
        }
      })
      .catch(function (e) {
        console.error('[Rastreamento] Erro ao geocodificar:', e);
        cb(LAT_FB, LNG_FB);
      });
  }

  if (latServico !== null && lngServico !== null) {
    // Coordenadas exatas salvas no banco — sem geocodificação
    iniciarMapa(latServico, lngServico);
  } else if (enderecoServico) {
    setStatus('Localizando endereço...');
    geocodificar(enderecoServico, iniciarMapa);
  } else {
    iniciarMapa(LAT_FB, LNG_FB);
  }
})();
