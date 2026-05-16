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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogo de Prestadores - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= $_urlInicio ?>">Início</a></li>
        <li class="nav-item"><a class="nav-link active" href="catalogo.php">Catálogo</a></li>
        <?php if (isset($_SESSION['cliente_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="dashboardCliente.php">Dashboard</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <?php if (($_GET['aviso'] ?? '') === 'solicitar'): ?>
  <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-info-circle-fill fs-5"></i>
    <span>Para solicitar um serviço, escolha um prestador abaixo e clique em <strong>Solicitar serviço</strong>.</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <h2 class="mb-1">Catálogo de Prestadores</h2>
  <p class="text-muted mb-4">Encontre o profissional ideal para o seu problema.</p>

  <div class="mb-4 d-flex flex-wrap gap-2">
    <a href="catalogo.php" class="btn btn-sm <?php echo !$filtroCategoria ? 'btn-primary' : 'btn-outline-primary'; ?>">Todos</a>
    <?php foreach ($categorias as $cat): ?>
      <a href="catalogo.php?categoria=<?php echo urlencode($cat['nome']); ?>"
         class="btn btn-sm <?php echo $filtroCategoria === $cat['nome'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
        <?php echo htmlspecialchars($cat['nome']); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$prestadores): ?>
    <div class="alert alert-info">Nenhum prestador encontrado para esta categoria.</div>
  <?php else: ?>
    <div class="row g-4">
    <?php foreach ($prestadores as $p): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
              <?php if ($p['foto_perfil']): ?>
                <img src="../<?php echo htmlspecialchars($p['foto_perfil']); ?>" width="56" height="56"
                     class="rounded-circle object-fit-cover border" alt="">
              <?php else: ?>
                <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5 border"
                      style="width:56px;height:56px;"><?php echo mb_substr(htmlspecialchars($p['nome']), 0, 1); ?></span>
              <?php endif; ?>
              <div>
                <h6 class="mb-0"><?php echo htmlspecialchars($p['nome']); ?></h6>
                <small class="text-muted"><?php echo htmlspecialchars($p['especialidade']); ?></small>
                <?php if ((float)$p['avaliacao_media'] > 0): ?>
                  <div class="text-warning small fw-semibold">★ <?php echo number_format((float)$p['avaliacao_media'], 1); ?></div>
                <?php endif; ?>
              </div>
              <?php if ($p['destaque']): ?>
                <span class="badge bg-warning text-dark ms-auto">Destaque</span>
              <?php endif; ?>
            </div>
            <?php foreach ($p['servicos'] as $sv): ?>
              <div class="border rounded p-2 mb-2">
                <div class="fw-semibold small"><?php echo htmlspecialchars($sv['nome']); ?></div>
                <?php if ($sv['categoria_nome']): ?>
                  <span class="badge bg-light text-secondary border small"><?php echo htmlspecialchars($sv['categoria_nome']); ?></span>
                <?php endif; ?>
                <div class="small text-muted">Orçamento após avaliação do problema</div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card-footer bg-transparent border-0 d-flex gap-2 pb-3">
            <a href="portfolioPublico.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-secondary flex-grow-1">Ver portfólio</a>
            <?php if (isset($_SESSION['cliente_id'])): ?>
              <a href="cliente/solicitar.php?prestador=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-warning fw-semibold flex-grow-1">Solicitar</a>
            <?php else: ?>
              <a href="login.php" class="btn btn-sm btn-warning fw-semibold flex-grow-1">Contratar</a>
            <?php endif; ?>
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
