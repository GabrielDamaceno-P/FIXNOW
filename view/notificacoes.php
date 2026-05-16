<?php
session_start();
require_once __DIR__ . '/../controller/NotificacoesControl.php';

$ctrl = new NotificacoesControl();
$ctrl->processar();
$notificacoes = $ctrl->notificacoes;
$naoLidas     = $ctrl->naoLidas;
$usuarioTipo  = $ctrl->usuarioTipo;
$usuarioNome  = $ctrl->usuarioNome;
$usuarioFoto  = $ctrl->usuarioFoto;

$dashLink = match($usuarioTipo) {
    'admin'     => 'admin/painelAdmin.php',
    'prestador' => 'prestador/dashboardPrestador.php',
    default     => 'dashboardCliente.php',
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Notificações - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?php echo $dashLink; ?>">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#notifMenu" aria-controls="notifMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="notifMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?php echo $dashLink; ?>"><i class="bi bi-house me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="notificacoes.php"><i class="bi bi-bell me-1"></i>Notificações</a></li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($usuarioFoto): ?>
              <img src="../<?php echo htmlspecialchars($usuarioFoto); ?>" alt="" width="32" height="32" class="rounded-circle border border-2 border-white border-opacity-50" style="object-fit:cover">
            <?php else: ?>
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:.85rem"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($usuarioNome ?? '', 0, 1))); ?></span>
            <?php endif; ?>
            <span class="d-none d-lg-inline text-truncate" style="max-width:120px"><?php echo htmlspecialchars($usuarioNome ?? ''); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:180px">
            <li><a class="dropdown-item py-2" href="perfil.php"><i class="bi bi-person me-2 text-primary"></i>Perfil</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="<?php echo $usuarioTipo === 'prestador' ? '../logout.php?entidade=prestador' : '../logout.php'; ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Notificações
      <?php if ($naoLidas > 0): ?>
        <span class="badge bg-danger ms-2"><?php echo $naoLidas; ?></span>
      <?php endif; ?>
    </h2>
    <?php if ($naoLidas > 0): ?>
      <form method="post">
        <button name="marcar_todas" class="btn btn-sm btn-outline-secondary">Marcar todas como lidas</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$notificacoes): ?>
    <div class="alert alert-info">Nenhuma notificação encontrada.</div>
  <?php else: ?>
    <div class="list-group shadow-sm">
    <?php foreach ($notificacoes as $n): ?>
      <?php $lida = $n->lida; ?>
      <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?php echo $lida ? '' : 'list-group-item-warning'; ?>">
        <div>
          <p class="mb-1"><?php echo htmlspecialchars($n->mensagem); ?></p>
          <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($n->criadoEm)); ?></small>
          <?php if ($n->chamadoId): ?>
            <a href="chat.php?chamado=<?php echo $n->chamadoId; ?>" class="ms-2 small">💬 Ver chamado</a>
          <?php endif; ?>
        </div>
        <?php if (!$lida): ?>
          <a href="notificacoes.php?lida=<?php echo $n->id; ?>" class="btn btn-sm btn-outline-secondary ms-2 text-nowrap">Marcar lida</a>
        <?php else: ?>
          <span class="badge bg-secondary ms-2">Lida</span>
        <?php endif; ?>
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
