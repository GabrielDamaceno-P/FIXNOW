<?php
session_start();
require_once __DIR__ . '/../controller/SuporteControl.php';

$ctrl = new SuporteControl();
$ctrl->processar();

$tickets     = $ctrl->tickets;
$mensagem    = $ctrl->mensagem;
$erro        = $ctrl->erro;
$usuarioTipo = $ctrl->usuarioTipo;
$usuarioNome = $ctrl->usuarioNome;
$usuarioFoto = $ctrl->usuarioFoto;
$usuarioId   = $ctrl->usuarioId;

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

function prioridadeBadge(string $p): string {
    return match($p) {
        'Urgente' => 'danger',
        'Alta'    => 'warning text-dark',
        'Baixa'   => 'secondary',
        default   => 'info text-dark',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Suporte - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .thread-box { max-height: 340px; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; padding: .75rem; background: var(--bs-light); border-radius: .5rem; }
    .bubble { max-width: 78%; padding: .55rem .85rem; border-radius: 1rem; font-size: .875rem; line-height: 1.45; }
    .bubble-user  { align-self: flex-end; background: #0d1b3d; color: #fff; border-bottom-right-radius: .25rem; }
    .bubble-admin { align-self: flex-start; background: #fff; border: 1px solid #dee2e6; border-bottom-left-radius: .25rem; }
    .bubble-meta  { font-size: .72rem; opacity: .65; margin-top: .2rem; }
    .dark-mode .bubble-admin { background: #2a2a2a; border-color: #444; }
    .dark-mode .thread-box   { background: #1a1a2e; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#suporteMenu" aria-controls="suporteMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="suporteMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= $dashLink ?>"><i class="bi bi-house me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="suporte.php"><i class="bi bi-headset me-1"></i>Suporte</a></li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($usuarioFoto): ?>
              <img src="../<?= htmlspecialchars($usuarioFoto) ?>" alt="" width="32" height="32" class="rounded-circle border border-2 border-white border-opacity-50" style="object-fit:cover">
            <?php else: ?>
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:.85rem"><?= htmlspecialchars(mb_strtoupper(mb_substr($usuarioNome ?? '', 0, 1))) ?></span>
            <?php endif; ?>
            <span class="d-none d-lg-inline text-truncate" style="max-width:120px"><?= htmlspecialchars($usuarioNome ?? '') ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:180px">
            <?php if ($usuarioTipo !== 'admin'): ?>
            <li><a class="dropdown-item py-2" href="perfil.php"><i class="bi bi-person me-2 text-primary"></i>Perfil</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <?php endif; ?>
            <li><a class="dropdown-item py-2 text-danger" href="<?= $sairLink ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Suporte</h2>
  <p class="text-muted mb-4">Abra um ticket ou acompanhe suas solicitações em andamento.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- Formulário novo ticket -->
    <div class="col-lg-4">
      <div class="card shadow-sm border-0 sticky-top" style="top:80px;">
        <div class="card-body">
          <h5 class="mb-3">Novo ticket</h5>
          <form method="post">
            <input type="hidden" name="acao" value="abrir">
            <div class="mb-3">
              <label class="form-label">Assunto <span class="text-danger">*</span></label>
              <input type="text" name="assunto" class="form-control" maxlength="200" required
                placeholder="Descreva brevemente o problema...">
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label">Categoria</label>
                <select name="categoria" class="form-select form-select-sm">
                  <?php foreach (SuporteControl::CATEGORIAS as $c): ?>
                    <option value="<?= $c ?>"><?= $c ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select form-select-sm">
                  <?php foreach (SuporteControl::PRIORIDADES as $p): ?>
                    <option value="<?= $p ?>" <?= $p === 'Normal' ? 'selected' : '' ?>><?= $p ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Mensagem <span class="text-danger">*</span></label>
              <textarea name="mensagem" class="form-control" rows="5" required
                placeholder="Detalhe sua dúvida ou problema..."></textarea>
            </div>
            <button type="submit" class="btn btn-warning fw-semibold w-100">Abrir ticket</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Lista de tickets -->
    <div class="col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3">Meus tickets <span class="badge bg-secondary"><?= count($tickets) ?></span></h5>
          <?php if (!$tickets): ?>
            <p class="text-muted">Você ainda não abriu nenhum ticket de suporte.</p>
          <?php else: ?>
            <div class="accordion" id="accordionTickets">
              <?php foreach ($tickets as $tk):
                $status   = $tk['status'] ?? 'Aberto';
                $prio     = $tk['prioridade'] ?? 'Normal';
                $categ    = $tk['categoria']  ?? 'Outro';
                $badgeSt  = $status === 'Aberto' ? 'danger' : ($status === 'Em Andamento' ? 'warning text-dark' : 'success');
                $badgePr  = prioridadeBadge($prio);
                $fechado  = $status === 'Fechado';
                $msgs     = $tk['mensagens'] ?? [];
              ?>
                <div class="accordion-item mb-2 border">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#ticket<?= (int)$tk['id'] ?>">
                      <div class="d-flex align-items-center gap-2 flex-wrap w-100 me-3">
                        <span class="badge bg-<?= $badgeSt ?>"><?= htmlspecialchars($status) ?></span>
                        <span class="badge bg-<?= $badgePr ?>"><?= htmlspecialchars($prio) ?></span>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($categ) ?></span>
                        <span class="fw-semibold small">#<?= (int)$tk['id'] ?> — <?= htmlspecialchars(mb_strimwidth($tk['assunto'], 0, 50, '...')) ?></span>
                        <span class="ms-auto small text-muted"><?= date('d/m/Y', strtotime($tk['criado_em'])) ?></span>
                      </div>
                    </button>
                  </h2>
                  <div id="ticket<?= (int)$tk['id'] ?>" class="accordion-collapse collapse">
                    <div class="accordion-body pt-2">

                      <!-- Thread de mensagens -->
                      <div class="thread-box mb-3">
                        <?php if (!$msgs): ?>
                          <p class="text-muted small mb-0">Sem mensagens.</p>
                        <?php else: ?>
                          <?php foreach ($msgs as $m):
                            $isUser = $m['autor_tipo'] !== 'admin';
                          ?>
                            <div>
                              <div class="bubble <?= $isUser ? 'bubble-user' : 'bubble-admin' ?>">
                                <?= nl2br(htmlspecialchars($m['mensagem'])) ?>
                              </div>
                              <div class="bubble-meta <?= $isUser ? 'text-end' : '' ?>">
                                <?= $isUser ? 'Você' : 'Suporte Fix Now' ?> · <?= date('d/m H:i', strtotime($m['criado_em'])) ?>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>

                      <!-- Responder (só se não fechado) -->
                      <?php if (!$fechado): ?>
                        <form method="post" class="d-flex gap-2">
                          <input type="hidden" name="acao" value="mensagem">
                          <input type="hidden" name="suporte_id" value="<?= (int)$tk['id'] ?>">
                          <textarea name="mensagem" class="form-control form-control-sm" rows="2"
                            placeholder="Adicionar mensagem..." required style="resize:none;"></textarea>
                          <button class="btn btn-warning btn-sm fw-semibold" style="white-space:nowrap;">Enviar</button>
                        </form>
                      <?php else: ?>
                        <p class="text-muted small mb-0">Este ticket está fechado.</p>
                      <?php endif; ?>

                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
// Scroll thread para o fim ao abrir o accordion
document.querySelectorAll('.accordion-collapse').forEach(el => {
  el.addEventListener('shown.bs.collapse', () => {
    const box = el.querySelector('.thread-box');
    if (box) box.scrollTop = box.scrollHeight;
  });
});
</script>
</body>
</html>
