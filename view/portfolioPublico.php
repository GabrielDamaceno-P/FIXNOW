<?php
session_start();
require_once __DIR__ . '/../controller/PortfolioPublicoControl.php';

$ctrl = new PortfolioPublicoControl();
$ctrl->processar();

$tec       = $ctrl->tecnico;
$portfolio = $ctrl->portfolio;
$avaliacoes = $ctrl->avaliacoes;
$servicos  = $ctrl->servicos;

$_urlInicio = '../index.php';
if (isset($_SESSION['cliente_id']))      $_urlInicio = 'dashboardCliente.php';
elseif (isset($_SESSION['tecnico_id'])) $_urlInicio = 'prestador/dashboardPrestador.php';
elseif (isset($_SESSION['admin_id']))   $_urlInicio = 'admin/painelAdmin.php';

$catIcons = ['Suporte TI'=>'💻','Elétrica'=>'⚡','Hidráulica'=>'🔧','Pintura'=>'🎨','Marcenaria'=>'🪚'];
$mediaFormatada = $tec->avaliacaoMedia > 0 ? number_format($tec->avaliacaoMedia, 1) : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($tec->nome); ?> - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    /* Hero */
    .perfil-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);padding:2.5rem 0 0;position:relative;overflow:hidden;margin-bottom:0}
    .perfil-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .perfil-avatar{width:96px;height:96px;border-radius:50%;object-fit:cover;border:4px solid rgba(255,255,255,.25);box-shadow:0 6px 20px rgba(0,0,0,.3)}
    .perfil-avatar-fallback{width:96px;height:96px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:700;color:#fff;border:4px solid rgba(255,255,255,.25);background:rgba(255,255,255,.15);box-shadow:0 6px 20px rgba(0,0,0,.3)}
    .perfil-nome{color:#fff;font-size:clamp(1.3rem,4vw,1.9rem);font-weight:800;margin:0 0 .2rem}
    .perfil-esp{color:rgba(255,255,255,.72);font-size:.92rem;margin:0}
    .perfil-rating{display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.12);border-radius:50px;padding:.25rem .8rem;font-size:.88rem;color:#fff;margin-top:.5rem}
    .perfil-rating .stars{color:#ffc107}
    .badge-destaque{background:linear-gradient(90deg,#f59e0b,#f97316);color:#fff;font-size:.7rem;font-weight:700;padding:.25rem .65rem;border-radius:50px}
    .perfil-wave{display:block;width:100%;height:50px;margin-bottom:-2px}

    /* Secção títulos */
    .secao-titulo{font-size:1.05rem;font-weight:800;color:#0d1b3d;margin-bottom:1.1rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}

    /* Serviços */
    .servico-pub-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:12px;padding:.9rem 1rem;display:flex;align-items:center;gap:.8rem;transition:box-shadow .18s}
    .servico-pub-card:hover{box-shadow:0 4px 14px rgba(13,27,61,.1)}
    .servico-pub-icon{width:40px;height:40px;border-radius:10px;background:#f0f3fa;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
    .servico-pub-nome{font-weight:700;font-size:.9rem;color:#0d1b3d;margin:0 0 .1rem}
    .servico-pub-cat{font-size:.76rem;color:#667085}

    /* Portfólio */
    .foto-pub-card{border-radius:12px;overflow:hidden;border:1.5px solid #e8ecf3;background:#fff;box-shadow:0 3px 10px rgba(13,27,61,.06);transition:transform .18s,box-shadow .18s;cursor:pointer}
    .foto-pub-card:hover{transform:translateY(-3px);box-shadow:0 10px 22px rgba(13,27,61,.12)}
    .foto-pub-card img{width:100%;height:190px;object-fit:cover;display:block}
    .foto-pub-card .info{padding:.6rem .8rem}
    .foto-pub-card .info .tit{font-size:.85rem;font-weight:700;color:#0d1b3d;margin:0 0 .1rem}
    .foto-pub-card .info .desc{font-size:.77rem;color:#667085;margin:0}

    /* Lightbox overlay */
    #lb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:2000;align-items:center;justify-content:center;cursor:zoom-out}
    #lb-overlay.ativo{display:flex}
    #lb-overlay img{max-width:90vw;max-height:88vh;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.6)}

    /* Avaliações */
    .av-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:12px;padding:1rem 1.1rem;transition:box-shadow .18s}
    .av-card:hover{box-shadow:0 4px 14px rgba(13,27,61,.1)}
    .av-stars{color:#f59e0b;font-size:1rem;letter-spacing:.05rem}
    .av-coment{font-size:.88rem;color:#374151;margin:.4rem 0 .3rem}
    .av-data{font-size:.74rem;color:#9ca3af}

    /* CTA Banner */
    .cta-banner{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;text-align:center;margin-top:2rem}
    .cta-banner h4{color:#fff;font-weight:800;margin:0 0 .4rem}
    .cta-banner p{color:rgba(255,255,255,.75);font-size:.9rem;margin:0 0 1.1rem}

    /* Tab filtros portfólio */
    .tab-filtro{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1.2rem}
    .tab-btn{border:1.5px solid #e8ecf3;border-radius:50px;padding:.3rem .85rem;font-size:.8rem;font-weight:600;color:#495057;background:#fff;cursor:pointer;transition:all .18s;white-space:nowrap}
    .tab-btn:hover,.tab-btn.ativo{background:#0d1b3d;border-color:#0d1b3d;color:#fff}

    /* Dark mode */
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .servico-pub-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .servico-pub-icon{background:#252d42}
    [data-theme="dark"] .servico-pub-nome{color:#e4e8f4}
    [data-theme="dark"] .servico-pub-cat{color:#8090b0}
    [data-theme="dark"] .foto-pub-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .foto-pub-card .info .tit{color:#e4e8f4}
    [data-theme="dark"] .foto-pub-card .info .desc{color:#8090b0}
    [data-theme="dark"] .av-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .av-coment{color:#c8d0e0}
    [data-theme="dark"] .tab-btn{background:#252d42;border-color:#2e3650;color:#c8d0e0}
    [data-theme="dark"] .tab-btn:hover,[data-theme="dark"] .tab-btn.ativo{background:#ffc107;border-color:#ffc107;color:#0d1b3d}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="catalogo.php">Catálogo</a></li>
        <?php if (isset($_SESSION['cliente_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="dashboardCliente.php">Dashboard</a></li>
        <?php elseif (isset($_SESSION['tecnico_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="prestador/dashboardPrestador.php">Dashboard</a></li>
        <?php elseif (isset($_SESSION['admin_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="admin/painelAdmin.php">Painel Admin</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero do prestador -->
<div class="perfil-hero mt-5">
  <div class="container">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-end gap-3 pb-3" style="position:relative;z-index:1">
      <!-- Avatar -->
      <?php if ($tec->fotoPerfil): ?>
        <img src="../<?php echo htmlspecialchars($tec->fotoPerfil); ?>" alt="Foto" class="perfil-avatar">
      <?php else: ?>
        <div class="perfil-avatar-fallback"><?php echo mb_strtoupper(mb_substr($tec->nome, 0, 1)); ?></div>
      <?php endif; ?>

      <!-- Info -->
      <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
          <h1 class="perfil-nome"><?php echo htmlspecialchars($tec->nome); ?></h1>
          <?php if ($tec->destaque): ?>
            <span class="badge-destaque">⭐ Destaque</span>
          <?php endif; ?>
        </div>
        <?php if ($tec->especialidade): ?>
          <p class="perfil-esp"><?php echo htmlspecialchars($tec->especialidade); ?></p>
        <?php endif; ?>
        <?php if ($mediaFormatada): ?>
          <div class="perfil-rating">
            <span class="stars">★</span>
            <strong><?php echo $mediaFormatada; ?></strong>
            <span style="opacity:.7"><?php echo count($avaliacoes); ?> avaliação<?php echo count($avaliacoes) !== 1 ? 'ões' : ''; ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- CTA -->
      <div class="mt-2 mt-sm-0 flex-shrink-0">
        <?php if (isset($_SESSION['cliente_id'])): ?>
          <a href="cliente/solicitar.php?prestador=<?php echo $tec->id; ?>"
             class="btn btn-warning fw-bold px-4 py-2">Solicitar serviço</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-warning fw-bold px-4 py-2">Contratar</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats rápidas -->
    <div class="d-flex flex-wrap gap-3 pb-3" style="position:relative;z-index:1">
      <?php if ($servicos): ?>
        <span style="color:rgba(255,255,255,.65);font-size:.82rem;">🔧 <?php echo count($servicos); ?> serviço<?php echo count($servicos) !== 1 ? 's' : ''; ?></span>
      <?php endif; ?>
      <?php if ($portfolio): ?>
        <span style="color:rgba(255,255,255,.65);font-size:.82rem;">🖼 <?php echo count($portfolio); ?> foto<?php echo count($portfolio) !== 1 ? 's' : ''; ?> no portfólio</span>
      <?php endif; ?>
      <?php if ($avaliacoes): ?>
        <span style="color:rgba(255,255,255,.65);font-size:.82rem;">⭐ <?php echo count($avaliacoes); ?> avaliação<?php echo count($avaliacoes) !== 1 ? 'ões' : ''; ?></span>
      <?php endif; ?>
    </div>
  </div>
  <svg class="perfil-wave" viewBox="0 0 1440 50" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0,30 C360,55 1080,5 1440,30 L1440,50 L0,50 Z" fill="var(--bs-body-bg,#f8f9fa)"/>
  </svg>
</div>

<main class="container py-4">

  <!-- Botão voltar -->
  <div class="mb-4">
    <a href="catalogo.php" class="btn btn-sm btn-outline-secondary">&larr; Voltar ao catálogo</a>
  </div>

  <!-- Serviços -->
  <?php if ($servicos): ?>
  <div class="mb-5">
    <div class="secao-titulo"><span>🔧</span> Serviços oferecidos</div>
    <div class="row g-3">
      <?php foreach ($servicos as $s):
        $ico = $catIcons[$s->categoriaNome ?? ''] ?? '🔩';
      ?>
      <div class="col-sm-6 col-lg-4">
        <div class="servico-pub-card">
          <div class="servico-pub-icon"><?php echo $ico; ?></div>
          <div>
            <div class="servico-pub-nome"><?php echo htmlspecialchars($s->nome ?: ($s->categoriaNome ?? '')); ?></div>
            <div class="servico-pub-cat"><?php echo htmlspecialchars($s->categoriaNome ?? 'Geral'); ?></div>
            <?php if ($s->descricao): ?>
              <div style="font-size:.77rem;color:#9ca3af;margin-top:.15rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                <?php echo htmlspecialchars($s->descricao); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Portfólio -->
  <?php if ($portfolio): ?>
  <?php
    $portCats = [];
    foreach ($portfolio as $item) {
        $cat = $item['categoria_nome'] ?? null;
        $portCats[$cat ?? '__sem__'][] = $item;
    }
    $temCategorias = !(count($portCats) === 1 && isset($portCats['__sem__']));
  ?>
  <div class="mb-5">
    <div class="secao-titulo"><span>🖼</span> Portfólio</div>

    <?php if ($temCategorias): ?>
    <div class="tab-filtro" id="porto-tabs">
      <button class="tab-btn ativo" data-tab="todos">🗂 Todos (<?php echo count($portfolio); ?>)</button>
      <?php foreach ($portCats as $catNome => $itens):
        if ($catNome === '__sem__') continue;
        $ico = $catIcons[$catNome] ?? '🔩';
      ?>
        <button class="tab-btn" data-tab="pcat-<?php echo htmlspecialchars($catNome); ?>">
          <?php echo $ico . ' ' . htmlspecialchars($catNome); ?> (<?php echo count($itens); ?>)
        </button>
      <?php endforeach; ?>
      <?php if (!empty($portCats['__sem__'])): ?>
        <button class="tab-btn" data-tab="pcat-sem">Sem categoria (<?php echo count($portCats['__sem__']); ?>)</button>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row g-3" id="galeria-pub">
      <?php foreach ($portfolio as $item):
        $catNome = $item['categoria_nome'] ?? null;
        $dataTab = $catNome ? 'pcat-' . $catNome : 'pcat-sem';
      ?>
      <div class="col-6 col-md-4 col-lg-3 porto-item" data-tab="<?php echo htmlspecialchars($dataTab); ?>">
        <div class="foto-pub-card" onclick="abrirLightbox('<?php echo htmlspecialchars('../' . $item['foto_path']); ?>')">
          <?php if (!empty($item['foto_path'])): ?>
            <img src="../<?php echo htmlspecialchars($item['foto_path']); ?>" alt="Portfólio" loading="lazy">
          <?php endif; ?>
          <div class="info">
            <?php if (!empty($item['categoria_nome'])): ?>
              <span style="font-size:.7rem;font-weight:700;color:#c95e00;"><?php echo ($catIcons[$item['categoria_nome']] ?? '🔩') . ' ' . htmlspecialchars($item['categoria_nome']); ?></span>
            <?php endif; ?>
            <?php if (!empty($item['titulo'])): ?>
              <p class="tit"><?php echo htmlspecialchars($item['titulo']); ?></p>
            <?php endif; ?>
            <?php if (!empty($item['descricao'])): ?>
              <p class="desc"><?php echo htmlspecialchars(mb_strimwidth($item['descricao'], 0, 65, '...')); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Avaliações -->
  <?php if ($avaliacoes): ?>
  <div class="mb-5">
    <div class="secao-titulo">
      <span>⭐</span> Avaliações dos clientes
      <?php if ($mediaFormatada): ?>
        <span style="font-size:.85rem;font-weight:700;color:#f59e0b;margin-left:auto;"><?php echo $mediaFormatada; ?>/5</span>
      <?php endif; ?>
    </div>
    <div class="row g-3">
      <?php foreach (array_slice($avaliacoes, 0, 6) as $av):
        $nota = is_array($av) ? (int)$av['nota'] : (int)$av->nota;
        $comentario = is_array($av) ? ($av['comentario'] ?? '') : ($av->comentario ?? '');
        $criadoEm = is_array($av) ? $av['criado_em'] : $av->criadoEm;
      ?>
      <div class="col-md-6">
        <div class="av-card">
          <div class="av-stars"><?php echo str_repeat('★', $nota) . str_repeat('☆', 5 - $nota); ?></div>
          <?php if (!empty($comentario)): ?>
            <p class="av-coment">"<?php echo htmlspecialchars($comentario); ?>"</p>
          <?php endif; ?>
          <div class="av-data">📅 <?php echo date('d/m/Y', strtotime($criadoEm)); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Portfólio e avaliações vazios -->
  <?php if (!$portfolio && !$servicos && !$avaliacoes): ?>
  <div class="text-center py-5 text-muted">
    <div style="font-size:3rem;margin-bottom:.6rem;">🔧</div>
    <h5 class="fw-bold">Perfil em construção</h5>
    <p style="font-size:.9rem;">Este prestador ainda não adicionou serviços ou fotos.</p>
  </div>
  <?php endif; ?>

  <!-- CTA final -->
  <?php if (isset($_SESSION['cliente_id']) || !isset($_SESSION['tecnico_id'])): ?>
  <div class="cta-banner">
    <h4>Gostou do trabalho de <?php echo htmlspecialchars($tec->nome); ?>?</h4>
    <p>Solicite um orçamento agora mesmo, é rápido e gratuito.</p>
    <?php if (isset($_SESSION['cliente_id'])): ?>
      <a href="cliente/solicitar.php?prestador=<?php echo $tec->id; ?>" class="btn btn-warning fw-bold px-5">Solicitar serviço</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-warning fw-bold px-5">Criar conta e contratar</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</main>

<!-- Lightbox -->
<div id="lb-overlay" onclick="fecharLightbox()">
  <img id="lb-img" src="" alt="Foto ampliada">
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
function abrirLightbox(src) {
  document.getElementById('lb-img').src = src;
  document.getElementById('lb-overlay').classList.add('ativo');
  document.body.style.overflow = 'hidden';
}
function fecharLightbox() {
  document.getElementById('lb-overlay').classList.remove('ativo');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') fecharLightbox();
});

document.querySelectorAll('#porto-tabs .tab-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('#porto-tabs .tab-btn').forEach(b => b.classList.remove('ativo'));
    this.classList.add('ativo');
    var tab = this.dataset.tab;
    document.querySelectorAll('.porto-item').forEach(function(item) {
      item.style.display = (tab === 'todos' || item.dataset.tab === tab) ? '' : 'none';
    });
  });
});
</script>
</body>
</html>
