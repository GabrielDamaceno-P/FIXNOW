<?php
session_start();
require_once __DIR__ . '/../controller/CatalogoControl.php';

$ctrl = new CatalogoControl();
$ctrl->processar();
$prestadores     = $ctrl->prestadores;
$categorias      = $ctrl->categorias;
$filtroCategoria = $ctrl->filtroCategoria;

$_urlInicio = '../index.php';
if (isset($_SESSION['cliente_id']))      $_urlInicio = 'dashboardCliente.php';
elseif (isset($_SESSION['tecnico_id'])) $_urlInicio = 'prestador/dashboardPrestador.php';
elseif (isset($_SESSION['admin_id']))   $_urlInicio = 'admin/painelAdmin.php';

$catIcons = [
    'Suporte TI'  => '💻',
    'Elétrica'    => '⚡',
    'Hidráulica'  => '🔧',
    'Pintura'     => '🎨',
    'Marcenaria'  => '🪚',
    'Jardinagem'  => '🌿',
    'Limpeza'     => '🧹',
    'Refrigeração'=> '❄️',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogo de Prestadores - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    /* ── Hero ── */
    .catalogo-hero {
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 55%, #c95e00 100%);
      padding: 5.5rem 0 3rem;
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    .catalogo-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .catalogo-hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 800; line-height: 1.15; }
    .catalogo-hero p   { color: rgba(255,255,255,.82); max-width: 520px; }

    .hero-search {
      background: rgba(255,255,255,.12);
      border: 1.5px solid rgba(255,255,255,.25);
      border-radius: 50px;
      padding: .5rem .5rem .5rem 1.2rem;
      display: flex;
      align-items: center;
      gap: .5rem;
      max-width: 500px;
      width: 100%;
      backdrop-filter: blur(8px);
    }
    .hero-search input {
      background: transparent;
      border: none;
      outline: none;
      color: #fff;
      font-size: .95rem;
      flex: 1;
    }
    .hero-search input::placeholder { color: rgba(255,255,255,.55); }
    .hero-search button {
      background: #ffc107;
      border: none;
      border-radius: 50px;
      padding: .4rem 1.1rem;
      font-weight: 700;
      font-size: .88rem;
      color: #0d1b3d;
      white-space: nowrap;
      cursor: pointer;
    }
    .hero-search button:hover { background: #ffca2c; }

    /* ── Filtros ── */
    .filtros-bar {
      background: #fff;
      border-bottom: 1px solid #e8ecf3;
      padding: 1rem 0;
      position: sticky;
      top: 56px;
      z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }
    .btn-cat {
      border-radius: 50px;
      padding: .32rem .9rem;
      font-size: .84rem;
      font-weight: 600;
      border: 1.5px solid #dee2e6;
      background: #fff;
      color: #495057;
      text-decoration: none;
      transition: all .18s;
      display: inline-flex;
      align-items: center;
      gap: .3rem;
      white-space: nowrap;
    }
    .btn-cat:hover, .btn-cat.ativo {
      background: #0d1b3d;
      border-color: #0d1b3d;
      color: #fff;
    }
    .btn-cat.ativo { box-shadow: 0 2px 8px rgba(13,27,61,.25); }

    /* ── Cards ── */
    .card-prestador {
      border-radius: 16px;
      border: 1.5px solid #e8ecf3;
      background: #fff;
      box-shadow: 0 4px 16px rgba(13,27,61,.06);
      transition: transform .2s, box-shadow .2s;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .card-prestador:hover {
      transform: translateY(-5px);
      box-shadow: 0 16px 36px rgba(13,27,61,.14);
    }
    .card-prestador-header {
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 100%);
      padding: 1.4rem 1.4rem 0;
      display: flex;
      align-items: flex-end;
      gap: 1rem;
      position: relative;
    }
    .card-prestador-header .foto-wrap {
      flex-shrink: 0;
      margin-bottom: -1.6rem;
    }
    .foto-prestador {
      width: 72px; height: 72px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,.2);
    }
    .foto-prestador-fallback {
      width: 72px; height: 72px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ffc107, #ff7a00);
      color: #0d1b3d;
      font-size: 1.7rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 3px solid #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,.2);
      flex-shrink: 0;
    }
    .badge-destaque {
      position: absolute;
      top: .7rem;
      right: .7rem;
      background: linear-gradient(135deg, #ffc107, #ff7a00);
      color: #0d1b3d;
      font-size: .72rem;
      font-weight: 800;
      padding: .22rem .6rem;
      border-radius: 50px;
      letter-spacing: .02em;
    }

    .card-prestador-body {
      padding: 2rem 1.4rem 1rem;
      flex: 1;
    }
    .card-prestador-body h6 {
      font-size: 1.05rem;
      font-weight: 700;
      margin-bottom: .1rem;
      color: #0d1b3d;
    }
    .especialidade-text {
      font-size: .82rem;
      color: #667085;
      margin-bottom: .6rem;
    }
    .estrelas-rating {
      display: flex;
      align-items: center;
      gap: .3rem;
      margin-bottom: .9rem;
    }
    .estrelas-rating .stars { color: #ffc107; font-size: .9rem; letter-spacing: .05em; }
    .estrelas-rating .val   { font-size: .82rem; color: #667085; }

    .servicos-lista {
      display: flex;
      flex-wrap: wrap;
      gap: .4rem;
      margin-bottom: 0;
    }
    .tag-servico {
      font-size: .75rem;
      background: #f0f3fa;
      color: #374151;
      border-radius: 50px;
      padding: .2rem .65rem;
      font-weight: 500;
      border: 1px solid #e2e6f0;
    }

    .card-prestador-footer {
      padding: .9rem 1.4rem 1.2rem;
      display: flex;
      gap: .5rem;
    }

    /* ── Sem resultados ── */
    .empty-state {
      text-align: center;
      padding: 4rem 1rem;
    }
    .empty-state .icone-empty { font-size: 3.5rem; margin-bottom: .8rem; }
    .empty-state h5 { font-weight: 700; color: #0d1b3d; margin-bottom: .4rem; }
    .empty-state p  { color: #667085; max-width: 340px; margin: 0 auto; }

    /* ── Contador ── */
    .resultado-count {
      font-size: .88rem;
      color: #667085;
      padding: .2rem 0 .8rem;
    }

    /* ══ DARK MODE ══ */
    [data-theme="dark"] .filtros-bar {
      background: #1a1f2e;
      border-bottom-color: #2e3650;
      box-shadow: 0 2px 8px rgba(0,0,0,.3);
    }
    [data-theme="dark"] .btn-cat {
      background: #252d42;
      border-color: #2e3650;
      color: #c8d0e0;
    }
    [data-theme="dark"] .btn-cat:hover,
    [data-theme="dark"] .btn-cat.ativo {
      background: #ffc107;
      border-color: #ffc107;
      color: #0d1b3d;
    }
    [data-theme="dark"] .card-prestador {
      background: #1e2538;
      border-color: #2e3650;
      box-shadow: 0 4px 16px rgba(0,0,0,.3);
    }
    [data-theme="dark"] .card-prestador:hover {
      box-shadow: 0 16px 36px rgba(0,0,0,.45);
    }
    [data-theme="dark"] .card-prestador-body h6 { color: #e4e8f4; }
    [data-theme="dark"] .especialidade-text      { color: #8090b0; }
    [data-theme="dark"] .estrelas-rating .val    { color: #8090b0; }
    [data-theme="dark"] .tag-servico {
      background: #252d42;
      color: #c8d0e0;
      border-color: #2e3650;
    }
    [data-theme="dark"] .card-prestador-footer {
      border-top: 1px solid #2e3650;
    }
    [data-theme="dark"] .resultado-count { color: #8090b0; }
    [data-theme="dark"] .empty-state h5  { color: #e4e8f4; }
    [data-theme="dark"] .empty-state p   { color: #8090b0; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= $_urlInicio ?>">Início</a></li>
        <li class="nav-item"><a class="nav-link active" href="catalogo.php">Catálogo</a></li>
        <?php if (isset($_SESSION['cliente_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="dashboardCliente.php">Dashboard</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-sm btn-warning fw-semibold px-3" href="cadastrarCliente.php">Criar conta</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="catalogo-hero">
  <div class="container" style="position:relative;z-index:1;">
    <?php if (($_GET['aviso'] ?? '') === 'solicitar'): ?>
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="max-width:560px;">
      <span>👇 Escolha um prestador abaixo e clique em <strong>Solicitar</strong>.</span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <h1>Catálogo de Prestadores</h1>
    <p class="mt-2 mb-4">Encontre o profissional ideal. Todos os prestadores são verificados pela Fix Now.</p>
    <div class="hero-search">
      <span style="font-size:1.1rem;opacity:.7;">🔍</span>
      <input type="text" id="buscaNome" placeholder="Buscar por nome ou especialidade…" autocomplete="off">
      <button type="button" onclick="document.getElementById('buscaNome').focus()">Buscar</button>
    </div>
  </div>
</section>

<!-- Filtros de categoria (sticky) -->
<div class="filtros-bar">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a href="catalogo.php" class="btn-cat <?= !$filtroCategoria ? 'ativo' : '' ?>">🗂 Todos</a>
      <?php foreach ($categorias as $cat):
        $ico = $catIcons[$cat['nome']] ?? '🔩';
      ?>
        <a href="catalogo.php?categoria=<?= urlencode($cat['nome']) ?>"
           class="btn-cat <?= $filtroCategoria === $cat['nome'] ? 'ativo' : '' ?>">
          <?= $ico ?> <?= htmlspecialchars($cat['nome']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<main class="container py-4">

  <!-- Contador de resultados -->
  <div class="resultado-count" id="resultadoCount">
    <?php
      $total = count($prestadores);
      echo $filtroCategoria
        ? "<strong>{$total}</strong> prestador" . ($total !== 1 ? 'es' : '') . " em <em>" . htmlspecialchars($filtroCategoria) . "</em>"
        : "<strong>{$total}</strong> prestador" . ($total !== 1 ? 'es' : '') . " disponíve" . ($total !== 1 ? 'is' : 'l');
    ?>
  </div>

  <?php if (!$prestadores): ?>
    <div class="empty-state">
      <div class="icone-empty">🔍</div>
      <h5>Nenhum prestador encontrado</h5>
      <p>Tente outra categoria ou <a href="catalogo.php" class="text-primary">veja todos os prestadores</a>.</p>
    </div>
  <?php else: ?>
    <div class="row g-4" id="gridPrestadores">
      <?php foreach ($prestadores as $p):
        $media = (float)$p['avaliacao_media'];
        $estrelasCheias = floor($media);
        $totalServicos  = count($p['servicos']);
      ?>
        <div class="col-12 col-sm-6 col-md-6 col-lg-4 card-prestador-item"
             data-nome="<?= strtolower(htmlspecialchars($p['nome'])) ?>"
             data-especialidade="<?= strtolower(htmlspecialchars($p['especialidade'] ?? '')) ?>">
          <div class="card-prestador">

            <!-- Cabeçalho colorido com foto sobreposta -->
            <div class="card-prestador-header" style="min-height:60px;">
              <div class="foto-wrap">
                <?php if ($p['foto_perfil']): ?>
                  <img src="../<?= htmlspecialchars($p['foto_perfil']) ?>" alt="" class="foto-prestador">
                <?php else: ?>
                  <div class="foto-prestador-fallback"><?= mb_strtoupper(mb_substr($p['nome'], 0, 1)) ?></div>
                <?php endif; ?>
              </div>
              <?php if ($p['destaque']): ?>
                <span class="badge-destaque">⭐ Destaque</span>
              <?php endif; ?>
            </div>

            <!-- Corpo -->
            <div class="card-prestador-body">
              <h6><?= htmlspecialchars($p['nome']) ?></h6>
              <div class="especialidade-text"><?= htmlspecialchars($p['especialidade'] ?? 'Prestador de serviços') ?></div>

              <!-- Avaliação com estrelas -->
              <div class="estrelas-rating">
                <span class="stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?= $i <= $estrelasCheias ? '★' : '☆' ?>
                  <?php endfor; ?>
                </span>
                <?php if ($media > 0): ?>
                  <span class="val"><?= number_format($media, 1) ?> / 5</span>
                <?php else: ?>
                  <span class="val" style="color:#adb5bd;">Sem avaliações</span>
                <?php endif; ?>
              </div>

              <!-- Serviços como tags -->
              <div class="servicos-lista">
                <?php foreach ($p['servicos'] as $sv): ?>
                  <span class="tag-servico">
                    <?= ($catIcons[$sv['categoria_nome']] ?? '🔩') ?> <?= htmlspecialchars($sv['nome']) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Rodapé com botões -->
            <div class="card-prestador-footer">
              <a href="portfolioPublico.php?id=<?= (int)$p['id'] ?>"
                 class="btn btn-sm btn-outline-secondary flex-grow-1">Ver portfólio</a>
              <?php if (isset($_SESSION['cliente_id'])): ?>
                <a href="cliente/solicitar.php?prestador=<?= (int)$p['id'] ?>"
                   class="btn btn-sm btn-warning fw-semibold flex-grow-1">Solicitar</a>
              <?php else: ?>
                <a href="login.php?redirect=catalogo"
                   class="btn btn-sm btn-warning fw-semibold flex-grow-1">Contratar</a>
              <?php endif; ?>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Estado vazio após busca JS -->
    <div id="semResultados" class="empty-state d-none">
      <div class="icone-empty">😕</div>
      <h5>Nenhum prestador encontrado</h5>
      <p>Tente outro nome ou limpe a busca.</p>
    </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
(function () {
  var busca    = document.getElementById('buscaNome');
  var items    = document.querySelectorAll('.card-prestador-item');
  var semRes   = document.getElementById('semResultados');
  var countEl  = document.getElementById('resultadoCount');
  var totalFix = items.length;

  if (!busca) return;

  busca.addEventListener('input', function () {
    var q = busca.value.trim().toLowerCase();
    var visiveis = 0;
    items.forEach(function (el) {
      var nome = el.dataset.nome || '';
      var esp  = el.dataset.especialidade || '';
      var show = !q || nome.includes(q) || esp.includes(q);
      el.style.display = show ? '' : 'none';
      if (show) visiveis++;
    });
    if (semRes) semRes.classList.toggle('d-none', visiveis > 0);
    if (countEl) {
      if (q) {
        countEl.innerHTML = '<strong>' + visiveis + '</strong> prestador' + (visiveis !== 1 ? 'es' : '') + ' encontrado' + (visiveis !== 1 ? 's' : '') + ' para <em>"' + q.replace(/</g,'&lt;') + '"</em>';
      } else {
        countEl.innerHTML = '<strong>' + totalFix + '</strong> prestador' + (totalFix !== 1 ? 'es' : '') + ' disponíve' + (totalFix !== 1 ? 'is' : 'l');
      }
    }
  });
})();
</script>
</body>
</html>
