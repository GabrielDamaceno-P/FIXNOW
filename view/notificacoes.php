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
$sairLink = match($usuarioTipo) {
    'admin'     => '../logout.php?entidade=admin',
    'prestador' => '../logout.php?entidade=prestador',
    default     => '../logout.php',
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
  <style>
    .notif-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .notif-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .notif-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .notif-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}

    .notif-item{background:#fff;border:1.5px solid #e8ecf3;border-radius:12px;padding:1rem 1.1rem;margin-bottom:.6rem;display:flex;align-items:flex-start;gap:.9rem;transition:box-shadow .15s}
    .notif-item:hover{box-shadow:0 4px 14px rgba(13,27,61,.08)}
    .notif-item.nao-lida{border-left:4px solid #f97316;background:#fffbf5}
    .notif-item.lida{opacity:.85}

    .notif-icone{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
    .notif-icone.nao-lida{background:#fff3e0}
    .notif-icone.lida{background:#f3f4f6}

    .notif-msg{font-size:.88rem;color:#1a1a2e;line-height:1.5;margin-bottom:.3rem}
    .notif-meta{font-size:.75rem;color:#9ca3af;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}

    .notif-actions{display:flex;flex-direction:column;align-items:flex-end;gap:.4rem;flex-shrink:0;margin-left:auto}

    [data-theme="dark"] .notif-item{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .notif-item.nao-lida{background:#1f2b3a;border-left-color:#f97316}
    [data-theme="dark"] .notif-item.lida{opacity:.75}
    [data-theme="dark"] .notif-icone.nao-lida{background:#2d2518}
    [data-theme="dark"] .notif-icone.lida{background:#252e45}
    [data-theme="dark"] .notif-msg{color:#e4e8f4}
    [data-theme="dark"] .notif-meta{color:#6b7280}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top shadow-sm" style="background:linear-gradient(90deg,#0d1b3d,#1a2b63);">
  <div class="container">
    <a class="navbar-brand fw-bold text-white" href="<?= $dashLink ?>">Fix Now</a>
    <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#notifMenu" aria-controls="notifMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse" id="notifMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link text-white-50" href="<?= $dashLink ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6-.354.353V14.5A1.5 1.5 0 0 0 2.5 16h4a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5h4a1.5 1.5 0 0 0 1.5-1.5V7.5l-.354-.354z"/></svg>
            Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="notificacoes.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/></svg>
            Notificações
            <?php if ($naoLidas > 0): ?>
              <span class="badge rounded-pill ms-1" style="background:#f97316;font-size:.65rem"><?= $naoLidas ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($usuarioFoto): ?>
              <img src="../<?= htmlspecialchars($usuarioFoto) ?>" alt="" width="32" height="32" class="rounded-circle border border-2" style="border-color:rgba(255,255,255,.3)!important;object-fit:cover">
            <?php else: ?>
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold flex-shrink-0"
                    style="width:32px;height:32px;font-size:.85rem;background:rgba(255,255,255,.15);color:#fff">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($usuarioNome ?? '', 0, 1))) ?>
              </span>
            <?php endif; ?>
            <span class="d-none d-lg-inline text-white text-truncate" style="max-width:120px"><?= htmlspecialchars($usuarioNome ?? '') ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:180px">
            <li class="px-3 py-2 border-bottom">
              <small class="text-muted d-block" style="font-size:.7rem">Logado como</small>
              <strong class="d-block text-truncate" style="font-size:.85rem"><?= htmlspecialchars($usuarioNome ?? '') ?></strong>
            </li>
            <?php if ($usuarioTipo !== 'admin'): ?>
            <li><a class="dropdown-item py-2" href="perfil.php">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-2 text-primary"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.029 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
              Perfil
            </a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <?php endif; ?>
            <li><a class="dropdown-item py-2 text-danger" href="<?= $sairLink ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-2"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
              Sair
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4 mt-5">

  <!-- Hero -->
  <div class="notif-hero mb-4">
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
      <div>
        <h1>
          🔔 Notificações
          <?php if ($naoLidas > 0): ?>
            <span class="badge ms-2" style="background:#f97316;font-size:.75rem;vertical-align:middle"><?= $naoLidas ?> nova<?= $naoLidas !== 1 ? 's' : '' ?></span>
          <?php endif; ?>
        </h1>
        <p>Acompanhe atualizações sobre chamados, avaliações e atividades da plataforma.</p>
      </div>
      <?php if ($naoLidas > 0): ?>
        <form method="post">
          <button name="marcar_todas" class="btn btn-sm fw-semibold"
                  style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3)">
            ✓ Marcar todas como lidas
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$notificacoes): ?>
    <div class="text-center py-5" style="color:#9ca3af">
      <div style="font-size:3.5rem;margin-bottom:.75rem">🔕</div>
      <h5 class="fw-bold" style="color:#0d1b3d">Nenhuma notificação</h5>
      <p style="font-size:.9rem">Quando houver novidades, elas aparecerão aqui.</p>
    </div>
  <?php else: ?>

    <!-- Separador não lidas -->
    <?php
      $temNaoLidas = $naoLidas > 0;
      $mostrouSeparador = false;
    ?>

    <?php foreach ($notificacoes as $n):
      $lida = $n->lida;

      if ($temNaoLidas && !$mostrouSeparador && $lida):
        $mostrouSeparador = true;
    ?>
      <div class="d-flex align-items-center gap-2 mb-3 mt-4">
        <div style="flex:1;height:1px;background:#e8ecf3"></div>
        <span style="font-size:.75rem;color:#9ca3af;white-space:nowrap">Anteriores</span>
        <div style="flex:1;height:1px;background:#e8ecf3"></div>
      </div>
    <?php endif; ?>

    <div class="notif-item <?= $lida ? 'lida' : 'nao-lida' ?>">
      <div class="notif-icone <?= $lida ? 'lida' : 'nao-lida' ?>">
        <?php
          $msg = mb_strtolower($n->mensagem);
          if (str_contains($msg, 'avalia'))          echo '⭐';
          elseif (str_contains($msg, 'pagamento') || str_contains($msg, 'pago')) echo '💰';
          elseif (str_contains($msg, 'chamado') || str_contains($msg, 'servi')) echo '🔧';
          elseif (str_contains($msg, 'destaque'))    echo '⭐';
          elseif (str_contains($msg, 'aprovad'))     echo '✅';
          elseif (str_contains($msg, 'recusad'))     echo '❌';
          elseif (str_contains($msg, 'bloqueado'))   echo '🚫';
          else                                        echo '🔔';
        ?>
      </div>
      <div class="flex-grow-1 min-w-0">
        <p class="notif-msg <?= !$lida ? 'fw-semibold' : '' ?>"><?= htmlspecialchars($n->mensagem) ?></p>
        <div class="notif-meta">
          <span>🕐 <?= date('d/m/Y \à\s H:i', strtotime($n->criadoEm)) ?></span>
          <?php if ($n->chamadoId): ?>
            <a href="chat.php?chamado=<?= (int)$n->chamadoId ?>"
               class="text-decoration-none fw-semibold" style="color:#c95e00;font-size:.78rem">
              💬 Ver chamado #<?= (int)$n->chamadoId ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="notif-actions">
        <?php if (!$lida): ?>
          <a href="notificacoes.php?lida=<?= $n->id ?>"
             class="btn btn-sm fw-semibold text-nowrap"
             style="background:#fff3e0;color:#c95e00;border:1.5px solid #fed7aa;font-size:.75rem">
            ✓ Marcar lida
          </a>
        <?php else: ?>
          <span class="badge" style="background:#f3f4f6;color:#9ca3af;font-size:.7rem">Lida</span>
        <?php endif; ?>
      </div>
    </div>

    <?php endforeach; ?>

    <p class="text-center text-muted mt-3" style="font-size:.78rem">
      Exibindo <?= count($notificacoes) ?> notificaç<?= count($notificacoes) !== 1 ? 'ões' : 'ão' ?> mais recentes.
    </p>

  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
