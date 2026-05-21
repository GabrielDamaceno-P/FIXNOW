<?php
session_start();
require_once __DIR__ . '/../controller/MeusChamadosControl.php';

$ctrl = new MeusChamadosControl();
$ctrl->processar();

$chamados      = $ctrl->chamados;
$stats         = $ctrl->stats;
$filtroStatus  = $ctrl->filtroStatus;
$mensagem      = $ctrl->mensagem;
$erro          = $ctrl->erro;
$naoLidas      = $ctrl->naoLidas;
$clienteNome   = $_SESSION['cliente_nome'] ?? 'Cliente';
$clienteFoto   = $_SESSION['cliente_foto'] ?? '';

$contadores = [
    ''                    => (int)$stats['total_chamados'],
    'Pendente'            => 0,
    'Aguardando Orçamento'=> 0,
    'Em Andamento'        => 0,
    'Concluído'           => 0,
    'Negado'              => 0,
];
foreach ($ctrl->chamados as $c) {
    if (isset($contadores[$c->status])) $contadores[$c->status]++;
}
// Recalcular total a partir do stats (não filtrado)
$contadores[''] = (int)$stats['total_chamados'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meus Chamados - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="../assets/css/stars-avaliacao.css" rel="stylesheet">
  <style>
    .mc-hero {
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 55%, #c95e00 100%);
      border-radius: 16px;
      padding: 2rem 2rem 1.8rem;
      position: relative;
      overflow: hidden;
      margin-bottom: 1.5rem;
    }
    .mc-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .mc-hero h1 { color:#fff; font-size:clamp(1.1rem,3vw,1.5rem); font-weight:800; margin:0 0 .2rem; position:relative; z-index:1; }
    .mc-hero p  { color:rgba(255,255,255,.72); font-size:.9rem; margin:0; position:relative; z-index:1; }

    .mc-stat { border-radius:12px; padding:.9rem 1.1rem; text-align:center; }
    .mc-stat .val { font-size:1.6rem; font-weight:800; line-height:1; }
    .mc-stat .lbl { font-size:.75rem; color:#6b7280; margin-top:.2rem; }

    .tab-status .btn { border-radius:50px; font-size:.82rem; padding:.3rem .9rem; }

    .chamado-card { border-left-width:4px!important; transition:box-shadow .15s; }
    .chamado-card:hover { box-shadow:0 6px 18px rgba(13,27,61,.1)!important; }
    .chamado-desc { font-size:.9rem; font-weight:600; color:#0d1b3d; }
    .chamado-id   { font-size:.78rem; color:#9ca3af; font-weight:600; }
    .chamado-data { font-size:.78rem; color:#6b7280; }
    .chamado-tec  { font-size:.82rem; color:#6b7280; }

    [data-theme="dark"] .chamado-card { background:#1e2538!important; border-color:#2e3650!important; }
    [data-theme="dark"] .chamado-desc { color:#e4e8f4; }
    [data-theme="dark"] .mc-stat .lbl { color:#8090b0; }
    [data-theme="dark"] .mc-hero { background: linear-gradient(135deg,#0d1b3d 0%,#1a2b63 55%,#c95e00 100%) !important; }
  </style>
</head>
<body>
<?php $paginaAtiva = 'chamados'; $_navDepth = 1; require_once __DIR__ . '/../includes/cliente_nav.php'; ?>

<main class="container py-5 mt-5">

  <!-- Hero -->
  <div class="mc-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>📋 Meus Chamados</h1>
      <p>Acompanhe, gerencie e avalie todos os seus serviços em um só lugar.</p>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm mc-stat">
        <div class="val text-primary"><?= (int)$stats['total_chamados'] ?></div>
        <div class="lbl">Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm mc-stat">
        <div class="val text-warning"><?= (int)($stats['em_andamento'] ?? 0) ?></div>
        <div class="lbl">Em Andamento</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm mc-stat">
        <div class="val text-success"><?= (int)$stats['concluidos'] ?></div>
        <div class="lbl">Concluídos</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm mc-stat">
        <div class="val" style="color:#f59e0b"><?= (int)($stats['pendentes'] ?? 0) ?></div>
        <div class="lbl">Pendentes</div>
      </div>
    </div>
  </div>

  <!-- Filtros por status -->
  <div class="tab-status d-flex flex-wrap gap-2 mb-4 align-items-center justify-content-between">
    <div class="d-flex flex-wrap gap-2">
      <?php
        $abas = [
          ''                     => ['label' => 'Todos',              'icon' => '📋'],
          'Pendente'             => ['label' => 'Pendentes',          'icon' => '⏳'],
          'Aguardando Orçamento' => ['label' => 'Aguardando Orçamento','icon' => '💬'],
          'Em Andamento'         => ['label' => 'Em Andamento',       'icon' => '🔧'],
          'Concluído'            => ['label' => 'Concluídos',         'icon' => '✅'],
          'Negado'               => ['label' => 'Negados',            'icon' => '❌'],
        ];
        foreach ($abas as $st => $info):
          $ativo = $filtroStatus === $st;
          $url   = 'meusChamados.php' . ($st ? '?status=' . urlencode($st) : '');
          $cnt   = $contadores[$st] ?? 0;
      ?>
        <a href="<?= $url ?>"
           class="btn btn-sm <?= $ativo ? 'btn-primary' : 'btn-outline-secondary' ?>">
          <?= $info['icon'] ?> <?= $info['label'] ?>
          <?php if ($cnt > 0): ?>
            <span class="badge ms-1 <?= $ativo ? 'bg-white text-primary' : 'bg-secondary' ?>"><?= $cnt ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <a href="cliente/solicitar.php" class="btn btn-warning btn-sm fw-semibold">+ Novo chamado</a>
  </div>

  <?php if ($mensagem): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <!-- Lista de chamados -->
  <?php if (!$chamados): ?>
    <div class="card border-0 shadow-sm text-center py-5">
      <div class="card-body">
        <div style="font-size:2.5rem;margin-bottom:.5rem">📭</div>
        <h5 class="fw-bold mb-1">Nenhum chamado<?= $filtroStatus ? ' com status "' . htmlspecialchars($filtroStatus) . '"' : '' ?></h5>
        <p class="text-muted small mb-3">
          <?= $filtroStatus ? 'Tente outro filtro ou abra um novo chamado.' : 'Abra seu primeiro chamado e encontre o prestador ideal.' ?>
        </p>
        <a href="cliente/solicitar.php" class="btn btn-warning fw-semibold">+ Nova solicitação</a>
      </div>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3">
    <?php foreach ($chamados as $c):
      $podePagar     = $c->status === 'Concluído' && $c->pagamentoId && $c->pagStatus === 'Pendente';
      $jaAvaliado    = $c->avaliacaoNota !== null;
      $podeAvaliar   = $c->status === 'Concluído' && !empty($c->tecnicoNome) && !$jaAvaliado;
      $podeReagendar = in_array($c->status, ['Pendente','Aguardando Orçamento','Em Andamento']);
      $valorFmt      = 'R$ ' . number_format((float)($c->pagValor ?? $c->precoSugerido), 2, ',', '.');

      [$statusBg, $statusTxt] = match($c->status) {
        'Pendente'             => ['#fff3cd', '#92400e'],
        'Aguardando Orçamento' => ['#cff4fc', '#055160'],
        'Em Andamento'         => ['#dbeafe', '#1d4ed8'],
        'Concluído'            => ['#dcfce7', '#15803d'],
        'Negado'               => ['#f3f4f6', '#374151'],
        default                => ['#f3f4f6', '#374151'],
      };
      $accentColor = match($c->status) {
        'Em Andamento' => '#1d4ed8',
        'Concluído'    => '#15803d',
        'Negado'       => '#9ca3af',
        default        => '#f59e0b',
      };
    ?>
      <div class="card border-0 shadow-sm chamado-card" style="border-left-color:<?= $accentColor ?>!important">
        <div class="card-body p-3">

          <!-- Linha 1 -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="chamado-id">#<?= $c->id ?></span>
              <span class="badge rounded-pill" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem"><?= htmlspecialchars($c->categoria) ?></span>
              <span class="badge rounded-pill" style="background:<?= $statusBg ?>;color:<?= $statusTxt ?>;font-size:.75rem"><?= htmlspecialchars($c->status) ?></span>
              <?php if ($podePagar): ?>
                <span class="badge rounded-pill" style="background:#fef9c3;color:#92400e;font-size:.72rem">💳 Pagamento pendente</span>
              <?php elseif ($c->pagamentoId && $c->pagStatus === 'Pago'): ?>
                <span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;font-size:.72rem">✓ Pago</span>
              <?php endif; ?>
            </div>
            <?php if ($c->dataAgendamento): ?>
              <span class="chamado-data">🗓 <?= date('d/m/Y H:i', strtotime($c->dataAgendamento)) ?></span>
            <?php else: ?>
              <span class="chamado-data text-muted small">📅 <?= date('d/m/Y', strtotime($c->criadoEm)) ?></span>
            <?php endif; ?>
          </div>

          <!-- Linha 2 -->
          <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div class="flex-grow-1" style="min-width:0">
              <p class="chamado-desc mb-1"><?= htmlspecialchars(mb_strimwidth($c->descricao, 0, 100, '…')) ?></p>
              <?php if ($c->tecnicoNome): ?>
                <div class="d-flex align-items-center gap-2">
                  <?php if (!empty($c->tecnicoFoto)): ?>
                    <img src="../<?= htmlspecialchars($c->tecnicoFoto) ?>" class="rounded-circle flex-shrink-0" style="width:22px;height:22px;object-fit:cover" alt="">
                  <?php else: ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:22px;height:22px;background:#0d1b3d;color:#fff;font-size:.6rem;font-weight:700"><?= mb_substr($c->tecnicoNome,0,1) ?></div>
                  <?php endif; ?>
                  <span class="chamado-tec"><?= htmlspecialchars($c->tecnicoNome) ?></span>
                </div>
              <?php else: ?>
                <span class="text-muted small">🔍 Aguardando prestador</span>
              <?php endif; ?>
              <?php if ($jaAvaliado): ?>
                <div class="mt-1">
                  <span style="color:#f59e0b;font-size:.8rem"><?= str_repeat('★',(int)$c->avaliacaoNota) . str_repeat('☆',5-(int)$c->avaliacaoNota) ?></span>
                  <span class="text-muted" style="font-size:.75rem"> avaliado</span>
                </div>
              <?php endif; ?>
            </div>
            <?php if ($c->precoSugerido > 0): ?>
              <div class="text-end flex-shrink-0">
                <div class="fw-bold">R$ <?= number_format($c->precoSugerido,2,',','.') ?></div>
                <div class="text-muted" style="font-size:.72rem">valor estimado</div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Ações -->
          <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
            <?php if ($c->status === 'Em Andamento'): ?>
              <a class="btn btn-sm btn-outline-warning" href="rastreamento.php?chamado=<?= $c->id ?>">📍 Rastrear</a>
            <?php endif; ?>
            <?php if ($c->status === 'Pendente' && empty($c->tecnicoId)): ?>
              <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal" data-bs-target="#modalAlterar"
                data-chamado-id="<?= $c->id ?>"
                data-descricao="<?= htmlspecialchars($c->descricao, ENT_QUOTES) ?>"
                data-endereco="<?= htmlspecialchars($c->enderecoServico, ENT_QUOTES) ?>">Alterar</button>
              <form method="post" class="d-inline js-guard-submit" onsubmit="return confirm('Cancelar este chamado?');">
                <input type="hidden" name="cancelar_chamado_id" value="<?= $c->id ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
              </form>
            <?php endif; ?>
            <?php if ($podeReagendar): ?>
              <button type="button" class="btn btn-sm btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#modalReagendar"
                data-chamado-id="<?= $c->id ?>"
                data-tecnico-id="<?= (int)($c->tecnicoId ?? 0) ?>"
                data-data-atual="<?= htmlspecialchars($c->dataAgendamento ?? '') ?>">Reagendar</button>
            <?php endif; ?>
            <?php if ($podePagar): ?>
              <button type="button" class="btn btn-sm btn-success fw-semibold"
                data-bs-open-pagamento
                data-pagamento-id="<?= $c->pagamentoId ?>"
                data-chamado-id="<?= $c->id ?>"
                data-valor="<?= htmlspecialchars($valorFmt) ?>">💳 Pagar</button>
            <?php endif; ?>
            <?php if ($podeAvaliar): ?>
              <button type="button" class="btn btn-sm btn-outline-warning"
                data-bs-open-avaliacao
                data-chamado-id="<?= $c->id ?>"
                data-tecnico-nome="<?= htmlspecialchars($c->tecnicoNome ?? '') ?>">⭐ Avaliar</button>
            <?php endif; ?>
            <a href="chat.php?chamado=<?= $c->id ?>" class="btn btn-sm btn-outline-primary">💬 Chat</a>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<!-- Modal Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down" style="max-width:440px">
    <div class="modal-content border-0 shadow-lg overflow-hidden">
      <div class="p-4 text-white" style="background:var(--fix-blue)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75 mb-1">Total a pagar · Chamado <span id="pag-chamado-ref">#—</span></div>
            <div class="fw-bold fs-3" id="pag-valor-display">R$ 0,00</div>
          </div>
          <button type="button" class="btn-close btn-close-white mt-1" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="px-4 pt-3">
        <ul class="nav nav-pills gap-2" id="pag-tabs" role="tablist">
          <li class="nav-item"><button class="nav-link active px-3 py-1" data-bs-toggle="pill" data-bs-target="#tab-pix" type="button"><i class="bi bi-qr-code me-1"></i>PIX</button></li>
          <li class="nav-item"><button class="nav-link px-3 py-1" data-bs-toggle="pill" data-bs-target="#tab-cartao" type="button"><i class="bi bi-credit-card me-1"></i>Cartão</button></li>
          <li class="nav-item"><button class="nav-link px-3 py-1" data-bs-toggle="pill" data-bs-target="#tab-dinheiro" type="button"><i class="bi bi-cash me-1"></i>Dinheiro</button></li>
        </ul>
      </div>
      <form method="post" id="form-confirmar-pagamento" class="js-guard-submit">
        <input type="hidden" name="confirmar_pagamento_id" value="">
        <input type="hidden" name="metodo_pagamento" id="pag-metodo-hidden" value="PIX">
        <div class="tab-content px-4 pt-3 pb-1">
          <div class="tab-pane fade show active" id="tab-pix" role="tabpanel">
            <div class="text-center mb-3"><div class="d-inline-flex align-items-center justify-content-center rounded-3 border bg-white p-2 mb-2"><div id="pix-qrcode"></div></div><div class="small text-muted">Escaneie com o app do seu banco</div></div>
            <div class="mb-1"><label class="form-label small fw-semibold">Código copia e cola</label><div class="input-group input-group-sm"><input type="text" class="form-control font-monospace" id="pix-copia-cola" readonly><button type="button" class="btn btn-outline-secondary" id="btn-copiar-pix"><i class="bi bi-clipboard"></i></button></div></div>
          </div>
          <div class="tab-pane fade" id="tab-cartao" role="tabpanel">
            <div class="mb-3"><label class="form-label small fw-semibold">Número do cartão</label><input type="text" class="form-control" id="cartao-numero" placeholder="0000 0000 0000 0000" maxlength="19" inputmode="numeric"></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Nome no cartão</label><input type="text" class="form-control text-uppercase" id="cartao-nome" placeholder="NOME SOBRENOME"></div>
            <div class="row g-2 mb-1"><div class="col-6"><label class="form-label small fw-semibold">Validade</label><input type="text" class="form-control" id="cartao-validade" placeholder="MM/AA" maxlength="5"></div><div class="col-6"><label class="form-label small fw-semibold">CVV</label><input type="text" class="form-control" id="cartao-cvv" placeholder="•••" maxlength="3"></div></div>
          </div>
          <div class="tab-pane fade" id="tab-dinheiro" role="tabpanel"><div class="text-center py-3"><i class="bi bi-cash-stack text-success" style="font-size:3rem"></i><p class="mt-2 mb-1 fw-semibold">Pagamento em dinheiro</p><p class="small text-muted">O prestador registrará a confirmação após o recebimento presencial.</p></div></div>
        </div>
        <div class="px-4 pb-4 pt-2"><button type="submit" class="btn btn-warning fw-semibold w-100" id="btn-confirmar-pag">Confirmar pagamento</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Avaliação -->
<div class="modal fade" id="modalAvaliacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content overflow-hidden">
      <div style="background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);padding:1.4rem 1.5rem;position:relative;">
        <button type="button" class="btn-close btn-close-white position-absolute" style="top:.85rem;right:1rem;" data-bs-dismiss="modal"></button>
        <div class="d-flex align-items-center gap-2">
          <span style="font-size:1.6rem;">⭐</span>
          <div><h5 class="mb-0 fw-bold text-white">Avaliar atendimento</h5><p class="mb-0" style="font-size:.8rem;color:rgba(255,255,255,.7);" data-avaliacao-resumo></p></div>
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
              <span class="fn-star-legend badge px-3 py-2" data-fn-stars-legend style="background:#0d1b3d;color:#ffc107;font-size:.85rem;font-weight:700;border-radius:50px;">Excelente</span>
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Comentário <span class="text-muted fw-normal">(opcional)</span></label>
            <textarea name="comentario_avaliacao" class="form-control" rows="3" maxlength="255" placeholder="Conte como foi o atendimento..."></textarea>
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

<!-- Modal Reagendar -->
<div class="modal fade" id="modalReagendar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Reagendar serviço</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="post" class="js-guard-submit" id="form-reagendar">
        <div class="modal-body">
          <input type="hidden" name="reagendar_chamado_id" id="reagendar-chamado-id">
          <input type="hidden" name="nova_data_agendamento" id="reagendar-nova-data-hidden">
          <div id="reagendar-slots-box" class="mb-3 d-none">
            <label class="form-label">Horário disponível <span class="text-danger">*</span></label>
            <div id="reagendar-slots-loading" class="text-muted small">Buscando horários...</div>
            <select id="reagendar-select-slot" class="form-select d-none" required><option value="">Selecione...</option></select>
          </div>
          <div id="reagendar-slots-aviso" class="text-warning small d-none mb-2"></div>
          <div id="reagendar-hora-livre" class="mb-3 d-none">
            <label class="form-label">Data e hora desejada <span class="text-danger">*</span></label>
            <input type="datetime-local" id="reagendar-datetime" class="form-control" min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="reagendar-btn-confirmar" class="btn btn-warning fw-semibold" disabled>Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Alterar -->
<div class="modal fade" id="modalAlterar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Alterar chamado</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="post" class="js-guard-submit">
        <div class="modal-body">
          <input type="hidden" name="alterar_chamado_id" id="alterar-chamado-id">
          <div class="mb-3"><label class="form-label">Descrição <span class="text-danger">*</span></label><textarea name="nova_descricao" id="alterar-descricao" class="form-control" rows="3" required maxlength="500"></textarea></div>
          <div class="mb-3"><label class="form-label">Endereço <span class="text-danger">*</span></label><input type="text" name="novo_endereco" id="alterar-endereco" class="form-control" required maxlength="200"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning fw-semibold">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="../assets/js/pagamento-dashboard.js"></script>
<script src="../assets/js/avaliacao-dashboard.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var mAlterar = document.getElementById('modalAlterar');
  if (mAlterar) {
    mAlterar.addEventListener('show.bs.modal', function (e) {
      var b = e.relatedTarget;
      document.getElementById('alterar-chamado-id').value = b.dataset.chamadoId || '';
      document.getElementById('alterar-descricao').value  = b.dataset.descricao  || '';
      document.getElementById('alterar-endereco').value   = b.dataset.endereco   || '';
    });
  }
  var mReag = document.getElementById('modalReagendar');
  if (mReag) {
    var _tId = 0, _cId = 0;
    var _dias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
    function fmtDia(d){ var p=d.split('-'), dt=new Date(+p[0],+p[1]-1,+p[2]); return _dias[dt.getDay()]+', '+String(dt.getDate()).padStart(2,'0')+'/'+String(dt.getMonth()+1).padStart(2,'0'); }
    mReag.addEventListener('show.bs.modal', function(e){
      var b = e.relatedTarget;
      _cId = parseInt(b.dataset.chamadoId||'0');
      _tId = parseInt(b.dataset.tecnicoId||'0');
      document.getElementById('reagendar-chamado-id').value = _cId;
      document.getElementById('reagendar-nova-data-hidden').value = '';
      document.getElementById('reagendar-btn-confirmar').disabled = true;
      var sb=document.getElementById('reagendar-slots-box'), ld=document.getElementById('reagendar-slots-loading'),
          av=document.getElementById('reagendar-slots-aviso'), sl=document.getElementById('reagendar-select-slot'),
          hl=document.getElementById('reagendar-hora-livre'), dt=document.getElementById('reagendar-datetime');
      av.classList.add('d-none'); sl.classList.add('d-none'); sl.innerHTML='<option value="">Selecione...</option>';
      if (_tId>0){
        sb.classList.remove('d-none'); hl.classList.add('d-none'); ld.classList.remove('d-none');
        fetch('../api/slots_prestador.php?tecnico_id='+_tId+'&chamado_id='+_cId+'&dias=14')
          .then(r=>r.json()).then(res=>{
            ld.classList.add('d-none');
            var pd=res.slots_por_data||{}, ds=Object.keys(pd);
            if(res.aviso||!ds.length){av.textContent=(res.aviso||'Sem horários cadastrados.')+' Escolha um horário.';av.classList.remove('d-none');sb.classList.add('d-none');hl.classList.remove('d-none');return;}
            ds.forEach(function(dia){var g=document.createElement('optgroup');g.label=fmtDia(dia);(pd[dia]||[]).forEach(function(h){var o=document.createElement('option');o.value=dia+'|'+h;o.textContent=fmtDia(dia)+' às '+h;g.appendChild(o);});sl.appendChild(g);});
            sl.classList.remove('d-none');
          }).catch(function(){ld.classList.add('d-none');av.textContent='Erro ao buscar horários. Escolha manualmente.';av.classList.remove('d-none');sb.classList.add('d-none');hl.classList.remove('d-none');});
      } else { sb.classList.add('d-none'); hl.classList.remove('d-none'); dt.value=''; }
    });
    sl2=document.getElementById('reagendar-select-slot');
    sl2.addEventListener('change',function(){var v=this.value;document.getElementById('reagendar-nova-data-hidden').value='';document.getElementById('reagendar-btn-confirmar').disabled=true;if(v){var p=v.split('|');document.getElementById('reagendar-nova-data-hidden').value=p[0]+' '+p[1]+':00';document.getElementById('reagendar-btn-confirmar').disabled=false;}});
    document.getElementById('reagendar-datetime').addEventListener('change',function(){var v=this.value;document.getElementById('reagendar-nova-data-hidden').value=v?v.replace('T',' ')+':00':'';document.getElementById('reagendar-btn-confirmar').disabled=!v;});
    document.getElementById('form-reagendar').addEventListener('submit',function(e){if(!document.getElementById('reagendar-nova-data-hidden').value){e.preventDefault();alert('Selecione uma data e horário.');}});
  }
});
</script>
<button id="fn-dark-toggle" title="Alternar modo escuro" aria-label="Alternar modo escuro">🌙</button>
</body>
</html>
