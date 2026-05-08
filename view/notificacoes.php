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
    <?php if ($usuarioFoto): ?>
      <span class="fn-user-badge me-2">
        <img src="../<?php echo htmlspecialchars($usuarioFoto); ?>" alt="" width="44" height="44">
        <span><?php echo htmlspecialchars($usuarioNome); ?></span>
      </span>
    <?php elseif ($usuarioNome): ?>
      <span class="fn-user-badge me-2">
        <span class="fallback"><?php echo htmlspecialchars(mb_substr($usuarioNome, 0, 1)); ?></span>
        <span><?php echo htmlspecialchars($usuarioNome); ?></span>
      </span>
    <?php endif; ?>
    <a class="navbar-brand fw-bold" href="../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?php echo $dashLink; ?>">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="notificacoes.php">Notificações</a></li>
        <li class="nav-item"><a class="nav-link" href="perfil.php">Perfil</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $usuarioTipo === 'prestador' ? '../logout.php?entidade=prestador' : '../logout.php'; ?>">Sair</a></li>
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
      <?php $lida = (int)$n['lida']; ?>
      <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?php echo $lida ? '' : 'list-group-item-warning'; ?>">
        <div>
          <p class="mb-1"><?php echo htmlspecialchars($n['mensagem']); ?></p>
          <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($n['criado_em'])); ?></small>
          <?php if ($n['chamado_id']): ?>
            <a href="chat.php?chamado=<?php echo (int)$n['chamado_id']; ?>" class="ms-2 small">💬 Ver chamado</a>
          <?php endif; ?>
        </div>
        <?php if (!$lida): ?>
          <a href="notificacoes.php?lida=<?php echo (int)$n['id']; ?>" class="btn btn-sm btn-outline-secondary ms-2 text-nowrap">Marcar lida</a>
        <?php else: ?>
          <span class="badge bg-secondary ms-2">Lida</span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?php echo date('Y'); ?> Fix Now.</small></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
