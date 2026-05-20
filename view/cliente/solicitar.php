<?php
session_start();
require_once __DIR__ . '/../../controller/SolicitarChamadoControl.php';

if (empty($_GET['prestador'])) {
    header('Location: ../catalogo.php?aviso=solicitar'); exit;
}

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

$catIcons = [
    'Suporte TI' => '💻',
    'Elétrica'   => '⚡',
    'Hidráulica' => '🔧',
    'Pintura'    => '🎨',
    'Marcenaria' => '🪚',
];
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
    /* ── Hero ── */
    .sol-hero {
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 60%, #c95e00 100%);
      padding: 5rem 0 2.5rem;
      position: relative;
      overflow: hidden;
    }
    .sol-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .sol-hero h1 { font-size: clamp(1.4rem, 3.5vw, 2rem); font-weight: 800; color: #fff; margin-bottom: .3rem; }
    .sol-hero p  { color: rgba(255,255,255,.75); font-size: .95rem; margin: 0; }

    /* ── Etapas (step indicators) ── */
    .etapas-bar {
      display: flex;
      gap: 0;
      align-items: stretch;
      background: #fff;
      border-bottom: 1px solid #e8ecf3;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .etapa-item {
      display: flex;
      align-items: center;
      gap: .5rem;
      padding: .75rem 1.2rem;
      font-size: .8rem;
      font-weight: 600;
      color: #9aa5b8;
      white-space: nowrap;
      position: relative;
      flex-shrink: 0;
    }
    .etapa-item.ativa { color: #0d1b3d; }
    .etapa-item.ativa::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0; right: 0;
      height: 2px;
      background: #ffc107;
      border-radius: 2px 2px 0 0;
    }
    .etapa-num {
      width: 22px; height: 22px;
      border-radius: 50%;
      background: #e8ecf3;
      color: #9aa5b8;
      font-size: .72rem;
      font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .etapa-item.ativa .etapa-num { background: #ffc107; color: #0d1b3d; }

    /* ── Seções do formulário ── */
    .secao-form {
      background: #fff;
      border: 1.5px solid #e8ecf3;
      border-radius: 14px;
      padding: 1.5rem;
      margin-bottom: 1.25rem;
    }
    .secao-form-titulo {
      display: flex;
      align-items: center;
      gap: .7rem;
      font-weight: 700;
      font-size: .95rem;
      color: #0d1b3d;
      margin-bottom: 1.1rem;
      padding-bottom: .75rem;
      border-bottom: 1px solid #f0f3fa;
    }
    .secao-num {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0d1b3d, #1a2b63);
      color: #ffc107;
      font-size: .8rem;
      font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    /* ── Card do prestador (sidebar) ── */
    .sidebar-sticky {
      position: sticky;
      top: 80px;
    }
    .card-prestador-sol {
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 100%);
      border-radius: 16px;
      padding: 1.5rem;
      color: #fff;
    }
    .card-prestador-sol .foto-prest {
      width: 64px; height: 64px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #ffc107;
      flex-shrink: 0;
    }
    .card-prestador-sol .foto-prest-fb {
      width: 64px; height: 64px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ffc107, #ff7a00);
      color: #0d1b3d;
      font-size: 1.5rem;
      font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      border: 3px solid rgba(255,255,255,.3);
      flex-shrink: 0;
    }
    .card-prestador-sol h6 { color: #fff; font-weight: 700; font-size: 1rem; margin-bottom: .15rem; }
    .card-prestador-sol small { color: rgba(255,255,255,.65); font-size: .8rem; }

    .info-strip {
      background: #f8f9fc;
      border: 1.5px solid #e8ecf3;
      border-radius: 12px;
      padding: 1rem 1.1rem;
      display: flex;
      align-items: center;
      gap: .8rem;
      margin-bottom: 1.25rem;
    }
    .info-strip .icone { font-size: 1.4rem; flex-shrink: 0; }

    /* ── Upload zone ── */
    .upload-zone {
      border: 2px dashed #ced4da;
      border-radius: 10px;
      padding: 1.4rem;
      text-align: center;
      cursor: pointer;
      transition: border-color .2s, background .2s;
    }
    .upload-zone:hover { border-color: #ffc107; background: #fffbf0; }
    .upload-zone .icone-upload { font-size: 2rem; margin-bottom: .4rem; }
    .upload-zone input[type="file"] { display: none; }

    /* ── Mapa ── */
    #mapa-solicitar {
      height: 240px;
      border-radius: 10px;
      border: 1.5px solid #e8ecf3;
      overflow: hidden;
    }

    /* ── Checkbox feminino ── */
    .check-prest-fem {
      border: 1.5px solid #e8ecf3;
      border-radius: 12px;
      padding: 1rem 1.1rem;
      display: flex;
      align-items: center;
      gap: .8rem;
      cursor: pointer;
      transition: border-color .2s, background .2s;
    }
    .check-prest-fem:has(input:checked) {
      border-color: #ffc107;
      background: #fffbf0;
    }

    /* ── Botão submit ── */
    .btn-abrir {
      background: linear-gradient(135deg, #ffc107, #ff7a00);
      color: #0d1b3d;
      font-weight: 800;
      font-size: 1rem;
      border: none;
      border-radius: 50px;
      padding: .8rem 2.5rem;
      transition: transform .18s, box-shadow .18s;
      box-shadow: 0 4px 14px rgba(255,193,7,.35);
    }
    .btn-abrir:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(255,193,7,.45);
      color: #0d1b3d;
    }
    .btn-abrir:disabled { opacity: .55; }

    /* ════ DARK MODE ════ */
    [data-theme="dark"] .etapas-bar {
      background: #1a1f2e;
      border-bottom-color: #2e3650;
    }
    [data-theme="dark"] .etapa-item.ativa { color: #e4e8f4; }
    [data-theme="dark"] .etapa-num        { background: #2e3650; color: #8090b0; }
    [data-theme="dark"] .secao-form {
      background: #1e2538;
      border-color: #2e3650;
    }
    [data-theme="dark"] .secao-form-titulo {
      color: #e4e8f4;
      border-bottom-color: #2e3650;
    }
    [data-theme="dark"] .info-strip {
      background: #252d42;
      border-color: #2e3650;
    }
    [data-theme="dark"] .info-strip span  { color: #c8d0e0; }
    [data-theme="dark"] .upload-zone {
      border-color: #2e3650;
    }
    [data-theme="dark"] .upload-zone:hover { background: #252d42; border-color: #ffc107; }
    [data-theme="dark"] .upload-zone p,
    [data-theme="dark"] .upload-zone small { color: #8090b0; }
    [data-theme="dark"] .check-prest-fem {
      border-color: #2e3650;
    }
    [data-theme="dark"] .check-prest-fem:has(input:checked) {
      border-color: #ffc107;
      background: #252d42;
    }
    [data-theme="dark"] .check-prest-fem label,
    [data-theme="dark"] .check-prest-fem span { color: #c8d0e0 !important; }
    [data-theme="dark"] #mapa-solicitar { border-color: #2e3650; }
    [data-theme="dark"] .text-muted { color: #8090b0 !important; }
  </style>
</head>
<body data-cliente-genero="<?php echo htmlspecialchars($clienteGenero); ?>">
<?php $paginaAtiva = 'solicitar'; $_navDepth = 2; require_once __DIR__ . '/../../includes/cliente_nav.php'; ?>

<!-- Hero -->
<section class="sol-hero">
  <div class="container" style="position:relative;z-index:1;">
    <h1>🛠 Abrir chamado de serviço</h1>
    <p>Descreva o problema e aguarde orçamentos de prestadores verificados.</p>
  </div>
</section>

<!-- Barra de etapas -->
<div class="etapas-bar">
  <div class="container d-flex p-0">
    <div class="etapa-item ativa"><div class="etapa-num">1</div>Descrição</div>
    <div class="etapa-item"><div class="etapa-num">2</div>Categoria</div>
    <div class="etapa-item"><div class="etapa-num">3</div>Fotos</div>
    <div class="etapa-item"><div class="etapa-num">4</div>Local</div>
    <div class="etapa-item"><div class="etapa-num">5</div>Horário</div>
  </div>
</div>

<main class="container py-4">

  <?php if ($mensagem): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      ✅ <?php echo htmlspecialchars($mensagem); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      ⚠️ <?php echo htmlspecialchars($erro); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Coluna principal: formulário -->
    <div class="col-lg-8">
      <form method="post" enctype="multipart/form-data" class="js-guard-submit" id="form-solicitar">

        <!-- Seção 1: Descrição -->
        <div class="secao-form">
          <div class="secao-form-titulo">
            <div class="secao-num">1</div>
            Descreva o problema
          </div>
          <textarea name="descricao" class="form-control" rows="5" required maxlength="2000"
            id="descricao-textarea"
            placeholder="Ex: O chuveiro não esquenta, há um barulho estranho no encanamento, a tomada está com faísca..."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
          <div class="d-flex justify-content-end mt-1">
            <small class="text-muted" id="descricao-contador">0 / 2000 caracteres</small>
          </div>
        </div>

        <!-- Seção 2: Categoria -->
        <div class="secao-form">
          <div class="secao-form-titulo">
            <div class="secao-num">2</div>
            Categoria do serviço
          </div>
          <?php if ($tecnicoInfo && $categoriaPre !== ''): ?>
            <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoriaPre); ?>">
            <div class="info-strip">
              <span class="icone"><?php echo $catIcons[$categoriaPre] ?? '🔩'; ?></span>
              <div>
                <div class="fw-semibold" style="font-size:.9rem;"><?php echo htmlspecialchars($categoriaPre); ?></div>
                <small class="text-muted">Categoria definida automaticamente para este prestador</small>
              </div>
            </div>
          <?php elseif ($tecnicoInfo && $categoriasPrestador): ?>
            <select name="categoria" class="form-select" required>
              <option value="">Selecione a categoria...</option>
              <?php foreach ($categoriasPrestador as $cat):
                $ico = $catIcons[$cat] ?? '🔩';
              ?>
                <option value="<?php echo htmlspecialchars($cat); ?>"
                  <?php echo (($_POST['categoria'] ?? '') === $cat) ? 'selected' : ''; ?>>
                  <?php echo $ico . ' ' . htmlspecialchars($cat); ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <select name="categoria" class="form-select" required>
              <option value="">Selecione a categoria...</option>
              <?php foreach ($categorias as $c):
                $ico = $catIcons[$c['nome']] ?? '🔩';
              ?>
                <option value="<?php echo htmlspecialchars($c['nome']); ?>"
                  <?php echo ($categoriaPre === $c['nome'] || ($_POST['categoria'] ?? '') === $c['nome']) ? 'selected' : ''; ?>>
                  <?php echo $ico . ' ' . htmlspecialchars($c['nome']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>

        <!-- Seção 3: Fotos -->
        <div class="secao-form">
          <div class="secao-form-titulo">
            <div class="secao-num">3</div>
            Fotos do problema <span class="ms-1 fw-normal text-muted" style="font-size:.82rem;">(opcional, até 6)</span>
          </div>
          <label class="upload-zone w-100" for="foto-input">
            <div class="icone-upload">📷</div>
            <p class="mb-1 fw-semibold" style="font-size:.9rem;">Clique para selecionar fotos</p>
            <small class="text-muted">JPG, PNG ou WEBP &bull; Múltiplos arquivos permitidos</small>
            <input type="file" name="fotos[]" id="foto-input" multiple
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
          </label>
          <div id="foto-preview-box" class="d-none mt-3">
            <p class="small text-muted mb-2">Pré-visualização</p>
            <div id="foto-preview-imgs" class="d-flex flex-wrap gap-2"></div>
          </div>
        </div>

        <!-- Seção 4: Local -->
        <div class="secao-form">
          <div class="secao-form-titulo">
            <div class="secao-num">4</div>
            Local do serviço
          </div>
          <?php $endCadastrado = trim($clienteEndereco . ($clienteCep ? " — CEP: $clienteCep" : '')); ?>
          <?php if ($endCadastrado): ?>
            <div class="info-strip mb-3">
              <span class="icone">📍</span>
              <div class="flex-grow-1">
                <div style="font-size:.9rem;"><?php echo htmlspecialchars($endCadastrado); ?></div>
                <small class="text-muted">Endereço cadastrado no perfil</small>
              </div>
              <a href="../perfil.php" class="btn btn-sm btn-outline-secondary flex-shrink-0">Alterar</a>
            </div>
            <input type="hidden" name="endereco" value="<?php echo htmlspecialchars($endCadastrado, ENT_QUOTES); ?>">
            <label class="form-label fw-semibold" style="font-size:.88rem;">
              Ajuste o marcador no mapa se necessário
            </label>
            <div id="mapa-solicitar"></div>
            <input type="hidden" name="lat_servico" id="inp-lat-servico">
            <input type="hidden" name="lng_servico" id="inp-lng-servico">
            <p class="form-text text-muted mt-2" id="txt-coords-selecionadas">Localizando endereço...</p>
          <?php else: ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
              <span>📭</span>
              <span>Você ainda não tem endereço cadastrado.
                <a href="../perfil.php" class="alert-link fw-semibold">Atualizar perfil</a>.
              </span>
            </div>
            <input type="hidden" name="endereco" value="">
          <?php endif; ?>
        </div>

        <!-- Seção 5: Horário -->
        <div class="secao-form">
          <div class="secao-form-titulo">
            <div class="secao-num">5</div>
            <?php echo $tecnicoInfo ? 'Horário disponível' : 'Data e hora desejada'; ?>
            <?php if (!$tecnicoInfo): ?><span class="ms-1 fw-normal text-muted" style="font-size:.82rem;">(opcional)</span><?php endif; ?>
          </div>
          <?php if ($tecnicoInfo): ?>
            <input type="hidden" name="tecnico_id_selecionado" value="<?php echo (int)$tecnicoInfo['id']; ?>">
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
              <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
                <span>🗓</span>
                <span>Sem horários disponíveis no momento.
                  <a href="../catalogo.php" class="alert-link fw-semibold">Escolha outro prestador</a>.
                </span>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <input type="datetime-local" name="data_agendamento" class="form-control"
                   min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                   value="<?php echo htmlspecialchars($_POST['data_agendamento'] ?? ''); ?>">
            <small class="text-muted mt-1 d-block">Se não souber a data, deixe em branco e acerte com o prestador depois.</small>
          <?php endif; ?>
        </div>

        <!-- Preferência feminino -->
        <?php if ($clienteGenero === 'Feminino'): ?>
        <div class="secao-form">
          <div class="secao-form-titulo">
            <div class="secao-num" style="background:linear-gradient(135deg,#e91e8c,#c2185b);">♀</div>
            Preferência de prestador
          </div>
          <label class="check-prest-fem w-100 d-flex align-items-center gap-3">
            <input class="form-check-input m-0 flex-shrink-0" type="checkbox" name="prest_feminino" value="1"
              id="chk-prestadora-mulher" <?php echo !empty($_POST['prest_feminino']) ? 'checked' : ''; ?>>
            <div>
              <span class="fw-semibold d-block">Quero somente prestadoras mulheres</span>
              <small class="text-muted">Apenas profissionais do gênero feminino serão consideradas para este chamado.</small>
            </div>
          </label>
        </div>
        <?php endif; ?>

        <!-- Botões -->
        <div class="d-flex flex-wrap gap-3 align-items-center pb-4">
          <button type="submit" class="btn-abrir" id="btn-abrir-chamado"
            <?php echo ($tecnicoInfo && !$slotsDisp) ? 'disabled' : ''; ?>>
            🚀 Abrir chamado
          </button>
          <a href="../dashboardCliente.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>

      </form>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <div class="sidebar-sticky">

      <?php if ($tecnicoInfo): ?>
      <!-- Card do prestador selecionado -->
      <div class="card-prestador-sol mb-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <?php if (!empty($tecnicoInfo['foto_perfil'])): ?>
            <img src="../../<?php echo htmlspecialchars($tecnicoInfo['foto_perfil']); ?>"
                 class="foto-prest" alt="">
          <?php else: ?>
            <div class="foto-prest-fb"><?php echo mb_strtoupper(mb_substr($tecnicoInfo['nome'], 0, 1)); ?></div>
          <?php endif; ?>
          <div>
            <h6><?php echo htmlspecialchars($tecnicoInfo['nome']); ?></h6>
            <small><?php echo htmlspecialchars($tecnicoInfo['especialidade'] ?? 'Prestador de serviços'); ?></small>
          </div>
        </div>
        <div style="background:rgba(255,255,255,.1);border-radius:8px;padding:.6rem .9rem;font-size:.82rem;color:rgba(255,255,255,.8);">
          ✅ Solicitação direta — o prestador será notificado imediatamente.
        </div>
        <a href="../catalogo.php" class="btn btn-sm btn-outline-light mt-3 w-100" style="border-color:rgba(255,255,255,.3);">
          Escolher outro prestador
        </a>
      </div>
      <?php endif; ?>

      <!-- Como funciona -->
      <div class="secao-form">
        <div class="secao-form-titulo">
          <span style="font-size:1.1rem;">💡</span>
          Como funciona
        </div>
        <ol class="ps-3 mb-0" style="font-size:.86rem;line-height:1.9;color:#667085;">
          <li>Preencha o formulário ao lado</li>
          <li>Prestadores qualificados recebem seu chamado</li>
          <li>Você recebe orçamentos e escolhe o melhor</li>
          <li>O serviço é agendado e acompanhado pela plataforma</li>
          <li>Ao concluir, avalie o profissional</li>
        </ol>
      </div>

      <!-- Dica -->
      <div class="alert alert-warning border-start border-4 border-warning rounded-3 py-3 px-3" style="font-size:.85rem;">
        <strong>Dica:</strong> Quanto mais detalhada for a descrição e mais fotos você adicionar, mais preciso será o orçamento que você receberá.
      </div>

      </div><!-- /sidebar-sticky -->
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
/* ── Mapa ─────────────────────────────────────────────────────── */
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

  function confirmar() { if (solTxt) solTxt.textContent = 'Local confirmado. Arraste o marcador para ajustar.'; }
  function falhou()    { if (solTxt) solTxt.textContent = 'Não foi possível localizar. Arraste o marcador para o local correto.'; }

  function limparRua(str) {
    return (str || '')
      .replace(/^Quadra\s+/i, '')
      .replace(/\s+Conjunto\s+.*$/i, '')
      .replace(/\s+Lote\s+.*$/i, '')
      .trim();
  }
  function porBairro(bairro, cidade, uf) {
    var q = [bairro, cidade, uf, 'Brasil'].filter(Boolean).join(', ');
    if (q.replace('Brasil', '').trim().length < 3) { falhou(); return; }
    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.length) { mover(parseFloat(d[0].lat), parseFloat(d[0].lon)); confirmar(); } else falhou(); })
      .catch(falhou);
  }
  function porNominatimEstruturado(rua, bairro, cidade, uf) {
    var p = 'format=json&limit=1&countrycodes=br';
    if (rua)    p += '&street='  + encodeURIComponent(rua);
    if (bairro) p += '&suburb='  + encodeURIComponent(bairro);
    if (cidade) p += '&city='    + encodeURIComponent(cidade);
    if (uf)     p += '&state='   + encodeURIComponent(uf);
    fetch('https://nominatim.openstreetmap.org/search?' + p)
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.length) { mover(parseFloat(d[0].lat), parseFloat(d[0].lon)); confirmar(); }
        else if (bairro) porNominatimEstruturado(rua, '', cidade, uf);
        else porAwesome(bairro, cidade, uf);
      })
      .catch(function () { porAwesome(bairro, cidade, uf); });
  }
  function porAwesome(bairro, cidade, uf) {
    fetch('https://cep.awesomeapi.com.br/json/' + cepCad)
      .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
      .then(function (d) {
        if (d && d.lat && d.lng && parseFloat(d.lat) !== 0) { mover(parseFloat(d.lat), parseFloat(d.lng)); confirmar(); }
        else porBairro(bairro || (d && d.district), cidade || (d && d.city), uf || (d && d.state));
      })
      .catch(function () { porBairro(bairro, cidade, uf); });
  }
  function iniciar() {
    if (cepCad.length !== 8) { if (endCad) porBairro('', endCad, ''); else falhou(); return; }
    fetch('https://viacep.com.br/ws/' + cepCad + '/json/')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.erro || !d.localidade) { porAwesome('', '', ''); return; }
        var rua = limparRua(d.logradouro);
        if (rua) porNominatimEstruturado(rua, d.bairro || '', d.localidade || '', d.uf || '');
        else porAwesome(d.bairro || '', d.localidade || '', d.uf || '');
      })
      .catch(function () { porAwesome('', '', ''); });
  }
  iniciar();
})();
</script>
</body>
</html>
