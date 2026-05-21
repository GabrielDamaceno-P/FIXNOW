<?php
session_start();
require_once __DIR__ . '/../controller/HistoricoControl.php';

$ctrl = new HistoricoControl();
$ctrl->processar();

$chamados          = $ctrl->chamados;
$mensagem          = $ctrl->mensagem;
$erro              = $ctrl->erro;
$naoLidas          = $ctrl->naoLidas;
$totalGasto        = $ctrl->totalGasto;
$prestadoresUnicos = $ctrl->prestadoresUnicos;
$concluidos        = count(array_filter($chamados, fn($c) => $c->status === 'Concluído'));

$paginaAtiva = 'historico';
$_navDepth   = 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Histórico - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="../assets/css/stars-avaliacao.css" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__ . '/../includes/cliente_nav.php'; ?>

<main class="container py-5 mt-5">

  <!-- Hero -->
  <section class="fn-hero p-4 p-lg-5 mb-4">
    <div class="row align-items-center g-3">
      <div class="col">
        <p class="text-white-50 small mb-0">Seus serviços encerrados</p>
        <h1 class="h3 mb-1 text-white">Histórico</h1>
        <p class="mb-0 text-white-50 small">Acompanhe todos os serviços concluídos e cancelados.</p>
      </div>
      <div class="col-lg-auto mt-2 mt-lg-0">
        <a href="cliente/solicitar.php" class="btn btn-light px-4">+ Nova solicitação</a>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <section class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-success"
               style="width:52px;height:52px;background:rgba(25,135,84,.1);font-size:1.4rem;">✅</div>
          <div>
            <p class="text-muted mb-0 small">Serviços concluídos</p>
            <h3 class="mb-0 fw-bold"><?= $concluidos ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-primary"
               style="width:52px;height:52px;background:rgba(13,110,253,.1);font-size:1.4rem;">💰</div>
          <div>
            <p class="text-muted mb-0 small">Total investido</p>
            <h3 class="mb-0 fw-bold">R$ <?= number_format($totalGasto, 2, ',', '.') ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-warning"
               style="width:52px;height:52px;background:rgba(255,193,7,.15);font-size:1.4rem;">🔧</div>
          <div>
            <p class="text-muted mb-0 small">Prestadores atendidos</p>
            <h3 class="mb-0 fw-bold"><?= $prestadoresUnicos ?></h3>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php if ($mensagem): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <!-- Lista -->
  <?php if (!$chamados): ?>
    <div class="card border-0 shadow-sm text-center py-5">
      <div class="card-body">
        <div style="font-size:2.5rem;margin-bottom:.5rem">📋</div>
        <h5 class="fw-bold mb-1">Nenhum histórico ainda</h5>
        <p class="text-muted small mb-3">Quando um serviço for concluído ele aparecerá aqui.</p>
        <a href="cliente/solicitar.php" class="btn btn-warning fw-semibold">+ Nova solicitação</a>
      </div>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3">
    <?php foreach ($chamados as $c):
      $concluido   = $c->status === 'Concluído';
      $jaAvaliado  = $c->avaliacaoNota !== null;
      $podeAvaliar = $concluido && !empty($c->tecnicoNome) && !$jaAvaliado;
      $valorPago   = ($c->pagStatus === 'Pago' && $c->pagValor > 0)
                      ? 'R$ ' . number_format((float)$c->pagValor, 2, ',', '.')
                      : null;
      $accentColor = $concluido ? '#15803d' : '#9ca3af';
      [$statusBg, $statusTxt] = $concluido
          ? ['#dcfce7', '#15803d']
          : ['#f3f4f6', '#374151'];
    ?>
      <div class="card border-0 shadow-sm" style="border-left:4px solid <?= $accentColor ?>;transition:box-shadow .15s">
        <div class="card-body p-3">

          <!-- Linha 1: ID + categoria + status + data -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span style="font-size:.78rem;color:#9ca3af;font-weight:600">#<?= $c->id ?></span>
              <span class="badge rounded-pill" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem">
                <?= htmlspecialchars($c->categoria) ?>
              </span>
              <span class="badge rounded-pill" style="background:<?= $statusBg ?>;color:<?= $statusTxt ?>;font-size:.75rem">
                <?= htmlspecialchars($c->status) ?>
              </span>
              <?php if ($valorPago): ?>
                <span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;font-size:.72rem">
                  💳 <?= $valorPago ?> · <?= htmlspecialchars($c->pagMetodo ?? 'Pago') ?>
                </span>
              <?php endif; ?>
            </div>
            <?php if ($c->criadoEm): ?>
              <span style="font-size:.78rem;color:#6b7280">🗓 <?= date('d/m/Y', strtotime($c->criadoEm)) ?></span>
            <?php endif; ?>
          </div>

          <!-- Linha 2: descrição + prestador -->
          <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-2">
            <div class="flex-grow-1" style="min-width:0">
              <p style="font-size:.9rem;font-weight:600;color:#0d1b3d;margin-bottom:.25rem">
                <?= htmlspecialchars(mb_strimwidth($c->descricao, 0, 80, '…')) ?>
              </p>

              <?php if ($c->tecnicoNome): ?>
                <div class="d-flex align-items-center gap-2">
                  <?php if (!empty($c->tecnicoFoto)): ?>
                    <img src="../<?= htmlspecialchars($c->tecnicoFoto) ?>" alt=""
                         class="rounded-circle flex-shrink-0" style="width:24px;height:24px;object-fit:cover">
                  <?php else: ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:24px;height:24px;background:#0d1b3d;color:#fff;font-size:.65rem;font-weight:700">
                      <?= mb_substr($c->tecnicoNome, 0, 1) ?>
                    </div>
                  <?php endif; ?>
                  <span style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($c->tecnicoNome) ?></span>
                </div>
              <?php else: ?>
                <span style="font-size:.8rem;color:#9ca3af">Sem prestador atribuído</span>
              <?php endif; ?>
            </div>

            <!-- Avaliação já dada -->
            <?php if ($jaAvaliado): ?>
              <div class="text-end flex-shrink-0">
                <div style="color:#f59e0b;font-size:1rem">
                  <?= str_repeat('★', (int)$c->avaliacaoNota) . str_repeat('☆', 5 - (int)$c->avaliacaoNota) ?>
                </div>
                <div style="font-size:.72rem;color:#9ca3af">avaliado</div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Ações -->
          <div style="border-top:1px solid #f0f3fa;margin-top:.5rem;padding-top:.5rem" class="d-flex flex-wrap gap-2">
            <?php if ($podeAvaliar): ?>
              <button type="button" class="btn btn-sm btn-outline-warning"
                data-bs-open-avaliacao
                data-chamado-id="<?= $c->id ?>"
                data-tecnico-nome="<?= htmlspecialchars($c->tecnicoNome ?? '') ?>">
                ⭐ Avaliar prestador
              </button>
            <?php endif; ?>
            <?php if ($concluido && $c->tecnicoId): ?>
              <a href="cliente/solicitar.php?prestador=<?= (int)$c->tecnicoId ?>"
                 class="btn btn-sm btn-outline-primary">
                🔁 Solicitar novamente
              </a>
            <?php endif; ?>
            <a href="chat.php?chamado=<?= $c->id ?>" class="btn btn-sm btn-outline-secondary">
              💬 Chat
            </a>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<!-- Modal Avaliação -->
<div class="modal fade" id="modalAvaliacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content overflow-hidden">
      <div style="background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);padding:1.4rem 1.5rem;position:relative;">
        <button type="button" class="btn-close btn-close-white position-absolute" style="top:.85rem;right:1rem;" data-bs-dismiss="modal"></button>
        <div class="d-flex align-items-center gap-2">
          <span style="font-size:1.6rem;">⭐</span>
          <div>
            <h5 class="mb-0 fw-bold text-white">Avaliar atendimento</h5>
            <p class="mb-0" style="font-size:.8rem;color:rgba(255,255,255,.7);" data-avaliacao-resumo></p>
          </div>
        </div>
      </div>
      <div class="modal-body p-4">
        <form method="post" id="form-avaliacao" class="js-guard-submit">
          <input type="hidden" name="avaliar_chamado_id" value="">
          <input type="hidden" name="nota_avaliacao" value="5">
          <div class="mb-4 text-center">
            <div class="mb-2" style="font-size:.85rem;font-weight:600;color:#6b7280;">Como você avalia o atendimento?</div>
            <div class="fn-stars-wrap d-flex flex-column align-items-center gap-2">
              <div class="fn-stars" data-fn-stars style="font-size:2.2rem;"></div>
              <span class="fn-star-legend badge px-3 py-2" data-fn-stars-legend
                    style="background:#0d1b3d;color:#ffc107;font-size:.85rem;font-weight:700;border-radius:50px;">Excelente</span>
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Comentário <span class="text-muted fw-normal">(opcional)</span></label>
            <textarea name="comentario_avaliacao" class="form-control" rows="3" maxlength="255"
              placeholder="Conte como foi o atendimento..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning fw-bold flex-grow-1" form="form-avaliacao">Enviar avaliação</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js"></script>
<script src="../assets/js/avaliacao-dashboard.js"></script>
<button id="fn-dark-toggle" title="Alternar modo escuro" aria-label="Alternar modo escuro">🌙</button>
</body>
</html>
