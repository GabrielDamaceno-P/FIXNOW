<?php
session_start();
require_once __DIR__ . '/../../controller/SolicitarChamadoControl.php';

$ctrl = new SolicitarChamadoControl();
$ctrl->processar();

$categorias      = $ctrl->categorias;
$prestadores     = $ctrl->prestadores;
$prestadorPre    = $ctrl->prestadorPre;
$categoriaPre    = $ctrl->categoriaPre;
$clienteGenero   = $ctrl->clienteGenero;
$clienteNome     = $ctrl->clienteNome;
$clienteFoto     = $ctrl->clienteFoto;
$clienteEndereco = $ctrl->clienteEndereco;
$clienteCep      = $ctrl->clienteCep;
$tecnicoInfo          = $ctrl->tecnicoInfo;
$slotsDisp            = $ctrl->slotsDisponiveis;
$categoriasPrestador  = $ctrl->categoriasPrestador;
$naoLidas        = $ctrl->naoLidas;
$mensagem        = $ctrl->mensagem;
$erro            = $ctrl->erro;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitar Serviço - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>
    #mapa-solicitar { height: 280px; border-radius: 10px; border: 1px solid #dee2e6; }
  </style>
</head>
<body data-cliente-genero="<?php echo htmlspecialchars($clienteGenero); ?>">
<?php $paginaAtiva = 'solicitar'; $_navDepth = 2; require_once __DIR__ . '/../../includes/cliente_nav.php'; ?>

<main class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h2 class="mb-3">Solicitar serviço técnico</h2>

          <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
          <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

          <?php if ($tecnicoInfo): ?>
          <div class="alert alert-primary d-flex align-items-center gap-3 mb-3">
            <?php if (!empty($tecnicoInfo['foto_perfil'])): ?>
              <img src="../../<?php echo htmlspecialchars($tecnicoInfo['foto_perfil']); ?>" width="48" height="48"
                   class="rounded-circle object-fit-cover flex-shrink-0" alt="Foto">
            <?php endif; ?>
            <div>
              <div class="fw-semibold">Solicitação direta para: <?php echo htmlspecialchars($tecnicoInfo['nome']); ?></div>
              <?php if ($categoriaPre !== ''): ?>
                <div class="small text-muted">Categoria: <?php echo htmlspecialchars($categoriaPre); ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" class="row g-3 js-guard-submit" id="form-solicitar">

            <div class="col-12">
              <label class="form-label">Descrição do problema <span class="text-danger">*</span></label>
              <textarea name="descricao" class="form-control" rows="4" required maxlength="2000"
                id="descricao-textarea"><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
              <small class="text-muted" id="descricao-contador">0 / 2000 caracteres</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Categoria <span class="text-danger">*</span></label>
              <?php if ($tecnicoInfo && $categoriaPre !== ''): ?>
                <!-- Categoria única ou já definida: somente leitura -->
                <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoriaPre); ?>">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($categoriaPre); ?>" disabled>
              <?php elseif ($tecnicoInfo && $categoriasPrestador): ?>
                <!-- Prestador selecionado: mostrar apenas as categorias dos serviços dele -->
                <select name="categoria" class="form-select" required>
                  <option value="">Selecione a categoria...</option>
                  <?php foreach ($categoriasPrestador as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"
                      <?php echo (($_POST['categoria'] ?? '') === $cat) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($cat); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <!-- Sem prestador fixo: todas as categorias -->
                <select name="categoria" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($categorias as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['nome']); ?>"
                      <?php echo ($categoriaPre === $c['nome'] || ($_POST['categoria'] ?? '') === $c['nome']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($c['nome']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="form-label">Fotos <span class="text-muted small">(opcional, até 6)</span></label>
              <input type="file" name="fotos[]" class="form-control" id="foto-input" multiple
                     accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
              <small class="text-muted">Selecione uma ou mais fotos do problema.</small>
            </div>

            <div class="col-12 d-none" id="foto-preview-box">
              <p class="small text-muted mb-1">Pré-visualização</p>
              <div id="foto-preview-imgs" class="d-flex flex-wrap gap-2"></div>
            </div>

            <?php if ($clienteGenero === 'Feminino'): ?>
            <div class="col-12" id="wrap-prestadora-mulher">
              <div class="form-check border rounded p-3 bg-light">
                <input class="form-check-input" type="checkbox" name="prest_feminino" value="1"
                  id="chk-prestadora-mulher" <?php echo !empty($_POST['prest_feminino']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chk-prestadora-mulher">
                  Quero <strong>somente prestadoras mulheres</strong> para este serviço.
                </label>
              </div>
            </div>
            <?php endif; ?>

            <?php $endCadastrado = trim($clienteEndereco . ($clienteCep ? " - CEP: $clienteCep" : '')); ?>
            <div class="col-12">
              <label class="form-label">Endereço do serviço</label>
              <?php if ($endCadastrado): ?>
                <div class="border rounded p-3 bg-light d-flex align-items-center gap-2">
                  <span class="text-success fs-5">📍</span>
                  <span><?php echo htmlspecialchars($endCadastrado); ?></span>
                  <a href="../perfil.php" class="ms-auto small text-muted">Alterar</a>
                </div>
                <input type="hidden" name="endereco" value="<?php echo htmlspecialchars($endCadastrado, ENT_QUOTES); ?>">
              <?php else: ?>
                <div class="alert alert-warning py-2 mb-0">
                  Você ainda não cadastrou um endereço.
                  <a href="../perfil.php" class="alert-link">Clique aqui para atualizar seu perfil</a>.
                </div>
                <input type="hidden" name="endereco" value="">
              <?php endif; ?>
            </div>

            <!-- Mapa mostrando o local do serviço -->
            <?php if ($endCadastrado): ?>
            <div class="col-12">
              <label class="form-label">Local do serviço no mapa <span class="text-muted small">(arraste o marcador para ajustar)</span></label>
              <div id="mapa-solicitar"></div>
              <input type="hidden" name="lat_servico" id="inp-lat-servico">
              <input type="hidden" name="lng_servico" id="inp-lng-servico">
              <p class="form-text text-muted" id="txt-coords-selecionadas">Localizando endereço...</p>
            </div>
            <?php endif; ?>

            <?php if ($tecnicoInfo): ?>
            <div class="col-md-6">
              <input type="hidden" name="tecnico_id_selecionado" value="<?php echo (int)$tecnicoInfo['id']; ?>">
              <label class="form-label">Horário disponível</label>
              <?php if ($slotsDisp): ?>
                <select name="slot_selecionado" class="form-select" required>
                  <option value="">Selecione um horário...</option>
                  <?php
                    $nomeDias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
                    foreach ($slotsDisp as $dataSlot => $horasSlot):
                        $dtSlot = new DateTime($dataSlot);
                        $diaFmt = $nomeDias[(int)$dtSlot->format('w')] . ', ' . $dtSlot->format('d/m');
                  ?>
                  <optgroup label="<?php echo htmlspecialchars($diaFmt); ?>">
                    <?php foreach ($horasSlot as $hSlot): ?>
                      <option value="<?php echo $dataSlot; ?>|<?php echo $hSlot; ?>"
                        <?php echo (($_POST['slot_selecionado'] ?? '') === "$dataSlot|$hSlot") ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars("$diaFmt às $hSlot"); ?>
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <div class="alert alert-warning py-2 mb-0">
                  Este prestador não possui horários disponíveis no momento.
                  <a href="../catalogo.php" class="alert-link">Escolha outro prestador</a>.
                </div>
              <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="col-md-6">
              <label class="form-label">Data e hora desejada <span class="text-muted small">(opcional)</span></label>
              <input type="datetime-local" name="data_agendamento" class="form-control"
                     min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                     value="<?php echo htmlspecialchars($_POST['data_agendamento'] ?? ''); ?>">
            </div>
            <?php endif; ?>

            <div class="col-12">
              <button type="submit" class="btn btn-warning fw-semibold" id="btn-abrir-chamado"
                <?php echo ($tecnicoInfo && !$slotsDisp) ? 'disabled' : ''; ?>>Abrir chamado</button>
              <a href="../dashboardCliente.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/forms-helpers.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../../assets/js/solicitar.js"></script>
<script>
/* ── Mapa do endereço cadastrado ───────────────────────────────── */
(function () {
  var mapaEl = document.getElementById('mapa-solicitar');
  if (!mapaEl || typeof L === 'undefined') return;

  var solLat = document.getElementById('inp-lat-servico');
  var solLng = document.getElementById('inp-lng-servico');
  var solTxt = document.getElementById('txt-coords-selecionadas');

  var cepCad = '<?php echo preg_replace('/\D/', '', $clienteCep ?? ''); ?>';
  var endCad = '<?php echo addslashes($clienteEndereco ?? ''); ?>';

  var map    = L.map(mapaEl).setView([-15.7801, -47.9292], 12);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  var marker = L.marker([-15.7801, -47.9292], { draggable: true }).addTo(map);

  function atualizar(latlng) {
    if (solLat) solLat.value = latlng.lat.toFixed(7);
    if (solLng) solLng.value = latlng.lng.toFixed(7);
    if (solTxt) solTxt.textContent = 'Local confirmado: ' + latlng.lat.toFixed(5) + ', ' + latlng.lng.toFixed(5);
  }

  function mover(lat, lng) {
    var ll = L.latLng(lat, lng);
    map.setView(ll, 17);
    marker.setLatLng(ll);
    atualizar(ll);
  }

  marker.on('dragend', function () { atualizar(marker.getLatLng()); });
  map.on('click', function (e) { marker.setLatLng(e.latlng); atualizar(e.latlng); });

  function confirmar() {
    if (solTxt) solTxt.textContent = 'Local confirmado. Arraste o marcador para ajustar se necessário.';
  }
  function falhou() {
    if (solTxt) solTxt.textContent = 'Não foi possível localizar. Arraste o marcador para o local correto.';
  }

  // Remove "Quadra " do início e "Conjunto X" do fim para obter só "QNN 7"
  function limparRua(str) {
    return (str || '')
      .replace(/^Quadra\s+/i, '')
      .replace(/\s+Conjunto\s+\S+$/i, '')
      .trim();
  }

  // Último recurso: busca por bairro + cidade
  function porBairro(bairro, cidade, uf) {
    var q = [bairro, cidade, uf, 'Brasil'].filter(Boolean).join(', ');
    if (q.replace('Brasil', '').trim().length < 3) { falhou(); return; }
    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.length) { mover(parseFloat(d[0].lat), parseFloat(d[0].lon)); confirmar(); }
        else falhou();
      })
      .catch(falhou);
  }

  // Nominatim busca estruturada: street + suburb + city + state
  // Se não achar com suburb, tenta sem suburb
  function porNominatimEstruturado(rua, bairro, cidade, uf) {
    var p = 'format=json&limit=1&countrycodes=br';
    if (rua)   p += '&street='  + encodeURIComponent(rua);
    if (bairro) p += '&suburb=' + encodeURIComponent(bairro);
    if (cidade) p += '&city='   + encodeURIComponent(cidade);
    if (uf)     p += '&state='  + encodeURIComponent(uf);

    fetch('https://nominatim.openstreetmap.org/search?' + p)
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.length) {
          mover(parseFloat(d[0].lat), parseFloat(d[0].lon)); confirmar();
        } else if (bairro) {
          // Tenta sem o bairro (mais abrangente)
          porNominatimEstruturado(rua, '', cidade, uf);
        } else {
          porAwesome(bairro, cidade, uf);
        }
      })
      .catch(function () { porAwesome(bairro, cidade, uf); });
  }

  // AwesomeAPI: coordenadas por CEP (nível de bairro/quadra)
  function porAwesome(bairro, cidade, uf) {
    fetch('https://cep.awesomeapi.com.br/json/' + cepCad)
      .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
      .then(function (d) {
        if (d && d.lat && d.lng && parseFloat(d.lat) !== 0) {
          mover(parseFloat(d.lat), parseFloat(d.lng)); confirmar();
        } else {
          porBairro(bairro || (d && d.district), cidade || (d && d.city), uf || (d && d.state));
        }
      })
      .catch(function () { porBairro(bairro, cidade, uf); });
  }

  // Ponto de entrada: ViaCEP para dados do endereço → Nominatim estruturado → AwesomeAPI → bairro
  function iniciar() {
    if (cepCad.length !== 8) {
      if (endCad) porBairro('', endCad, '');
      else falhou();
      return;
    }
    fetch('https://viacep.com.br/ws/' + cepCad + '/json/')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.erro || !d.localidade) { porAwesome('', '', ''); return; }
        var rua    = limparRua(d.logradouro);   // "QNN 7"
        var bairro = d.bairro    || '';          // "Ceilândia Norte"
        var cidade = d.localidade || '';         // "Brasília"
        var uf     = d.uf        || '';          // "DF"
        if (rua) {
          porNominatimEstruturado(rua, bairro, cidade, uf);
        } else {
          porAwesome(bairro, cidade, uf);
        }
      })
      .catch(function () { porAwesome('', '', ''); });
  }

  iniciar();
})();

</script>
</body>
</html>
