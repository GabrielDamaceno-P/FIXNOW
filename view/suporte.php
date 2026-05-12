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
    .thread-box { max-height: 340px; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; padding: .75rem; background: var(--bs-light); border-radius: .5rem; }
    .bubble { max-width: 78%; padding: .55rem .85rem; border-radius: 1rem; font-size: .875rem; line-height: 1.45; }
    .bubble-user  { align-self: flex-end; background: #0d1b3d; color: #fff; border-bottom-right-radius: .25rem; }
    .bubble-admin { align-self: flex-start; background: #fff; color: #1f2937; border: 1px solid #dee2e6; border-bottom-left-radius: .25rem; }
    .bubble-system { align-self: center; background: #f0f0f0; color: #555; font-size: .78rem; border-radius: 1rem; padding: .3rem .75rem; font-style: italic; }
    .bubble-meta  { font-size: .72rem; opacity: .65; margin-top: .2rem; }
    [data-theme="dark"] .bubble-admin { background: #2a2a2a; border-color: #444; color: #eee; }
    [data-theme="dark"] .thread-box   { background: #1a1a2e; }
    [data-theme="dark"] .bubble-system { background: #2a2a2a; color: #aaa; }
    .resolucao-box { background: #d1fae5; border: 1px solid #6ee7b7; border-radius: .5rem; padding: .85rem 1rem; }
    [data-theme="dark"] .resolucao-box { background: #1a3a2a; border-color: #2d6a4f; color: #a7f3d0; }
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
          <form method="post" id="form-ticket">
            <input type="hidden" name="acao" value="abrir">
            <input type="hidden" name="chamado_id" id="hidden-chamado-id" value="">

            <div class="mb-3">
              <label class="form-label">Categoria</label>
              <select name="categoria" id="sel-categoria" class="form-select">
                <?php foreach (SuporteDAO::CATEGORIAS as $c): ?>
                  <option value="<?= $c ?>"><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Seção: Pagamento -->
            <div id="sec-pagamento" class="categoria-sec d-none mb-3">
              <?php if ($usuarioTipo === 'cliente'): ?>
                <label class="form-label">Selecione o pagamento para solicitar estorno</label>
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
                  Relate abaixo qual chamado está com problema de pagamento — valor incorreto, pagamento não registrado, etc.
                </div>
                <label class="form-label">Chamado relacionado</label>
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
                <label class="form-label">Selecione o chamado com problema</label>
                <div class="form-text mb-2">Ex: prestador não compareceu, serviço mal executado, reagendamento sem aviso.</div>
              <?php else: ?>
                <label class="form-label">Selecione o chamado com problema</label>
                <div class="form-text mb-2">Ex: cliente não estava presente, informações incorretas no chamado, dificuldade de acesso.</div>
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
                  Descreva o problema com sua conta — ex: alteração de e-mail ou senha, dados incorretos, solicitação de exclusão de conta.
                </div>
              <?php else: ?>
                <div class="alert alert-info small mb-0">
                  <i class="bi bi-person-badge me-1"></i>
                  Descreva o problema com sua conta — ex: alteração de dados cadastrais, atualização de documentos, verificação de perfil, solicitação de exclusão.
                </div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label">Prioridade</label>
              <select name="prioridade" class="form-select form-select-sm">
                <?php foreach (SuporteDAO::PRIORIDADES as $p): ?>
                  <option value="<?= $p ?>" <?= $p === 'Normal' ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Assunto <span class="text-danger">*</span></label>
              <input type="text" name="assunto" id="input-assunto" class="form-control" maxlength="200" required
                placeholder="Descreva brevemente o problema...">
            </div>

            <div class="mb-3">
              <label class="form-label">Mensagem <span class="text-danger">*</span></label>
              <textarea name="mensagem" id="input-mensagem" class="form-control" rows="4" required
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
                $status   = $tk->status;
                $prio     = $tk->prioridade;
                $categ    = $tk->categoria;
                $badgeSt  = $status === 'Aberto' ? 'danger' : ($status === 'Em Andamento' ? 'warning text-dark' : 'success');
                $badgePr  = prioridadeBadge($prio);
                $fechado  = $status === 'Fechado';
                $msgs     = $tk->mensagens;
                $resolucao = $tk->resposta;
              ?>
                <div class="accordion-item mb-2 border">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#ticket<?= $tk->id ?>">
                      <div class="d-flex align-items-center gap-2 flex-wrap w-100 me-3">
                        <span class="badge bg-<?= $badgeSt ?>"><?= htmlspecialchars($status) ?></span>
                        <span class="badge bg-<?= $badgePr ?>"><?= htmlspecialchars($prio) ?></span>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($categ) ?></span>
                        <?php if ($tk->chamadoId): ?>
                          <span class="badge bg-secondary"><i class="bi bi-wrench me-1"></i>Chamado #<?= $tk->chamadoId ?></span>
                        <?php endif; ?>
                        <span class="fw-semibold small">#<?= $tk->id ?> — <?= htmlspecialchars(mb_strimwidth($tk->assunto, 0, 50, '...')) ?></span>
                        <span class="ms-auto small text-muted"><?= date('d/m/Y', strtotime($tk->criadoEm)) ?></span>
                      </div>
                    </button>
                  </h2>
                  <div id="ticket<?= $tk->id ?>" class="accordion-collapse collapse">
                    <div class="accordion-body pt-2">

                      <!-- Resolução destacada (só se fechado e tem resolução) -->
                      <?php if ($fechado && $resolucao): ?>
                        <div class="resolucao-box mb-3">
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong class="small">Resolvido pela equipe de suporte</strong>
                          </div>
                          <p class="mb-0 small"><?= nl2br(htmlspecialchars($resolucao)) ?></p>
                        </div>
                      <?php endif; ?>

                      <!-- Thread de mensagens -->
                      <div class="thread-box mb-3" id="thread-<?= $tk->id ?>">
                        <?php if (!$msgs): ?>
                          <p class="text-muted small mb-0">Sem mensagens.</p>
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

                      <!-- Responder ou Reabrir -->
                      <?php if (!$fechado): ?>
                        <form method="post" class="d-flex gap-2">
                          <input type="hidden" name="acao" value="mensagem">
                          <input type="hidden" name="suporte_id" value="<?= $tk->id ?>">
                          <textarea name="mensagem" class="form-control form-control-sm" rows="2"
                            placeholder="Adicionar mensagem..." required style="resize:none;"></textarea>
                          <button class="btn btn-warning btn-sm fw-semibold" style="white-space:nowrap;">Enviar</button>
                        </form>
                      <?php else: ?>
                        <div class="d-flex align-items-center justify-content-between">
                          <p class="text-muted small mb-0">
                            <i class="bi bi-lock me-1"></i>Este ticket está fechado.
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

// Auto-refresh a cada 30s — só se nenhum campo estiver preenchido
let _usuarioDigitando = false;
document.querySelectorAll('input, textarea').forEach(el => {
  el.addEventListener('input', () => { _usuarioDigitando = true; });
  el.addEventListener('blur',  () => {
    if (!el.value.trim()) _usuarioDigitando = false;
  });
});
setInterval(function () {
  if (document.hidden || _usuarioDigitando) return;
  location.reload();
}, 30000);

// Formulário dinâmico por categoria
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
  selChamado.addEventListener('change', function () {
    hiddenChamado.value = this.value;
  });
}

const selChamadoPag = document.getElementById('sel-chamado-pag');
if (selChamadoPag) {
  selChamadoPag.addEventListener('change', function () {
    hiddenChamado.value = this.value;
  });
}
</script>
</body>
</html>
