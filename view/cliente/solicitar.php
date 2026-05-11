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

            <?php
              $endCadastrado = trim($clienteEndereco . ($clienteCep ? " - CEP: $clienteCep" : ''));
              $endPost       = $_POST['endereco'] ?? '';
              $usandoOutro   = $endPost !== '' && $endPost !== $endCadastrado;
            ?>
            <div class="col-12">
              <label class="form-label">Endereço do serviço <span class="text-danger">*</span></label>
              <?php if ($endCadastrado): ?>
              <div class="border rounded p-3 bg-light mb-2">
                <div class="form-check mb-1">
                  <input class="form-check-input" type="radio" name="endereco_opcao" id="end-cadastrado"
                    value="cadastrado" <?php echo !$usandoOutro ? 'checked' : ''; ?>
                    onclick="fnEndToggle(false)">
                  <label class="form-check-label" for="end-cadastrado">
                    Usar endereço cadastrado: <strong><?php echo htmlspecialchars($endCadastrado); ?></strong>
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="endereco_opcao" id="end-outro"
                    value="outro" <?php echo $usandoOutro ? 'checked' : ''; ?>
                    onclick="fnEndToggle(true)">
                  <label class="form-check-label" for="end-outro">Usar outro endereço</label>
                </div>
              </div>
              <div id="wrap-outro-endereco" <?php echo $usandoOutro ? '' : 'style="display:none"'; ?>>
                <div class="row g-2">
                  <div class="col-auto">
                    <label class="form-label small text-muted mb-1">CEP</label>
                    <div class="input-group input-group-sm">
                      <input type="text" id="inp-cep-outro" class="form-control" maxlength="9"
                             placeholder="00000-000" inputmode="numeric" style="width:110px">
                      <span class="input-group-text" id="cep-spinner" style="display:none">
                        <span class="spinner-border spinner-border-sm"></span>
                      </span>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label small text-muted mb-1">Endereço completo <span class="text-danger">*</span></label>
                    <input type="text" name="endereco" id="input-outro-endereco" class="form-control" maxlength="200"
                           placeholder="Rua, número, bairro, cidade — ou busque pelo CEP acima"
                           value="<?php echo htmlspecialchars($usandoOutro ? $endPost : ''); ?>">
                  </div>
                </div>
              </div>
              <input type="hidden" name="endereco_cadastrado" value="<?php echo htmlspecialchars($endCadastrado, ENT_QUOTES); ?>">
              <?php else: ?>
              <div class="row g-2">
                <div class="col-auto">
                  <label class="form-label small text-muted mb-1">CEP</label>
                  <div class="input-group input-group-sm">
                    <input type="text" id="inp-cep-outro" class="form-control" maxlength="9"
                           placeholder="00000-000" inputmode="numeric" style="width:110px">
                    <span class="input-group-text" id="cep-spinner" style="display:none">
                      <span class="spinner-border spinner-border-sm"></span>
                    </span>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label small text-muted mb-1">Endereço completo <span class="text-danger">*</span></label>
                  <input type="text" name="endereco" id="input-outro-endereco" class="form-control" required maxlength="200"
                         placeholder="Rua, número, bairro, cidade — ou busque pelo CEP acima"
                         value="<?php echo htmlspecialchars($endPost); ?>">
                </div>
              </div>
              <?php endif; ?>
            </div>

            <!-- Mapa de confirmação do local do serviço -->
            <div class="col-12">
              <label class="form-label">Confirme o local no mapa <span class="text-muted small">(arraste o marcador para o local exato)</span></label>
              <div id="mapa-solicitar"></div>
              <input type="hidden" name="lat_servico" id="inp-lat-servico">
              <input type="hidden" name="lng_servico" id="inp-lng-servico">
              <p class="form-text" id="txt-coords-selecionadas">Clique ou arraste o marcador no mapa para confirmar o local.</p>
            </div>

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
/* ── Toggle endereço ───────────────────────────────────────────── */
function fnEndToggle(usaOutro) {
  var wrap   = document.getElementById('wrap-outro-endereco');
  var inp    = document.getElementById('input-outro-endereco');
  var hidCad = document.querySelector('input[name="endereco_cadastrado"]');
  var hidVal = document.getElementById('_end-cad-value');
  if (!wrap) return;
  wrap.style.display = usaOutro ? '' : 'none';
  if (inp) {
    inp.required = usaOutro;
    inp.name = usaOutro ? 'endereco' : '_endereco_ignorado';
    if (!usaOutro) inp.value = '';
  }
  if (!usaOutro && hidCad) {
    if (!hidVal) {
      hidVal = document.createElement('input');
      hidVal.type = 'hidden'; hidVal.id = '_end-cad-value'; hidVal.name = 'endereco';
      hidCad.parentNode.appendChild(hidVal);
    }
    hidVal.value = hidCad.value;
    // Dispara geocodificação do endereço cadastrado ao voltar
    _centralizarEnderecoCadastrado();
  } else if (hidVal) {
    hidVal.parentNode.removeChild(hidVal);
  }
}

/* ── Mapa + CEP ────────────────────────────────────────────────── */
var _solMap    = null;
var _solMarker = null;
var _solLat    = document.getElementById('inp-lat-servico');
var _solLng    = document.getElementById('inp-lng-servico');
var _solTxt    = document.getElementById('txt-coords-selecionadas');
var _geoTimer  = null;

function _solAtualizar(latlng) {
  if (_solLat) _solLat.value = latlng.lat.toFixed(7);
  if (_solLng) _solLng.value = latlng.lng.toFixed(7);
  if (_solTxt) _solTxt.textContent = 'Local selecionado: ' + latlng.lat.toFixed(5) + ', ' + latlng.lng.toFixed(5);
}

function _solMover(lat, lng) {
  if (!_solMap || !_solMarker) return;
  var ll = L.latLng(lat, lng);
  _solMap.setView(ll, 16);
  _solMarker.setLatLng(ll);
  _solAtualizar(ll);
}

function _limparEndereco(str) {
  if (!str) return '';
  return str.replace(/Quadra\s+/i, '')
            .replace(/Conjunto\s+/i, '')
            .replace(/Brasília\s*-\s*DF/i, 'Ceilândia')
            .trim();
}

/* Nominatim — busca por string de endereço */
function _geocodificarString(str) {
  var query = _limparEndereco(str);
  if (query.length < 3) return;
                 
  var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q=' + encodeURIComponent(query);
  
  fetch(url)
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d && d.length) {
        _solMover(parseFloat(d[0].lat), parseFloat(d[0].lon));
      }
    })
    .catch(function () {});
}

/* Geocodificação Combinada — Prioriza string do endereço, cai para CEP */
function _geocodificarCompleto(cep, endereco) {
  var query = _limparEndereco(endereco);
  if (query.length > 5) {
    // Tenta primeiro pela string completa (mais preciso para quadras)
    var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q=' + encodeURIComponent(query);
    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.length) {
          _solMover(parseFloat(d[0].lat), parseFloat(d[0].lon));
        } else if (cep) {
          _geocodificarCep(cep);
        }
      })
      .catch(function () {
        if (cep) _geocodificarCep(cep);
      });
  } else if (cep) {
    _geocodificarCep(cep);
  }
}

/* BrasilAPI v2 — coordenadas do CEP */
function _geocodificarCep(cep) {
  if (!cep) return;
  fetch('https://brasilapi.com.br/api/cep/v2/' + cep.replace(/\D/g, ''))
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (d) {
      var c = d && d.location && d.location.coordinates;
      if (c && c.latitude && c.longitude) {
        _solMover(parseFloat(c.latitude), parseFloat(c.longitude));
      }
    })
    .catch(function () {});
}

function _centralizarEnderecoCadastrado() {
  var _cepCad = '<?php echo preg_replace('/\D/', '', $clienteCep); ?>';
  var _endCad = '<?php echo addslashes($clienteEndereco); ?>';
  _geocodificarCompleto(_cepCad, _endCad);
}

/* Inicializa o mapa */
var _mapaEl = document.getElementById('mapa-solicitar');
if (_mapaEl && typeof L !== 'undefined') {
  _solMap = L.map(_mapaEl).setView([-15.7801, -47.9292], 12);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap',
  }).addTo(_solMap);
  _solMarker = L.marker([-15.7801, -47.9292], { draggable: true }).addTo(_solMap);
  _solAtualizar(_solMarker.getLatLng());
  _solMarker.on('dragend', function () { _solAtualizar(_solMarker.getLatLng()); });
  _solMap.on('click', function (e) { _solMarker.setLatLng(e.latlng); _solAtualizar(e.latlng); });

  var _radCad = document.getElementById('end-cadastrado');
  if (_radCad && _radCad.checked) _centralizarEnderecoCadastrado();
  
  if (!_radCad && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function (pos) {
      _solMover(pos.coords.latitude, pos.coords.longitude);
    });
  }
}

/* ── Busca CEP via BrasilAPI (+ fallback ViaCEP) ──────────────── */
var _inpCep  = document.getElementById('inp-cep-outro');
var _inpEnd2 = document.getElementById('input-outro-endereco');
var _spinner = document.getElementById('cep-spinner');

if (_inpCep) {
  _inpCep.addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').slice(0, 8);
    this.value = v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5) : v;
    if (v.length === 8) _buscarCep(v);
  });
}

// Debounce para geocodificação manual do endereço
if (_inpEnd2) {
  _inpEnd2.addEventListener('input', function() {
    clearTimeout(_geoTimer);
    var val = this.value;
    _geoTimer = setTimeout(function() {
      if (val.length > 10) _geocodificarString(val);
    }, 1200);
  });
}

function _buscarCep(cep) {
  if (_spinner) _spinner.style.display = '';
  /* ViaCEP: preenche o endereço */
  fetch('https://viacep.com.br/ws/' + cep + '/json/')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (_spinner) _spinner.style.display = 'none';
      if (data.erro) {
        if (_inpEnd2) _inpEnd2.placeholder = 'CEP não encontrado — preencha manualmente';
        return;
      }
      var partes = [data.logradouro, data.bairro, data.localidade + ' - ' + data.uf]
        .filter(Boolean).join(', ');
      if (_inpEnd2) { _inpEnd2.value = partes; _inpEnd2.focus(); }
      
      // Mover o mapa usando o endereço completo (mais preciso que apenas o CEP)
      _geocodificarCompleto(cep, partes);
    })
    .catch(function () { if (_spinner) _spinner.style.display = 'none'; });
}

</script>
</body>
</html>
