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

$naoLidas   = 0;
$pagamentos = $ctrl->pagamentos;
$chamados   = $ctrl->chamados;

$_navDepth   = 1;
$paginaAtiva = 'suporte';

function prioridadeBadge(string $p): string {
    return match($p) {
        'Urgente' => 'danger',
        'Alta'    => 'warning text-dark',
        'Baixa'   => 'secondary',
        default   => 'info text-dark',
    };
}
function statusBadge(string $s): string {
    return match($s) {
        'Aberto'      => 'danger',
        'Em Andamento'=> 'warning text-dark',
        'Fechado'     => 'success',
        default       => 'secondary',
    };
}
function statusIcon(string $s): string {
    return match($s) {
        'Aberto'      => '🔴',
        'Em Andamento'=> '🟡',
        'Fechado'     => '🟢',
        default       => '⚪',
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    /* Hero */
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}

    /* Form card */
    .form-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.5rem;position:sticky;top:80px}
    @media(max-width:991.98px){.form-card{position:relative;top:0}}
    .form-card-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:1.1rem;padding-bottom:.7rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}

    /* Tickets accordion */
    .ticket-item{border-radius:12px!important;overflow:hidden;border:1.5px solid #e8ecf3!important;margin-bottom:.75rem!important}
    .ticket-item .accordion-button{border-radius:0!important;padding:.85rem 1rem}
    .ticket-item .accordion-button:not(.collapsed){background:#f8faff;box-shadow:none}
    .ticket-item .accordion-button::after{flex-shrink:0}
    .ticket-assunto{font-weight:700;font-size:.88rem;color:#0d1b3d}
    .ticket-data{font-size:.74rem;color:#9ca3af;white-space:nowrap}

    /* Thread */
    .thread-box{max-height:340px;overflow-y:auto;display:flex;flex-direction:column;gap:.5rem;padding:.75rem;background:#f8faff;border-radius:10px;border:1px solid #e8ecf3}
    .bubble{max-width:78%;padding:.55rem .85rem;border-radius:1rem;font-size:.875rem;line-height:1.45}
    .bubble-user{align-self:flex-end;background:#0d1b3d;color:#fff;border-bottom-right-radius:.25rem}
    .bubble-admin{align-self:flex-start;background:#fff;color:#1f2937;border:1px solid #dee2e6;border-bottom-left-radius:.25rem}
    .bubble-system{align-self:center;background:#f0f0f0;color:#555;font-size:.78rem;border-radius:1rem;padding:.3rem .75rem;font-style:italic}
    .bubble-meta{font-size:.72rem;opacity:.65;margin-top:.2rem}
    .resolucao-box{background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:.85rem 1rem}

    /* Empty state */
    .empty-tickets{text-align:center;padding:3rem 1rem}
    .empty-tickets .ico{font-size:3rem;margin-bottom:.6rem}

    /* Dark mode */
    [data-theme="dark"] .form-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .form-card-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .ticket-item{border-color:#2e3650!important}
    [data-theme="dark"] .ticket-item .accordion-button:not(.collapsed){background:#252d42}
    [data-theme="dark"] .ticket-item .accordion-button{background:#1e2538;color:#e4e8f4}
    [data-theme="dark"] .ticket-assunto{color:#e4e8f4}
    [data-theme="dark"] .thread-box{background:#1a2035;border-color:#2e3650}
    [data-theme="dark"] .bubble-admin{background:#252d42;border-color:#2e3650;color:#e4e8f4}
    [data-theme="dark"] .bubble-system{background:#252d42;color:#9ca3af}
    [data-theme="dark"] .resolucao-box{background:#1a3a2a;border-color:#2d6a4f;color:#a7f3d0}
    [data-theme="dark"] .accordion-body{background:#1e2538}
  </style>
</head>
<body>

<?php if ($usuarioTipo === 'prestador'): ?>
  <?php require_once __DIR__ . '/../includes/prestador_nav.php'; ?>
<?php elseif ($usuarioTipo === 'admin'): ?>
  <?php
    require_once __DIR__ . '/../model/dao/Conexao.php';
    $pdo = Conexao::getConexao();
    require_once __DIR__ . '/admin/_navbar.php';
  ?>
<?php else: ?>
  <?php require_once __DIR__ . '/../includes/cliente_nav.php'; ?>
<?php endif; ?>

<main class="container py-4 mt-5">

  <!-- Hero -->
  <div class="page-hero">
    <div style="position:relative;z-index:1">
      <h1>🎧 Suporte</h1>
      <p>Abra um ticket e nossa equipe responderá em breve.</p>
    </div>
    <?php if ($tickets): ?>
    <div style="position:absolute;right:2rem;top:50%;transform:translateY(-50%);z-index:1;text-align:center">
      <div style="font-size:1.8rem;font-weight:800;color:#ffc107;line-height:1"><?php echo count($tickets); ?></div>
      <div style="font-size:.75rem;color:rgba(255,255,255,.7)">ticket<?php echo count($tickets) !== 1 ? 's' : ''; ?></div>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($mensagem): ?><div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($mensagem); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($erro); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

  <div class="row g-4">
    <!-- Formulário novo ticket -->
    <div class="col-lg-4">
      <div class="form-card">
        <div class="form-card-titulo"><span>➕</span> Novo ticket</div>
        <form method="post" id="form-ticket">
          <input type="hidden" name="acao" value="abrir">
          <input type="hidden" name="chamado_id" id="hidden-chamado-id" value="">

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Categoria</label>
            <select name="categoria" id="sel-categoria" class="form-select">
              <?php foreach (SuporteDAO::CATEGORIAS as $c): ?>
                <option value="<?= $c ?>"><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Seção: Pagamento -->
          <div id="sec-pagamento" class="categoria-sec d-none mb-3">
            <?php if ($usuarioTipo === 'cliente'): ?>
              <label class="form-label fw-semibold" style="font-size:.88rem;">Pagamento para estorno</label>
              <?php if ($pagamentos): ?>
                <select id="sel-pagamento" class="form-select form-select-sm">
                  <option value="">— Selecione —</option>
                  <?php foreach ($pagamentos as $p): ?>
                    <option value="<?= (int)$p['chamado_id'] ?>"
                            data-valor="<?= number_format((float)$p['valor'], 2, ',', '.') ?>"
                            data-status="<?= htmlspecialchars($p['status']) ?>">
                      Chamado #<?= (int)$p['chamado_id'] ?> — <?= htmlspecialchars($p['chamado_categoria']) ?>
                      — R$ <?= number_format((float)$p['valor'], 2, ',', '.') ?>
                      (<?= htmlspecialchars($p['status']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="info-pagamento" class="alert alert-info small mt-2 d-none"></div>
              <?php else: ?>
                <div class="alert alert-secondary small">Você não possui pagamentos registrados.</div>
              <?php endif; ?>
            <?php else: ?>
              <div class="alert alert-info small mb-2">
                <i class="bi bi-cash me-1"></i>
                Relate qual chamado está com problema de pagamento.
              </div>
              <label class="form-label fw-semibold" style="font-size:.88rem;">Chamado relacionado</label>
              <?php if ($chamados): ?>
                <select id="sel-chamado-pag" class="form-select form-select-sm">
                  <option value="">— Selecione (opcional) —</option>
                  <?php foreach ($chamados as $c): ?>
                    <option value="<?= (int)$c['id'] ?>">
                      #<?= (int)$c['id'] ?> — <?= htmlspecialchars($c['categoria']) ?>
                      (<?= htmlspecialchars($c['status']) ?>)
                      <?php if (!empty($c['cliente_nome'])): ?>— <?= htmlspecialchars($c['cliente_nome']) ?><?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <div class="alert alert-secondary small">Você não possui chamados registrados.</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <!-- Seção: Técnico -->
          <div id="sec-tecnico" class="categoria-sec d-none mb-3">
            <?php if ($usuarioTipo === 'cliente'): ?>
              <label class="form-label fw-semibold" style="font-size:.88rem;">Chamado com problema</label>
              <div class="form-text mb-2">Ex: prestador não compareceu, serviço mal executado.</div>
            <?php else: ?>
              <label class="form-label fw-semibold" style="font-size:.88rem;">Chamado com problema</label>
              <div class="form-text mb-2">Ex: cliente não estava presente, informações incorretas.</div>
            <?php endif; ?>
            <?php if ($chamados): ?>
              <select id="sel-chamado" class="form-select form-select-sm">
                <option value="">— Selecione —</option>
                <?php foreach ($chamados as $c): ?>
                  <option value="<?= (int)$c['id'] ?>">
                    #<?= (int)$c['id'] ?> — <?= htmlspecialchars($c['categoria']) ?>
                    (<?= htmlspecialchars($c['status']) ?>)
                    <?php if (!empty($c['tecnico_nome'])): ?>— <?= htmlspecialchars($c['tecnico_nome']) ?><?php elseif (!empty($c['cliente_nome'])): ?>— <?= htmlspecialchars($c['cliente_nome']) ?><?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <div class="alert alert-secondary small">Você não possui chamados registrados.</div>
            <?php endif; ?>
          </div>

          <!-- Seção: Conta -->
          <div id="sec-conta" class="categoria-sec d-none mb-3">
            <?php if ($usuarioTipo === 'cliente'): ?>
              <div class="alert alert-info small mb-0">
                <i class="bi bi-person-circle me-1"></i>
                Descreva o problema com sua conta — alteração de e-mail, senha, exclusão, etc.
              </div>
            <?php else: ?>
              <div class="alert alert-info small mb-0">
                <i class="bi bi-person-badge me-1"></i>
                Descreva o problema com sua conta — dados cadastrais, documentos, verificação, exclusão.
              </div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Prioridade</label>
            <select name="prioridade" class="form-select form-select-sm">
              <?php foreach (SuporteDAO::PRIORIDADES as $p): ?>
                <option value="<?= $p ?>" <?= $p === 'Normal' ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Assunto <span class="text-danger">*</span></label>
            <input type="text" name="assunto" id="input-assunto" class="form-control" maxlength="200" required
              placeholder="Descreva brevemente o problema...">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Mensagem <span class="text-danger">*</span></label>
            <textarea name="mensagem" id="input-mensagem" class="form-control" rows="4" required
              placeholder="Detalhe sua dúvida ou problema..."></textarea>
          </div>

          <button type="submit" class="btn btn-warning fw-bold w-100">Enviar Suporte</button>
        </form>
      </div>
    </div>

    <!-- Lista de tickets -->
    <div class="col-lg-8">

      <?php if (!$tickets): ?>
        <div class="empty-tickets">
          <div class="ico">🎧</div>
          <h5 class="fw-bold" style="color:#0d1b3d;">Nenhum ticket aberto</h5>
          <p class="text-muted" style="font-size:.9rem;">Use o formulário ao lado para entrar em contato com nossa equipe.</p>
        </div>
      <?php else: ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted" style="font-size:.85rem;">
            <strong><?php echo count($tickets); ?></strong> ticket<?php echo count($tickets) !== 1 ? 's' : ''; ?>
          </span>
          <div class="d-flex gap-2 flex-wrap">
            <?php
              $abertos = array_filter($tickets, fn($t) => $t->status === 'Aberto');
              $emAndamento = array_filter($tickets, fn($t) => $t->status === 'Em Andamento');
              $fechados = array_filter($tickets, fn($t) => $t->status === 'Fechado');
            ?>
            <?php if ($abertos): ?><span class="badge bg-danger"><?php echo count($abertos); ?> aberto<?php echo count($abertos) !== 1 ? 's' : ''; ?></span><?php endif; ?>
            <?php if ($emAndamento): ?><span class="badge bg-warning text-dark"><?php echo count($emAndamento); ?> em andamento</span><?php endif; ?>
            <?php if ($fechados): ?><span class="badge bg-success"><?php echo count($fechados); ?> fechado<?php echo count($fechados) !== 1 ? 's' : ''; ?></span><?php endif; ?>
          </div>
        </div>

        <div class="accordion" id="accordionTickets">
          <?php foreach ($tickets as $tk):
            $status   = $tk->status;
            $prio     = $tk->prioridade;
            $categ    = $tk->categoria;
            $badgeSt  = statusBadge($status);
            $badgePr  = prioridadeBadge($prio);
            $fechado  = $status === 'Fechado';
            $msgs     = $tk->mensagens;
            $resolucao = $tk->resposta;
          ?>
            <div class="accordion-item ticket-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#ticket<?= $tk->id ?>">
                  <div class="d-flex align-items-start gap-2 flex-wrap w-100 me-3">
                    <div class="d-flex gap-1 flex-wrap align-items-center">
                      <span class="badge bg-<?= $badgeSt ?>"><?= statusIcon($status) ?> <?= htmlspecialchars($status) ?></span>
                      <span class="badge bg-<?= $badgePr ?>"><?= htmlspecialchars($prio) ?></span>
                      <span class="badge bg-light text-dark border" style="font-size:.7rem;"><?= htmlspecialchars($categ) ?></span>
                      <?php if ($tk->chamadoId): ?>
                        <span class="badge bg-secondary" style="font-size:.7rem;">🔧 #<?= $tk->chamadoId ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                      <div class="ticket-assunto">#<?= $tk->id ?> — <?= htmlspecialchars(mb_strimwidth($tk->assunto, 0, 55, '...')); ?></div>
                      <div class="ticket-data">📅 <?= date('d/m/Y', strtotime($tk->criadoEm)) ?></div>
                    </div>
                  </div>
                </button>
              </h2>
              <div id="ticket<?= $tk->id ?>" class="accordion-collapse collapse">
                <div class="accordion-body pt-2">

                  <!-- Resolução (fechado) -->
                  <?php if ($fechado && $resolucao): ?>
                    <div class="resolucao-box mb-3">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong class="small">Resolvido pela equipe de suporte</strong>
                      </div>
                      <p class="mb-0 small"><?= nl2br(htmlspecialchars($resolucao)) ?></p>
                    </div>
                  <?php endif; ?>

                  <!-- Thread -->
                  <div class="thread-box mb-3" id="thread-<?= $tk->id ?>">
                    <?php if (!$msgs): ?>
                      <p class="text-muted small mb-0 text-center">Sem mensagens ainda.</p>
                    <?php else: ?>
                      <?php foreach ($msgs as $m):
                        $isSystem = str_contains($m->mensagem, 'reaberto pelo usuário');
                        $isUser   = !$isSystem && $m->autorTipo !== 'admin';
                      ?>
                        <div>
                          <?php if ($isSystem): ?>
                            <div class="bubble bubble-system"><?= htmlspecialchars($m->mensagem) ?></div>
                          <?php else: ?>
                            <div class="bubble <?= $isUser ? 'bubble-user' : 'bubble-admin' ?>">
                              <?= nl2br(htmlspecialchars($m->mensagem)) ?>
                            </div>
                            <div class="bubble-meta <?= $isUser ? 'text-end' : '' ?>">
                              <?= $isUser ? 'Você' : 'Suporte Fix Now' ?> · <?= date('d/m H:i', strtotime($m->criadoEm)) ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>

                  <!-- Responder / Reabrir -->
                  <?php if (!$fechado): ?>
                    <form method="post" class="d-flex gap-2">
                      <input type="hidden" name="acao" value="mensagem">
                      <input type="hidden" name="suporte_id" value="<?= $tk->id ?>">
                      <textarea name="mensagem" class="form-control form-control-sm" rows="2"
                        placeholder="Adicionar mensagem..." required style="resize:none;border-radius:10px;"></textarea>
                      <button class="btn btn-warning btn-sm fw-bold" style="white-space:nowrap;align-self:flex-end;">Enviar</button>
                    </form>
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-between">
                      <p class="text-muted small mb-0">
                        <i class="bi bi-lock me-1"></i>Ticket fechado.
                      </p>
                      <form method="post">
                        <input type="hidden" name="acao" value="reabrir">
                        <input type="hidden" name="suporte_id" value="<?= $tk->id ?>">
                        <button class="btn btn-sm btn-outline-secondary"
                          onclick="return confirm('Reabrir este ticket? Nossa equipe irá analisar novamente.')">
                          <i class="bi bi-arrow-clockwise me-1"></i>Reabrir
                        </button>
                      </form>
                    </div>
                  <?php endif; ?>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
document.querySelectorAll('.accordion-collapse').forEach(el => {
  el.addEventListener('shown.bs.collapse', () => {
    const box = el.querySelector('.thread-box');
    if (box) box.scrollTop = box.scrollHeight;
  });
});

let _usuarioDigitando = false;
document.querySelectorAll('input, textarea').forEach(el => {
  el.addEventListener('input', () => { _usuarioDigitando = true; });
  el.addEventListener('blur',  () => { if (!el.value.trim()) _usuarioDigitando = false; });
});
setInterval(function () {
  if (document.hidden || _usuarioDigitando) return;
  location.reload();
}, 30000);

const selCategoria  = document.getElementById('sel-categoria');
const secs          = document.querySelectorAll('.categoria-sec');
const hiddenChamado = document.getElementById('hidden-chamado-id');
const selPagamento  = document.getElementById('sel-pagamento');
const selChamado    = document.getElementById('sel-chamado');
const infoPagamento = document.getElementById('info-pagamento');
const inputAssunto  = document.getElementById('input-assunto');

const assuntosPadrao = {
  'Pagamento': 'Problema com pagamento',
  'Técnico':   'Problema com chamado',
  'Conta':     'Problema com minha conta',
  'Outro':     ''
};

function atualizarCategoria() {
  const cat = selCategoria.value;
  secs.forEach(s => s.classList.add('d-none'));
  hiddenChamado.value = '';

  const sec = document.getElementById('sec-' + cat.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''));
  if (sec) sec.classList.remove('d-none');

  if (assuntosPadrao[cat] && inputAssunto.value === '') {
    inputAssunto.value = assuntosPadrao[cat];
  }
}

if (selCategoria) {
  selCategoria.addEventListener('change', atualizarCategoria);
  atualizarCategoria();
}

if (selPagamento) {
  selPagamento.addEventListener('change', function () {
    hiddenChamado.value = this.value;
    if (this.value && infoPagamento) {
      const opt = this.options[this.selectedIndex];
      infoPagamento.textContent = 'Pagamento selecionado: R$ ' + opt.dataset.valor + ' — Status: ' + opt.dataset.status;
      infoPagamento.classList.remove('d-none');
    } else if (infoPagamento) {
      infoPagamento.classList.add('d-none');
    }
  });
}

if (selChamado) {
  selChamado.addEventListener('change', function () { hiddenChamado.value = this.value; });
}

const selChamadoPag = document.getElementById('sel-chamado-pag');
if (selChamadoPag) {
  selChamadoPag.addEventListener('change', function () { hiddenChamado.value = this.value; });
}
</script>
</body>
</html>
