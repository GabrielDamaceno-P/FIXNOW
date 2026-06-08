<?php
session_start();
require_once __DIR__ . '/../controller/ChatControl.php';

$ctrl = new ChatControl();
$ctrl->processar();

$chamado          = $ctrl->chamado;
$mensagens        = $ctrl->mensagens;
$usuarioTipo      = $ctrl->usuarioTipo;
$usuarioId        = $ctrl->usuarioId;
$usuarioNome      = $ctrl->usuarioNome;
$usuarioFoto      = $ctrl->usuarioFoto;
$naoLidas         = $ctrl->naoLidas;
$chatAtivo        = $ctrl->chatAtivo;
$migracaoPendente = $ctrl->migracaoPendente;
$chamadoId        = $ctrl->chamadoId;

$dashLink = $usuarioTipo === 'prestador'
    ? 'prestador/dashboardPrestador.php'
    : 'dashboardCliente.php';
$sairLink = $usuarioTipo === 'prestador'
    ? '../logout.php?entidade=prestador'
    : '../logout.php';

$statusBadge = match($chamado->status ?? '') {
    'Pendente'     => 'warning text-dark',
    'Em Andamento' => 'primary',
    'Concluído'    => 'success',
    default        => 'secondary',
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat — Chamado #<?php echo $chamadoId; ?> - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .chat-wrap {
      display: flex;
      flex-direction: column;
      height: calc(100dvh - 200px);
      min-height: 320px;
    }
    @supports not (height: 100dvh) {
      .chat-wrap { height: calc(100vh - 200px); }
    }
    @media (max-width: 991.98px) {
      .chat-wrap { height: calc(100dvh - 280px); min-height: 280px; }
    }
    @media (max-width: 575.98px) {
      .chat-wrap { height: calc(100dvh - 320px); min-height: 240px; }
    }
    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 1rem;
      background: var(--bs-body-bg, #f8f9fa);
      border-radius: 12px;
      border: 1px solid var(--bs-border-color, #dee2e6);
    }
    .msg-bubble {
      max-width: 72%;
      padding: .65rem 1rem;
      border-radius: 18px;
      font-size: .93rem;
      line-height: 1.45;
      position: relative;
    }
    .msg-mine {
      background: #0d1b3d;
      color: #fff;
      border-bottom-right-radius: 4px;
      margin-left: auto;
    }
    .msg-other {
      background: #fff;
      color: #1f2937;
      border: 1px solid #e0e6f0;
      border-bottom-left-radius: 4px;
    }
    [data-theme="dark"] .msg-other {
      background: #1c2338;
      color: #dde4f0;
      border-color: #2c3a56;
    }
    .msg-meta {
      font-size: .72rem;
      opacity: .65;
      margin-top: .25rem;
      display: block;
    }
    .msg-mine .msg-meta { text-align: right; color: rgba(255,255,255,.7); }
    .msg-other .msg-meta { color: #667085; }
    .chat-input-wrap { padding: .75rem 0 0; }
    .avatar-sm {
      width: 34px; height: 34px;
      border-radius: 50%; object-fit: cover; flex-shrink: 0;
    }
    .avatar-sm-fallback {
      width: 34px; height: 34px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: .85rem; flex-shrink: 0;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <span class="fn-user-badge me-2">
      <?php if ($usuarioFoto): ?>
        <img src="../<?php echo htmlspecialchars($usuarioFoto); ?>" alt="Foto" width="44" height="44">
      <?php else: ?>
        <span class="fallback"><?php echo htmlspecialchars(mb_substr($usuarioNome, 0, 1)); ?></span>
      <?php endif; ?>
      <span><?php echo htmlspecialchars($usuarioNome); ?></span>
    </span>
    <a class="navbar-brand fw-bold" href="<?php echo $dashLink; ?>">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?php echo $dashLink; ?>">Dashboard</a></li>
        <li class="nav-item">
          <a class="nav-link" href="notificacoes.php">
            Notificações<?php if ($naoLidas > 0): ?><span class="badge bg-danger ms-1"><?php echo $naoLidas; ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $sairLink; ?>">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4 mt-5">
  <div class="mb-3">
    <a href="<?php echo $dashLink; ?>" class="btn btn-sm btn-outline-secondary">&larr; Voltar ao dashboard</a>
  </div>

  <div class="row g-4">
    <!-- Painel lateral do chamado -->
    <div class="col-12 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="fw-bold mb-3">Chamado #<?php echo $chamadoId; ?></h6>
          <?php if ($chamado): ?>
          <div class="mb-2">
            <span class="badge bg-<?php echo $statusBadge; ?>"><?php echo htmlspecialchars($chamado->status); ?></span>
          </div>
          <div class="small text-muted mb-1">Categoria</div>
          <div class="small fw-semibold mb-3"><?php echo htmlspecialchars($chamado->categoria); ?></div>
          <div class="small text-muted mb-1">Descrição</div>
          <div class="small mb-3"><?php echo htmlspecialchars(mb_strimwidth($chamado->descricao, 0, 120, '...')); ?></div>
          <hr>
          <?php if ($usuarioTipo === 'cliente'): ?>
            <div class="small text-muted mb-1">Prestador</div>
            <div class="small fw-semibold"><?php echo htmlspecialchars($chamado->tecnicoNome ?? 'A definir'); ?></div>
          <?php else: ?>
            <div class="small text-muted mb-1">Cliente</div>
            <div class="small fw-semibold"><?php echo htmlspecialchars($chamado->clienteNome ?? ''); ?></div>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Área de chat -->
    <div class="col-12 col-lg-9">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom d-flex align-items-center gap-2 py-3">
          <span class="fs-5">💬</span>
          <span class="fw-semibold">Conversa sobre o chamado #<?php echo $chamadoId; ?></span>
          <?php if ($chamado): ?>
            <span class="badge bg-<?php echo $statusBadge; ?> ms-auto"><?php echo htmlspecialchars($chamado->status); ?></span>
          <?php endif; ?>
        </div>
        <div class="card-body p-3">
          <?php if ($ctrl->erro): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3">
              <?php echo htmlspecialchars($ctrl->erro); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          <?php if ($migracaoPendente): ?>
            <div class="alert alert-warning">
              <strong>Migração pendente.</strong> Execute o arquivo <code>migrate_chat.sql</code> no phpMyAdmin para ativar o chat.
            </div>
          <?php endif; ?>

          <div class="chat-wrap">
            <div class="chat-messages" id="chat-messages">
              <?php if (!$mensagens): ?>
                <div class="text-center text-muted py-5">
                  <div class="fs-2 mb-2">💬</div>
                  <p class="mb-0">Nenhuma mensagem ainda.<br>Inicie a conversa abaixo.</p>
                </div>
              <?php endif; ?>
              <?php foreach ($mensagens as $m): ?>
                <?php $isMinha = $usuarioTipo === 'cliente' ? $m->clienteId !== null : $m->tecnicoId !== null; ?>
                <div class="d-flex align-items-end gap-2 mb-3 <?php echo $isMinha ? 'flex-row-reverse' : ''; ?>">
                  <?php if (!$isMinha && $chamado): ?>
                    <?php
                      $eMsgPrestador = $m->tecnicoId !== null;
                      $fotoOp = $eMsgPrestador ? ($chamado->tecnicoFoto ?? '') : ($chamado->clienteFoto ?? '');
                      $nomeOp = $eMsgPrestador ? ($chamado->tecnicoNome ?? 'Prestador') : ($chamado->clienteNome ?? 'Cliente');
                    ?>
                    <?php if ($fotoOp): ?>
                      <img src="../<?php echo htmlspecialchars($fotoOp); ?>" class="avatar-sm" alt="Foto">
                    <?php else: ?>
                      <div class="avatar-sm-fallback bg-primary text-white"><?php echo htmlspecialchars(mb_substr($nomeOp, 0, 1)); ?></div>
                    <?php endif; ?>
                  <?php endif; ?>
                  <div class="msg-bubble <?php echo $isMinha ? 'msg-mine' : 'msg-other'; ?>">
                    <?php if ($m->mensagem !== ''): ?>
                      <?php echo nl2br(htmlspecialchars($m->mensagem)); ?>
                    <?php endif; ?>
                    <?php if ($m->arquivoPath): ?>
                      <?php
                        $ext = strtolower(pathinfo($m->arquivoPath, PATHINFO_EXTENSION));
                        $isImg = in_array($ext, ['jpg','jpeg','png','webp','gif']);
                      ?>
                      <div class="mt-1">
                        <?php if ($isImg): ?>
                          <a href="../<?php echo htmlspecialchars($m->arquivoPath); ?>" target="_blank">
                            <img src="../<?php echo htmlspecialchars($m->arquivoPath); ?>"
                                 alt="Anexo" class="rounded" style="max-width:220px;max-height:180px;object-fit:cover;display:block;">
                          </a>
                        <?php else: ?>
                          <a href="../<?php echo htmlspecialchars($m->arquivoPath); ?>" target="_blank"
                             class="d-flex align-items-center gap-1 small <?php echo $isMinha ? 'text-white' : 'text-primary'; ?>">
                            📎 <?php echo htmlspecialchars($m->arquivoNome ?? 'Anexo'); ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                    <span class="msg-meta">
                      <?php echo date('d/m H:i', strtotime($m->criadoEm)); ?>
                      <?php if ($isMinha && $m->lida): ?> · Lida<?php endif; ?>
                    </span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if ($chatAtivo && !$migracaoPendente): ?>
            <div class="chat-input-wrap">
              <form method="post" enctype="multipart/form-data" id="chat-form">
                <div class="d-flex gap-2 align-items-end">
                  <textarea name="mensagem" class="form-control" rows="2" id="chat-textarea"
                    placeholder="Digite sua mensagem..."
                    maxlength="1000"
                    style="resize:none;border-radius:12px;"></textarea>
                  <div class="d-flex flex-column gap-1">
                    <label class="btn btn-outline-secondary px-2 py-1 mb-0" title="Anexar arquivo" style="cursor:pointer;">
                      📎<input type="file" name="arquivo" id="chat-file-input" class="d-none"
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.zip">
                    </label>
                    <button type="submit" class="btn btn-warning fw-semibold px-3">Enviar</button>
                  </div>
                </div>
                <div id="chat-file-preview" class="small text-muted mt-1 d-none">
                  📎 <span id="chat-file-name"></span>
                  <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1" id="chat-file-clear">✕</button>
                </div>
                <div class="small text-muted mt-1">Enter envia · Shift+Enter nova linha</div>
              </form>
            </div>
            <?php else: ?>
            <div class="alert alert-secondary mt-3 mb-0 text-center">
              Este chamado está <?php echo htmlspecialchars($chamado->status ?? ''); ?> — chat encerrado.
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var msgs = document.getElementById('chat-messages');
  if (msgs) { msgs.scrollTop = msgs.scrollHeight; }

  var ta = document.getElementById('chat-textarea');
  if (ta) {
    ta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        ta.closest('form').submit();
      }
    });
  }

  var fileInput = document.getElementById('chat-file-input');
  var filePreview = document.getElementById('chat-file-preview');
  var fileName = document.getElementById('chat-file-name');
  var fileClear = document.getElementById('chat-file-clear');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      if (this.files.length > 0) {
        fileName.textContent = this.files[0].name;
        filePreview.classList.remove('d-none');
      }
    });
    fileClear.addEventListener('click', function () {
      fileInput.value = '';
      filePreview.classList.add('d-none');
    });
  }
});

setInterval(function () {
  if (document.hidden) return;
  var textarea = document.querySelector('textarea[name="mensagem"]');
  if (textarea && textarea.value.trim() !== '') return;
  var fileInput = document.getElementById('chat-file-input');
  if (fileInput && fileInput.files.length > 0) return;
  var active = document.activeElement;
  if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;
  location.reload();
}, 8000);
</script>
</body>
</html>
