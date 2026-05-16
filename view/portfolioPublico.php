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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($tec->nome); ?> - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
    <div class="collapse navbar-collapse">
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

<main class="container py-5 mt-5">
  <!-- Cabeçalho do prestador -->
  <div class="row g-4 mb-5 align-items-center">
    <div class="col-auto">
      <?php if ($tec->fotoPerfil): ?>
        <img src="../<?php echo htmlspecialchars($tec->fotoPerfil); ?>" alt="Foto"
             class="rounded-circle shadow" width="110" height="110" style="object-fit:cover;">
      <?php else: ?>
        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center shadow"
             style="width:110px;height:110px;font-size:2.5rem;color:#fff;">
          <?php echo mb_strtoupper(mb_substr($tec->nome, 0, 1)); ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="col">
      <h1 class="h2 mb-1"><?php echo htmlspecialchars($tec->nome); ?></h1>
      <p class="text-muted mb-1"><?php echo htmlspecialchars($tec->especialidade ?? ''); ?></p>
      <?php if ($tec->avaliacaoMedia > 0): ?>
        <span class="text-warning fs-5">★</span>
        <span class="fw-semibold"><?php echo number_format($tec->avaliacaoMedia, 1); ?></span>
        <span class="text-muted small">(<?php echo count($avaliacoes); ?> avaliações)</span>
      <?php endif; ?>
      <?php if ($tec->destaque): ?>
        <span class="badge bg-warning text-dark ms-2">Destaque</span>
      <?php endif; ?>
    </div>
    <div class="col-md-auto">
      <?php if (isset($_SESSION['cliente_id'])): ?>
        <a href="cliente/solicitar.php?prestador=<?php echo $tec->id; ?>"
           class="btn btn-warning fw-semibold px-4">Solicitar serviço</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-warning fw-semibold px-4">Contratar</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Serviços -->
  <?php if ($servicos): ?>
  <h2 class="h4 mb-3">Serviços oferecidos</h2>
  <div class="row g-3 mb-5">
    <?php foreach ($servicos as $s): ?>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title"><?php echo htmlspecialchars($s->nome ?: ($s->categoriaNome ?? '')); ?></h6>
          <?php if ($s->descricao): ?>
            <p class="small text-muted"><?php echo htmlspecialchars(mb_strimwidth($s->descricao, 0, 100, '...')); ?></p>
          <?php endif; ?>
          <p class="mb-0 small text-muted">Orçamento sob consulta</p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Portfólio -->
  <?php if ($portfolio): ?>
  <h2 class="h4 mb-3">Portfólio</h2>
  <?php
    // Agrupa por categoria se disponível
    $portCats = [];
    foreach ($portfolio as $item) {
        $cat = $item['categoria_nome'] ?? null;
        $portCats[$cat ?? ''][] = $item;
    }
    $temCategorias = count($portCats) > 1 || !isset($portCats['']);
  ?>
  <?php if ($temCategorias): ?>
    <?php foreach ($portCats as $catNome => $itens): ?>
      <?php if ($catNome): ?>
        <h5 class="mb-2 mt-4 text-muted"><?php echo htmlspecialchars($catNome); ?></h5>
      <?php endif; ?>
      <div class="row g-3 mb-3">
        <?php foreach ($itens as $item): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="card border-0 shadow-sm h-100">
            <?php if (!empty($item['foto_path'])): ?>
              <img src="../<?php echo htmlspecialchars($item['foto_path']); ?>" class="card-img-top"
                   alt="Portfólio" style="height:180px;object-fit:cover;cursor:pointer;"
                   onclick="window.open('../<?php echo htmlspecialchars($item['foto_path']); ?>','_blank')">
            <?php endif; ?>
            <div class="card-body p-2">
              <?php if (!empty($item['titulo'])): ?>
                <p class="small mb-0 fw-semibold"><?php echo htmlspecialchars($item['titulo']); ?></p>
              <?php endif; ?>
              <?php if (!empty($item['descricao'])): ?>
                <p class="small text-muted mb-0"><?php echo htmlspecialchars(mb_strimwidth($item['descricao'], 0, 60, '...')); ?></p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
  <div class="row g-3 mb-5">
    <?php foreach ($portfolio as $item): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <?php if (!empty($item['foto_path'])): ?>
          <img src="../<?php echo htmlspecialchars($item['foto_path']); ?>" class="card-img-top"
               alt="Portfólio" style="height:180px;object-fit:cover;cursor:pointer;"
               onclick="window.open('../<?php echo htmlspecialchars($item['foto_path']); ?>','_blank')">
        <?php endif; ?>
        <div class="card-body p-2">
          <?php if (!empty($item['titulo'])): ?>
            <p class="small mb-0 fw-semibold"><?php echo htmlspecialchars($item['titulo']); ?></p>
          <?php endif; ?>
          <?php if (!empty($item['descricao'])): ?>
            <p class="small text-muted mb-0"><?php echo htmlspecialchars(mb_strimwidth($item['descricao'], 0, 60, '...')); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Avaliações -->
  <?php if ($avaliacoes): ?>
  <h2 class="h4 mb-3">Avaliações dos clientes</h2>
  <div class="row g-3">
    <?php foreach (array_slice($avaliacoes, 0, 6) as $av): ?>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <?php $nota = is_array($av) ? (int)$av['nota'] : (int)$av->nota; ?>
          <div class="text-warning mb-1"><?php echo str_repeat('★', $nota) . str_repeat('☆', 5 - $nota); ?></div>
          <?php $comentario = is_array($av) ? ($av['comentario'] ?? '') : ($av->comentario ?? ''); ?>
          <?php if (!empty($comentario)): ?>
            <p class="small mb-1"><?php echo htmlspecialchars($comentario); ?></p>
          <?php endif; ?>
          <?php $criadoEm = is_array($av) ? $av['criado_em'] : $av->criadoEm; ?>
          <small class="text-muted"><?php echo date('d/m/Y', strtotime($criadoEm)); ?></small>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
