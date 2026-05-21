<?php
session_start();
require_once __DIR__ . '/../controller/DashboardClienteControl.php';

$ctrl = new DashboardClienteControl();
$ctrl->processar();

$chamados            = $ctrl->chamados;
$stats               = $ctrl->stats;
$orcamentosPendentes = $ctrl->orcamentosPendentes;
$notificacoes        = $ctrl->notificacoes;
$servicos            = $ctrl->servicos;
$categorias          = $ctrl->categorias;
$depoimento          = $ctrl->depoimento;
$filtroCategoria        = $ctrl->filtroCategoria;
$filtroPrestadoraMulher = $ctrl->filtroPrestadoraMulher;
$clienteGenero          = $ctrl->clienteGenero;
$mensagem               = $ctrl->mensagem;
$erro                   = $ctrl->erro;
$naoLidas               = $ctrl->naoLidas;
$clienteNome            = $_SESSION['cliente_nome'] ?? 'Cliente';
$clienteFoto            = $_SESSION['cliente_foto'] ?? '';

$dicasGerais = [
    'Adicione fotos ao abrir um chamado — prestadores aceitam mais rápido quando entendem o problema.',
    'Descreva o problema com detalhes: marca, modelo e sintomas ajudam a receber o orçamento certo.',
    'Você pode reagendar um chamado em andamento sem cancelá-lo — use o botão "Reagendar" na tabela.',
    'Após o atendimento, avalie o prestador. Sua nota ajuda outros clientes a escolher melhor.',
    'Confira o portfólio do prestador antes de solicitar — veja trabalhos anteriores e avaliações.',
];
$temPendente    = count(array_filter($chamados, fn($c) => $c->status === 'Pendente')) > 0;
$temAndamento   = count(array_filter($chamados, fn($c) => $c->status === 'Em Andamento')) > 0;
$temParaPagar   = count(array_filter($chamados, fn($c) => $c->status === 'Concluído' && $c->pagStatus === 'Pendente')) > 0;
$temParaAvaliar = count(array_filter($chamados, fn($c) => $c->status === 'Concluído' && $c->avaliacaoNota === null && !empty($c->tecnicoNome))) > 0;

if ($temParaAvaliar)       $dica = 'Você tem atendimentos concluídos sem avaliação. Avalie o prestador — leva menos de 1 minuto!';
elseif ($temParaPagar)     $dica = 'Você tem um pagamento pendente. Confirme para liberar o encerramento do chamado.';
elseif ($temAndamento)     $dica = 'Seu chamado está em andamento. Você pode reagendar o horário se necessário.';
elseif ($temPendente)      $dica = 'Seu chamado está aguardando um prestador. Prestadores com foto e portfólio costumam responder mais rápido.';
else                       $dica = $dicasGerais[$ctrl->clienteId % count($dicasGerais)];

// Paginação (calculada aqui para servir tanto o AJAX quanto o render normal)
$_svcPorPagina     = 4;
$_svcPaginaAtual   = max(1, (int)($_GET['pagina'] ?? 1));
$_svcTotal         = count($servicos);
$_svcTotalPaginas  = max(1, (int)ceil($_svcTotal / $_svcPorPagina));
$_svcPaginaAtual   = min($_svcPaginaAtual, $_svcTotalPaginas);
$_svcPag           = array_slice($servicos, ($_svcPaginaAtual - 1) * $_svcPorPagina, $_svcPorPagina);
$_svcBase          = 'dashboardCliente.php?' . ($filtroCategoria ? 'categoria=' . urlencode($filtroCategoria) . '&' : '') . ($filtroPrestadoraMulher ? 'so_mulher=1&' : '');

// Resposta parcial para requisições AJAX de paginação
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=utf-8');
    if (!$_svcPag): ?>
      <div class="alert alert-info">Nenhum prestador encontrado.</div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($_svcPag as $s): ?>
          <div class="col-6 col-md-3">
            <div class="svc-card <?= $s['destaque'] ? 'destaque' : '' ?>">
              <?php if ($s['destaque']): ?><div class="svc-destaque-bar"><span style="color:#fff;font-size:.75rem;font-weight:700;">⭐ Destaque Fix Now</span></div><?php endif; ?>
              <div class="svc-card-body">
                <div class="d-flex align-items-center gap-3">
                  <?php if ($s['foto_perfil']): ?>
                    <img src="../<?= htmlspecialchars($s['foto_perfil']) ?>" alt="" class="svc-avatar">
                  <?php else: ?>
                    <div class="svc-avatar-init"><?= htmlspecialchars(mb_strtoupper(mb_substr($s['tecnico_nome'], 0, 1))) ?></div>
                  <?php endif; ?>
                  <div style="min-width:0">
                    <div class="svc-nome text-truncate"><?= htmlspecialchars($s['tecnico_nome']) ?></div>
                    <?php if ($s['media_nota'] > 0): ?>
                      <div class="svc-rating">★ <?= number_format((float)$s['media_nota'], 1) ?></div>
                    <?php else: ?>
                      <div class="svc-rating"><span class="sem-aval">Sem avaliações</span></div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="svc-divider"></div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                  <?php foreach ($s['servicos'] as $sv): ?>
                    <span class="svc-badge"><?= htmlspecialchars($sv['categoria_nome'] ?: $sv['nome']) ?></span>
                  <?php endforeach; ?>
                </div>
                <div class="mt-auto">
                  <?php $pm = (float)$s['preco_min']; $px = (float)$s['preco_max']; ?>
                  <?php if ($pm > 0): ?>
                    <span class="svc-price">R$ <?= number_format($pm, 2, ',', '.') ?>
                      <?php if ($pm !== $px): ?><span class="svc-price-range">– R$ <?= number_format($px, 2, ',', '.') ?></span><?php endif; ?>
                    </span>
                  <?php else: ?>
                    <span class="svc-price-combinar">💬 A combinar</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="svc-footer">
                <a href="portfolioPublico.php?id=<?= (int)$s['tecnico_id'] ?>" class="btn btn-sm btn-outline-secondary flex-fill">Portfólio</a>
                <a href="cliente/solicitar.php?prestador=<?= (int)$s['tecnico_id'] ?>" class="btn btn-sm btn-warning fw-semibold flex-fill">Solicitar</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($_svcTotalPaginas > 1):
        $ini = max(1, $_svcPaginaAtual - 2); $fim = min($_svcTotalPaginas, $_svcPaginaAtual + 2); ?>
      <nav class="mt-4 d-flex justify-content-center align-items-center gap-2 flex-wrap" id="svc-paginacao">
        <?php if ($_svcPaginaAtual > 1): ?><a href="<?= $_svcBase ?>pagina=<?= $_svcPaginaAtual - 1 ?>" class="btn btn-sm btn-outline-secondary">‹ Anterior</a><?php else: ?><button class="btn btn-sm btn-outline-secondary" disabled>‹ Anterior</button><?php endif; ?>
        <?php if ($ini > 1): ?><a href="<?= $_svcBase ?>pagina=1" class="btn btn-sm btn-outline-secondary">1</a><?php if ($ini > 2): ?><span class="text-muted small px-1">…</span><?php endif; ?><?php endif; ?>
        <?php for ($p = $ini; $p <= $fim; $p++): ?>
          <?php if ($p === $_svcPaginaAtual): ?><button class="btn btn-sm btn-warning fw-bold" disabled><?= $p ?></button>
          <?php else: ?><a href="<?= $_svcBase ?>pagina=<?= $p ?>" class="btn btn-sm btn-outline-secondary"><?= $p ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($fim < $_svcTotalPaginas): ?><?php if ($fim < $_svcTotalPaginas - 1): ?><span class="text-muted small px-1">…</span><?php endif; ?><a href="<?= $_svcBase ?>pagina=<?= $_svcTotalPaginas ?>" class="btn btn-sm btn-outline-secondary"><?= $_svcTotalPaginas ?></a><?php endif; ?>
        <?php if ($_svcPaginaAtual < $_svcTotalPaginas): ?><a href="<?= $_svcBase ?>pagina=<?= $_svcPaginaAtual + 1 ?>" class="btn btn-sm btn-outline-secondary">Próximo ›</a><?php else: ?><button class="btn btn-sm btn-outline-secondary" disabled>Próximo ›</button><?php endif; ?>
        <span class="text-muted small ms-2"><?= (($_svcPaginaAtual - 1) * $_svcPorPagina) + 1 ?>–<?= min($_svcPaginaAtual * $_svcPorPagina, $_svcTotal) ?> de <?= $_svcTotal ?></span>
      </nav>
      <?php endif; ?>
    <?php endif;
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Cliente - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="../assets/css/stars-avaliacao.css" rel="stylesheet">
  <style>
    /* cards de prestador */
    .svc-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;box-shadow:0 3px 10px rgba(13,27,61,.06);display:flex;flex-direction:column;height:100%;overflow:hidden;transition:box-shadow .15s,transform .15s}
    .svc-card:hover{box-shadow:0 8px 24px rgba(13,27,61,.12);transform:translateY(-2px)}
    .svc-card.destaque{border-color:#fbbf24;box-shadow:0 4px 16px rgba(251,191,36,.2)}
    .svc-destaque-bar{background:linear-gradient(90deg,#b45309,#d97706);padding:.35rem 1rem}
    .svc-card-body{padding:1.25rem;flex:1;display:flex;flex-direction:column}
    .svc-avatar{width:56px;height:56px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #e8ecf3}
    .svc-avatar-init{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#0d1b3d,#1a2b63);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.1rem;flex-shrink:0}
    .svc-nome{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.1rem}
    .svc-espec{font-size:.78rem;color:#6b7280}
    .svc-rating{font-size:.82rem;font-weight:600;color:#d97706}
    .svc-rating .sem-aval{color:#9ca3af;font-weight:400}
    .svc-divider{border-top:1px solid #f0f3fa;margin:.85rem 0}
    .svc-badge{background:#f0f3fa;color:#374151;font-size:.72rem;font-weight:600;padding:.22rem .6rem;border-radius:20px;white-space:nowrap}
    .svc-price{font-size:1.05rem;font-weight:800;color:#1d4ed8}
    .svc-price-range{font-size:.83rem;font-weight:400;color:#6b7280}
    .svc-price-combinar{font-size:.85rem;color:#9ca3af;font-style:italic}
    .svc-footer{display:flex;gap:.5rem;padding:0 1.25rem 1.25rem}
    [data-theme="dark"] .svc-card{background:#1e2538;border-color:#1e2538;box-shadow:0 2px 12px rgba(0,0,0,.35)}
    [data-theme="dark"] .svc-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.3)}
    [data-theme="dark"] .svc-nome{color:#e4e8f4}
    [data-theme="dark"] .svc-divider{border-top-color:#2e3650}
    [data-theme="dark"] .svc-badge{background:#252e45;color:#a0aec0}
    [data-theme="dark"] .svc-avatar{border-color:#2e3650}
    [data-theme="dark"] .svc-price{color:#60a5fa}

    .chamado-card{border-left-width:4px!important;transition:box-shadow .15s}
    .chamado-card:hover{box-shadow:0 6px 18px rgba(13,27,61,.1)!important}
    .chamado-desc{font-size:.9rem;font-weight:600;color:#0d1b3d;margin-bottom:.25rem}
    .chamado-preco .val{font-size:1rem;font-weight:700;color:#0d1b3d}
    .chamado-preco .lbl{font-size:.72rem;color:#9ca3af}
    .chamado-separator{border-top:1px solid #f0f3fa;margin-top:.5rem;padding-top:.5rem}
    .chamado-id{font-size:.78rem;color:#9ca3af;font-weight:600}
    .chamado-data{font-size:.78rem;color:#6b7280}
    .chamado-tec-nome{font-size:.82rem;color:#6b7280}
    .chamado-aguarda{font-size:.8rem;color:#9ca3af}
    .chamado-avaliado{font-size:.75rem;color:#9ca3af}

    [data-theme="dark"] .chamado-card{background:#1e2538!important;border-color:#2e3650!important}
    [data-theme="dark"] .chamado-card .chamado-desc{color:#e4e8f4}
    [data-theme="dark"] .chamado-card .chamado-preco .val{color:#e4e8f4}
    [data-theme="dark"] .chamado-card .chamado-preco .lbl{color:#6b7280}
    [data-theme="dark"] .chamado-card .chamado-separator{border-top-color:#2e3650}
    [data-theme="dark"] .chamado-card .chamado-id{color:#5a6a8a}
    [data-theme="dark"] .chamado-card .chamado-data{color:#8090b0}
    [data-theme="dark"] .chamado-card .chamado-tec-nome{color:#8090b0}
    [data-theme="dark"] .chamado-card .chamado-aguarda{color:#5a6a8a}
    [data-theme="dark"] .chamado-card .chamado-avaliado{color:#6b7280}
  </style>
</head>
<body>
<?php $paginaAtiva = 'dashboard'; $_navDepth = 1; require_once __DIR__ . '/../includes/cliente_nav.php'; ?>

<main class="container py-5 mt-5">
  <?php
    $primeiroNome    = explode(' ', trim($clienteNome))[0];
    $emAndamentoCount = count(array_filter($chamados, fn($c) => $c->status === 'Em Andamento'));
    $proximoChamado   = null;
    foreach ($chamados as $c) { if ($c->status === 'Em Andamento') { $proximoChamado = $c; break; } }
  ?>

  <!-- 1. Hero personalizado -->
  <section class="fn-hero p-4 p-lg-5 mb-4">
    <div class="row align-items-center g-3">
      <div class="col-auto">
        <?php if ($clienteFoto): ?>
          <img src="../<?php echo htmlspecialchars($clienteFoto); ?>" alt="Foto"
               class="rounded-circle border border-white border-2"
               style="width:64px;height:64px;object-fit:cover;">
        <?php else: ?>
          <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center fw-bold text-white"
               style="width:64px;height:64px;font-size:1.5rem;">
            <?php echo htmlspecialchars(mb_substr($primeiroNome, 0, 1)); ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="col">
        <p class="text-white-50 small mb-0">Bem-vindo de volta</p>
        <h1 class="h3 mb-1">Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋</h1>
        <p class="mb-0 text-white-50 small">Conectamos você aos melhores técnicos com agilidade e confiança.</p>
      </div>
      <div class="col-lg-3 text-lg-end mt-2 mt-lg-0">
        <a href="cliente/solicitar.php" class="btn btn-light btn-lg px-4">+ Nova solicitação</a>
      </div>
    </div>
  </section>

  <!-- 2. Cards de estatísticas visuais -->
  <section class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-primary"
               style="width:52px;height:52px;background:rgba(13,110,253,.1);font-size:1.4rem;">📋</div>
          <div>
            <p class="text-muted mb-0 small">Solicitados</p>
            <h3 class="mb-0 fw-bold"><?php echo (int)$stats['total_chamados']; ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-warning"
               style="width:52px;height:52px;background:rgba(255,193,7,.15);font-size:1.4rem;">⏳</div>
          <div>
            <p class="text-muted mb-0 small">Em Andamento</p>
            <h3 class="mb-0 fw-bold"><?php echo $emAndamentoCount; ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-success"
               style="width:52px;height:52px;background:rgba(25,135,84,.1);font-size:1.4rem;">✅</div>
          <div>
            <p class="text-muted mb-0 small">Concluídos</p>
            <h3 class="mb-0 fw-bold"><?php echo (int)$stats['concluidos']; ?></h3>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 5. Dica contextual em destaque -->
  <div class="alert border-0 shadow-sm d-flex align-items-start gap-2 mb-4"
       style="background:rgba(255,193,7,.12);border-left:4px solid #ffc107 !important;">
    <span style="font-size:1.2rem;line-height:1.4">💡</span>
    <span class="small"><?php echo htmlspecialchars($dica); ?></span>
  </div>

  <!-- 3. Próximo atendimento em destaque -->
  <?php if ($proximoChamado): ?>
  <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #0d6efd !important;">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
               style="width:48px;height:48px;font-size:1.3rem;">🔧</div>
          <div>
            <p class="text-muted small mb-0">Próximo atendimento</p>
            <h5 class="mb-0 fw-semibold"><?php echo htmlspecialchars($proximoChamado->categoria); ?></h5>
            <small class="text-muted">
              <?php echo htmlspecialchars(mb_strimwidth($proximoChamado->descricao, 0, 60, '...')); ?>
              <?php if ($proximoChamado->tecnicoNome): ?>
                · Técnico: <strong><?php echo htmlspecialchars($proximoChamado->tecnicoNome); ?></strong>
              <?php endif; ?>
              <?php if ($proximoChamado->dataAgendamento): ?>
                · <?php echo date('d/m/Y H:i', strtotime($proximoChamado->dataAgendamento)); ?>
              <?php endif; ?>
            </small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="rastreamento.php?chamado=<?php echo $proximoChamado->id; ?>"
             class="btn btn-sm btn-outline-primary">📍 Rastrear</a>
          <a href="chat.php?chamado=<?php echo $proximoChamado->id; ?>"
             class="btn btn-sm btn-outline-secondary">💬 Chat</a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($orcamentosPendentes): ?>
  <div class="card shadow-sm border-0 border-warning border-start border-3 mb-4">
    <div class="card-body">
      <h3 class="h5 mb-3">⚡ Orçamentos aguardando sua decisão</h3>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-primary">
            <tr><th>#</th><th>Prestador</th><th>Chamado</th><th>Valor proposto</th><th>Observação</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach ($orcamentosPendentes as $orc): ?>
            <tr>
              <td class="text-muted small"><?php echo (int)$orc['chamado_id']; ?></td>
              <td class="fw-semibold"><?php echo htmlspecialchars($orc['tecnico_nome']); ?></td>
              <td><?php echo htmlspecialchars(mb_strimwidth($orc['chamado_desc'], 0, 40, '...')); ?></td>
              <td class="fw-bold text-primary">R$ <?php echo number_format((float)$orc['valor'], 2, ',', '.'); ?></td>
              <td class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($orc['descricao'] ?? '—', 0, 50, '...')); ?></td>
              <td class="d-flex gap-1 flex-wrap">
                <form method="post" class="d-inline js-guard-submit" onsubmit="return confirm('Aceitar este orçamento?');">
                  <input type="hidden" name="aceitar_orcamento_id" value="<?php echo (int)$orc['id']; ?>">
                  <button class="btn btn-sm btn-success fw-semibold">Aceitar</button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-danger"
                  data-bs-toggle="modal" data-bs-target="#modalRecusarOrcamento"
                  data-orcamento-id="<?php echo (int)$orc['id']; ?>"
                  data-tecnico-nome="<?php echo htmlspecialchars($orc['tecnico_nome'], ENT_QUOTES); ?>">
                  Recusar
                </button>
                <a href="chat.php?chamado=<?php echo (int)$orc['chamado_id']; ?>" class="btn btn-sm btn-outline-primary">💬 Chat</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php
    $baseUrl  = 'dashboardCliente.php';
    $soMulher = $filtroPrestadoraMulher ? '&so_mulher=1' : '';
    $catIcons = [
      'Suporte TI'   => '💻', 'Elétrica'    => '⚡', 'Hidráulica' => '🔧',
      'Pintura'      => '🎨', 'Marcenaria'  => '🪵', 'Limpeza'    => '🧹',
      'Refrigeração' => '❄️',  'Jardinagem'  => '🌿',
    ];
    // Vars de paginação definidas no topo do arquivo: $_svcPorPagina, $_svcPaginaAtual, $_svcTotal, $_svcTotalPaginas, $_svcPag, $_svcBase
  ?>
  <section id="encontrar-prestador" class="mb-5">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h2 class="mb-0">Encontre um prestador</h2>
        <p class="text-muted mb-0 small">
          <?php echo count($servicos); ?> prestador<?php echo count($servicos) !== 1 ? 'es' : ''; ?> disponíve<?php echo count($servicos) !== 1 ? 'is' : 'l'; ?>
          <?php echo $filtroCategoria ? ' em <strong>' . htmlspecialchars($filtroCategoria) . '</strong>' : ''; ?>
        </p>
      </div>
      <?php if ($filtroCategoria || $filtroPrestadoraMulher): ?>
        <a href="dashboardCliente.php#encontrar-prestador" class="btn btn-sm btn-outline-danger">
          ✕ Limpar filtros
        </a>
      <?php endif; ?>
    </div>

    <!-- Filtros de categoria -->
    <div class="d-flex flex-wrap gap-2 mb-3">
      <a href="<?php echo $baseUrl . ($soMulher ? '?so_mulher=1' : ''); ?>#encontrar-prestador"
         class="btn btn-sm d-flex align-items-center gap-1 <?php echo $filtroCategoria === '' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
        🔍 Todos
      </a>
      <?php foreach ($categorias as $cat): ?>
        <?php
          $catParam = '?categoria=' . urlencode($cat['nome']) . ($filtroPrestadoraMulher ? '&so_mulher=1' : '');
          $icon = $catIcons[$cat['nome']] ?? '🔧';
          $ativo = $filtroCategoria === $cat['nome'];
        ?>
        <a href="<?php echo $baseUrl . $catParam; ?>#encontrar-prestador"
           class="btn btn-sm d-flex align-items-center gap-1 <?php echo $ativo ? 'btn-primary' : 'btn-outline-secondary'; ?>">
          <?php echo $icon; ?> <?php echo htmlspecialchars($cat['nome']); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Filtro somente mulheres (exclusivo para clientes femininas) -->
    <?php if ($clienteGenero === 'Feminino'): ?>
      <?php
        $toggleUrl  = $baseUrl . ($filtroCategoria ? '?categoria=' . urlencode($filtroCategoria) : '?');
        $toggleUrl .= ($filtroCategoria ? '&' : '') . ($filtroPrestadoraMulher ? '' : 'so_mulher=1');
        $toggleUrl .= '#encontrar-prestador';
      ?>
      <div class="mb-4">
        <a href="<?php echo $toggleUrl; ?>"
           class="btn btn-sm <?php echo $filtroPrestadoraMulher ? 'btn-pink' : 'btn-outline-pink'; ?>">
          <i class="bi bi-gender-female me-1"></i>
          <?php echo $filtroPrestadoraMulher ? '✓ Somente prestadoras mulheres' : 'Somente prestadoras mulheres'; ?>
        </a>
      </div>
    <?php else: ?>
      <div class="mb-4"></div>
    <?php endif; ?>
    <div id="svc-resultado">
    <?php if (!$servicos): ?>
      <div class="alert alert-info">
        Nenhum prestador encontrado<?php echo $filtroCategoria ? ' para a categoria <strong>' . htmlspecialchars($filtroCategoria) . '</strong>' : ''; ?>
        <?php echo $filtroPrestadoraMulher ? ' entre as prestadoras mulheres' : ''; ?>.
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($_svcPag as $s): ?>
          <div class="col-6 col-md-3">
            <div class="svc-card <?php echo $s['destaque'] ? 'destaque' : ''; ?>">

              <?php if ($s['destaque']): ?>
                <div class="svc-destaque-bar">
                  <span style="color:#fff;font-size:.75rem;font-weight:700;">⭐ Destaque Fix Now</span>
                </div>
              <?php endif; ?>

              <div class="svc-card-body">

                <!-- Foto + nome + avaliação -->
                <div class="d-flex align-items-center gap-3">
                  <?php if ($s['foto_perfil']): ?>
                    <img src="../<?php echo htmlspecialchars($s['foto_perfil']); ?>" alt="" class="svc-avatar">
                  <?php else: ?>
                    <div class="svc-avatar-init"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($s['tecnico_nome'], 0, 1))); ?></div>
                  <?php endif; ?>
                  <div style="min-width:0">
                    <div class="svc-nome text-truncate"><?php echo htmlspecialchars($s['tecnico_nome']); ?></div>
                    <?php if ($s['media_nota'] > 0): ?>
                      <div class="svc-rating">★ <?php echo number_format((float)$s['media_nota'], 1); ?></div>
                    <?php else: ?>
                      <div class="svc-rating"><span class="sem-aval">Sem avaliações</span></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="svc-divider"></div>

                <!-- Categorias -->
                <div class="d-flex flex-wrap gap-1 mb-3">
                  <?php foreach ($s['servicos'] as $sv): ?>
                    <span class="svc-badge"><?php echo htmlspecialchars($sv['categoria_nome'] ?: $sv['nome']); ?></span>
                  <?php endforeach; ?>
                </div>

                <!-- Preço -->
                <div class="mt-auto">
                  <?php $precoMin = (float)$s['preco_min']; $precoMax = (float)$s['preco_max']; ?>
                  <?php if ($precoMin > 0): ?>
                    <?php if ($precoMin === $precoMax): ?>
                      <span class="svc-price">R$ <?php echo number_format($precoMin, 2, ',', '.'); ?></span>
                    <?php else: ?>
                      <span class="svc-price">R$ <?php echo number_format($precoMin, 2, ',', '.'); ?>
                        <span class="svc-price-range">– R$ <?php echo number_format($precoMax, 2, ',', '.'); ?></span>
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="svc-price-combinar">💬 A combinar</span>
                  <?php endif; ?>
                </div>

              </div>

              <!-- Botões fora do body para ficarem grudados no rodapé -->
              <div class="svc-footer">
                <a href="portfolioPublico.php?id=<?php echo (int)$s['tecnico_id']; ?>"
                   class="btn btn-sm btn-outline-secondary flex-fill">Portfólio</a>
                <a href="cliente/solicitar.php?prestador=<?php echo (int)$s['tecnico_id']; ?>"
                   class="btn btn-sm btn-warning fw-semibold flex-fill">Solicitar</a>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($_svcTotalPaginas > 1):
        $inicio = max(1, $_svcPaginaAtual - 2); $fim = min($_svcTotalPaginas, $_svcPaginaAtual + 2); ?>
      <nav class="mt-4 d-flex justify-content-center align-items-center gap-2 flex-wrap" id="svc-paginacao">
        <?php if ($_svcPaginaAtual > 1): ?>
          <a href="<?= $_svcBase ?>pagina=<?= $_svcPaginaAtual - 1 ?>" class="btn btn-sm btn-outline-secondary">‹ Anterior</a>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-secondary" disabled>‹ Anterior</button>
        <?php endif; ?>
        <?php if ($inicio > 1): ?><a href="<?= $_svcBase ?>pagina=1" class="btn btn-sm btn-outline-secondary">1</a><?php if ($inicio > 2): ?><span class="text-muted small px-1">…</span><?php endif; ?><?php endif; ?>
        <?php for ($p = $inicio; $p <= $fim; $p++): ?>
          <?php if ($p === $_svcPaginaAtual): ?><button class="btn btn-sm btn-warning fw-bold" disabled><?= $p ?></button>
          <?php else: ?><a href="<?= $_svcBase ?>pagina=<?= $p ?>" class="btn btn-sm btn-outline-secondary"><?= $p ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($fim < $_svcTotalPaginas): ?><?php if ($fim < $_svcTotalPaginas - 1): ?><span class="text-muted small px-1">…</span><?php endif; ?><a href="<?= $_svcBase ?>pagina=<?= $_svcTotalPaginas ?>" class="btn btn-sm btn-outline-secondary"><?= $_svcTotalPaginas ?></a><?php endif; ?>
        <?php if ($_svcPaginaAtual < $_svcTotalPaginas): ?>
          <a href="<?= $_svcBase ?>pagina=<?= $_svcPaginaAtual + 1 ?>" class="btn btn-sm btn-outline-secondary">Próximo ›</a>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-secondary" disabled>Próximo ›</button>
        <?php endif; ?>
        <span class="text-muted small ms-2"><?= (($_svcPaginaAtual - 1) * $_svcPorPagina) + 1 ?>–<?= min($_svcPaginaAtual * $_svcPorPagina, $_svcTotal) ?> de <?= $_svcTotal ?></span>
      </nav>
      <?php endif; ?>

    <?php endif; ?>
    </div><!-- #svc-resultado -->
  </section>

  <?php
    // Chamados que já aparecem na seção de orçamentos: não duplicar na tabela abaixo
    $chamadosComOrcamentoPendente = array_flip(array_column($orcamentosPendentes, 'chamado_id'));
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Meus chamados</h2>
  </div>

  <?php if ($mensagem): ?>
    <?php if (isset($_GET['avaliacao_ok'])): ?>
      <div class="alert alert-success js-flash-reload" data-reload-ms="2200"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php else: ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <?php if (!$chamados): ?>
    <div class="card border-0 shadow-sm text-center py-5">
      <div class="card-body">
        <div style="font-size:2.5rem;margin-bottom:.5rem">📋</div>
        <h5 class="fw-bold mb-1">Nenhum chamado ainda</h5>
        <p class="text-muted small mb-3">Abra seu primeiro chamado e encontre o prestador ideal.</p>
        <a href="cliente/solicitar.php" class="btn btn-warning fw-semibold">+ Nova solicitação</a>
      </div>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3">
    <?php foreach ($chamados as $c): ?>
      <?php if (isset($chamadosComOrcamentoPendente[$c->id])) continue; ?>
      <?php
        $podePagar     = $c->status === 'Concluído' && $c->pagamentoId && $c->pagStatus === 'Pendente';
        $jaAvaliado    = $c->avaliacaoNota !== null;
        $podeAvaliar   = $c->status === 'Concluído' && !empty($c->tecnicoNome) && !$jaAvaliado;
        $podeReagendar = in_array($c->status, ['Pendente','Aguardando Orçamento','Em Andamento']);
        $valorFmt      = 'R$ ' . number_format((float)($c->pagValor ?? $c->precoSugerido), 2, ',', '.');

        [$statusBg, $statusTxt] = match($c->status) {
          'Pendente'            => ['#fff3cd', '#92400e'],
          'Aguardando Orçamento'=> ['#cff4fc', '#055160'],
          'Em Andamento'        => ['#dbeafe', '#1d4ed8'],
          'Concluído'           => ['#dcfce7', '#15803d'],
          'Negado'              => ['#f3f4f6', '#374151'],
          default               => ['#f3f4f6', '#374151'],
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

          <!-- Linha 1: ID + Categoria + Status + Data -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="chamado-id">#<?= $c->id ?></span>
              <span class="badge rounded-pill" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem">
                <?= htmlspecialchars($c->categoria) ?>
              </span>
              <span class="badge rounded-pill" style="background:<?= $statusBg ?>;color:<?= $statusTxt ?>;font-size:.75rem">
                <?= htmlspecialchars($c->status) ?>
              </span>
              <?php if ($podePagar): ?>
                <span class="badge rounded-pill" style="background:#fef9c3;color:#92400e;font-size:.72rem">💳 Pagamento pendente</span>
              <?php elseif ($c->pagamentoId && $c->pagStatus === 'Pago'): ?>
                <span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;font-size:.72rem">✓ Pago</span>
              <?php endif; ?>
            </div>
            <?php if ($c->dataAgendamento): ?>
              <span class="chamado-data">🗓 <?= date('d/m/Y H:i', strtotime($c->dataAgendamento)) ?></span>
            <?php endif; ?>
          </div>

          <!-- Linha 2: Descrição + Técnico + Preço -->
          <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div class="flex-grow-1 min-w-0">
              <p class="chamado-desc mb-1"><?= htmlspecialchars(mb_strimwidth($c->descricao, 0, 80, '…')) ?></p>

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
                  <span class="chamado-tec-nome"><?= htmlspecialchars($c->tecnicoNome) ?></span>
                </div>
              <?php else: ?>
                <span class="chamado-aguarda">🔍 Aguardando prestador</span>
              <?php endif; ?>

              <?php if ($jaAvaliado && $c->status === 'Concluído'): ?>
                <div class="mt-1">
                  <span style="color:#f59e0b;font-size:.8rem"><?= str_repeat('★', (int)$c->avaliacaoNota) . str_repeat('☆', 5 - (int)$c->avaliacaoNota) ?></span>
                  <span class="chamado-avaliado"> avaliado</span>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($c->precoSugerido > 0): ?>
              <div class="chamado-preco text-end flex-shrink-0">
                <div class="val">R$ <?= number_format($c->precoSugerido, 2, ',', '.') ?></div>
                <div class="lbl">valor estimado</div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Linha 3: Ações -->
          <div class="chamado-separator d-flex flex-wrap gap-1">
            <?php if ($c->status === 'Em Andamento'): ?>
              <a class="btn btn-sm btn-outline-warning" href="rastreamento.php?chamado=<?= $c->id ?>">📍 Rastrear</a>
            <?php endif; ?>
            <?php if ($c->status === 'Pendente' && empty($c->tecnicoId)): ?>
              <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal" data-bs-target="#modalAlterarChamado"
                data-chamado-id="<?= $c->id ?>"
                data-descricao="<?= htmlspecialchars($c->descricao, ENT_QUOTES) ?>"
                data-endereco="<?= htmlspecialchars($c->enderecoServico, ENT_QUOTES) ?>">Alterar</button>
              <form method="post" class="d-inline js-guard-submit" onsubmit="return confirm('Cancelar este agendamento?');">
                <input type="hidden" name="cancelar_chamado_id" value="<?= $c->id ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
              </form>
            <?php endif; ?>
            <?php if ($podeReagendar): ?>
              <button type="button" class="btn btn-sm btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#modalReagendarChamado"
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
                data-tecnico-nome="<?= htmlspecialchars($c->tecnicoNome ?? '') ?>">⭐ Avaliar prestador</button>
            <?php endif; ?>
            <a href="chat.php?chamado=<?= $c->id ?>" class="btn btn-sm btn-outline-primary">💬 Chat</a>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($depoimento): ?>
  <section class="mt-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-start gap-3">
        <span class="fs-4">💬</span>
        <div>
          <p class="text-muted fst-italic mb-1">"<?php echo htmlspecialchars($depoimento['comentario']); ?>"</p>
          <div class="d-flex align-items-center gap-2">
            <span class="text-warning small"><?php echo str_repeat('★', (int)$depoimento['nota']); ?></span>
            <small class="text-muted">— <?php echo htmlspecialchars(mb_substr($depoimento['cliente_nome'], 0, 1) . str_repeat('*', max(0, mb_strlen($depoimento['cliente_nome']) - 2)) . mb_substr($depoimento['cliente_nome'], -1)); ?>, cliente Fix Now</small>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>
</main>

<!-- Modal Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down" style="max-width:440px">
    <div class="modal-content border-0 shadow-lg overflow-hidden">

      <!-- Cabeçalho com valor -->
      <div class="p-4 text-white" style="background:var(--fix-blue)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75 mb-1">Total a pagar · Chamado <span id="pag-chamado-ref">#—</span></div>
            <div class="fw-bold fs-3" id="pag-valor-display">R$ 0,00</div>
          </div>
          <button type="button" class="btn-close btn-close-white mt-1" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <!-- Abas de método -->
      <div class="px-4 pt-3">
        <ul class="nav nav-pills gap-2" id="pag-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active px-3 py-1" id="tab-pix-btn" data-bs-toggle="pill" data-bs-target="#tab-pix" type="button" role="tab">
              <i class="bi bi-qr-code me-1"></i>PIX
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-3 py-1" id="tab-cartao-btn" data-bs-toggle="pill" data-bs-target="#tab-cartao" type="button" role="tab">
              <i class="bi bi-credit-card me-1"></i>Cartão
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-3 py-1" id="tab-dinheiro-btn" data-bs-toggle="pill" data-bs-target="#tab-dinheiro" type="button" role="tab">
              <i class="bi bi-cash me-1"></i>Dinheiro
            </button>
          </li>
        </ul>
      </div>

      <form method="post" id="form-confirmar-pagamento" class="js-guard-submit">
        <input type="hidden" name="confirmar_pagamento_id" value="">
        <input type="hidden" name="metodo_pagamento" id="pag-metodo-hidden" value="PIX">

        <div class="tab-content px-4 pt-3 pb-1">

          <!-- PIX -->
          <div class="tab-pane fade show active" id="tab-pix" role="tabpanel">
            <div class="text-center mb-3">
              <div class="d-inline-flex align-items-center justify-content-center rounded-3 border bg-white p-2 mb-2">
                <div id="pix-qrcode"></div>
              </div>
              <div class="small text-muted">Escaneie com o app do seu banco</div>
            </div>
            <div class="mb-1">
              <label class="form-label small fw-semibold">Código copia e cola</label>
              <div class="input-group input-group-sm">
                <input type="text" class="form-control font-monospace" id="pix-copia-cola" readonly>
                <button type="button" class="btn btn-outline-secondary" id="btn-copiar-pix" title="Copiar"><i class="bi bi-clipboard"></i></button>
              </div>
            </div>
          </div>

          <!-- Cartão -->
          <div class="tab-pane fade" id="tab-cartao" role="tabpanel">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Número do cartão</label>
              <input type="text" class="form-control" id="cartao-numero" placeholder="0000 0000 0000 0000" maxlength="19" inputmode="numeric" autocomplete="cc-number">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Nome no cartão</label>
              <input type="text" class="form-control text-uppercase" id="cartao-nome" placeholder="NOME SOBRENOME" autocomplete="cc-name">
            </div>
            <div class="row g-2 mb-1">
              <div class="col-6">
                <label class="form-label small fw-semibold">Validade</label>
                <input type="text" class="form-control" id="cartao-validade" placeholder="MM/AA" maxlength="5" inputmode="numeric" autocomplete="cc-exp">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">CVV</label>
                <input type="text" class="form-control" id="cartao-cvv" placeholder="•••" maxlength="3" inputmode="numeric" autocomplete="cc-csc">
              </div>
            </div>
          </div>

          <!-- Dinheiro -->
          <div class="tab-pane fade" id="tab-dinheiro" role="tabpanel">
            <div class="text-center py-3">
              <i class="bi bi-cash-stack text-success" style="font-size:3rem"></i>
              <p class="mt-2 mb-1 fw-semibold">Pagamento em dinheiro</p>
              <p class="small text-muted">O prestador registrará a confirmação após o recebimento presencial.</p>
            </div>
          </div>

        </div>

        <div class="px-4 pb-4 pt-2">
          <button type="submit" class="btn btn-warning fw-semibold w-100" id="btn-confirmar-pag">
            Confirmar pagamento
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- Modal Avaliação -->
<div class="modal fade" id="modalAvaliacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content overflow-hidden">
      <!-- Header gradiente -->
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

          <!-- Estrelas grandes centralizadas -->
          <div class="mb-4 text-center">
            <div class="mb-2" style="font-size:.85rem;font-weight:600;color:#6b7280;">Como você avalia o atendimento?</div>
            <div class="fn-stars-wrap d-flex flex-column align-items-center gap-2">
              <div class="fn-stars" data-fn-stars style="font-size:2.2rem;"></div>
              <span class="fn-star-legend badge px-3 py-2" data-fn-stars-legend
                    style="background:#0d1b3d;color:#ffc107;font-size:.85rem;font-weight:700;border-radius:50px;">Excelente</span>
            </div>
          </div>

          <!-- Comentário -->
          <div class="mb-1">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Comentário <span class="text-muted fw-normal">(opcional)</span></label>
            <textarea name="comentario_avaliacao" class="form-control" rows="3" maxlength="255"
              placeholder="Conte como foi o atendimento, o que gostou ou o que poderia melhorar..."></textarea>
            <div class="form-text">Sua avaliação ajuda outros clientes a escolher o prestador ideal.</div>
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
<div class="modal fade" id="modalReagendarChamado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reagendar serviço</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" class="js-guard-submit" id="form-reagendar">
        <div class="modal-body">
          <input type="hidden" name="reagendar_chamado_id" id="reagendar-chamado-id">
          <input type="hidden" name="nova_data_agendamento" id="reagendar-nova-data-hidden">

          <!-- Com prestador: select com optgroups por data -->
          <div id="reagendar-slots-box" class="mb-3 d-none">
            <label class="form-label">Horário disponível <span class="text-danger">*</span></label>
            <div id="reagendar-slots-loading" class="text-muted small">Buscando horários...</div>
            <select id="reagendar-select-slot" class="form-select d-none" required>
              <option value="">Selecione um horário...</option>
            </select>
          </div>

          <div id="reagendar-slots-aviso" class="text-warning small d-none mb-2"></div>

          <!-- Sem prestador ou sem agenda: datetime-local livre -->
          <div id="reagendar-hora-livre" class="mb-3 d-none">
            <label class="form-label">Data e hora desejada <span class="text-danger">*</span></label>
            <input type="datetime-local" id="reagendar-datetime" class="form-control"
                   min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="reagendar-btn-confirmar" class="btn btn-warning fw-semibold" disabled>Confirmar reagendamento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Alterar Chamado -->
<div class="modal fade" id="modalAlterarChamado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Alterar agendamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" class="js-guard-submit">
        <div class="modal-body">
          <input type="hidden" name="alterar_chamado_id" id="alterar-chamado-id">
          <div class="mb-3">
            <label class="form-label">Descrição do problema <span class="text-danger">*</span></label>
            <textarea name="nova_descricao" id="alterar-descricao" class="form-control" rows="3" required maxlength="500"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Endereço do serviço <span class="text-danger">*</span></label>
            <input type="text" name="novo_endereco" id="alterar-endereco" class="form-control" required maxlength="200">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning fw-semibold">Salvar alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Recusar Orçamento -->
<div class="modal fade" id="modalRecusarOrcamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Recusar orçamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" class="js-guard-submit">
        <div class="modal-body">
          <input type="hidden" name="recusar_orcamento_id" id="recusar-orcamento-id">
          <p class="small text-muted mb-3" id="recusar-orcamento-resumo"></p>
          <div class="mb-0">
            <label class="form-label">Motivo da recusa <span class="text-muted small">(opcional)</span></label>
            <textarea name="motivo_recusa" class="form-control" rows="3" maxlength="500"
              placeholder="Ex: valor acima do esperado, não preciso mais do serviço..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger fw-semibold">Confirmar recusa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="toastPagamento" class="toast" role="alert">
    <div class="toast-body bg-dark text-white">Ação</div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="../assets/js/pagamento-dashboard.js"></script>
<script src="../assets/js/avaliacao-dashboard.js"></script>
<script src="../assets/js/cpf-validation-reload.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var modalAlterar = document.getElementById('modalAlterarChamado');
  if (modalAlterar) {
    modalAlterar.addEventListener('show.bs.modal', function (e) {
      var btn = e.relatedTarget;
      document.getElementById('alterar-chamado-id').value = btn.dataset.chamadoId || '';
      document.getElementById('alterar-descricao').value  = btn.dataset.descricao  || '';
      document.getElementById('alterar-endereco').value   = btn.dataset.endereco   || '';
    });
  }
  var modalReagendar = document.getElementById('modalReagendarChamado');
  if (modalReagendar) {
    var _reagTecnicoId = 0;
    var _reagChamadoId = 0;
    var _nomeDias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

    function fmtDia(dataStr) {
      var partes = dataStr.split('-');
      var d = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
      var dd = String(d.getDate()).padStart(2, '0');
      var mm = String(d.getMonth() + 1).padStart(2, '0');
      return _nomeDias[d.getDay()] + ', ' + dd + '/' + mm;
    }

    modalReagendar.addEventListener('show.bs.modal', function (e) {
      var btn = e.relatedTarget;
      _reagChamadoId = parseInt(btn.dataset.chamadoId || '0');
      _reagTecnicoId = parseInt(btn.dataset.tecnicoId || '0');

      document.getElementById('reagendar-chamado-id').value = _reagChamadoId;
      document.getElementById('reagendar-nova-data-hidden').value = '';
      document.getElementById('reagendar-btn-confirmar').disabled = true;

      var slotsBox   = document.getElementById('reagendar-slots-box');
      var loading    = document.getElementById('reagendar-slots-loading');
      var aviso      = document.getElementById('reagendar-slots-aviso');
      var selectEl   = document.getElementById('reagendar-select-slot');
      var horaLivre  = document.getElementById('reagendar-hora-livre');
      var dtInput    = document.getElementById('reagendar-datetime');

      aviso.classList.add('d-none');
      selectEl.classList.add('d-none');
      selectEl.innerHTML = '<option value="">Selecione um horário...</option>';

      if (_reagTecnicoId > 0) {
        slotsBox.classList.remove('d-none');
        horaLivre.classList.add('d-none');
        loading.classList.remove('d-none');

        fetch('../api/slots_prestador.php?tecnico_id=' + _reagTecnicoId + '&chamado_id=' + _reagChamadoId + '&dias=14')
          .then(function(r){ return r.json(); })
          .then(function(res) {
            loading.classList.add('d-none');
            var porData = res.slots_por_data || {};
            var datas = Object.keys(porData);

            if (res.aviso || !datas.length) {
              // Prestador sem agenda cadastrada: libera escolha livre com aviso
              aviso.textContent = (res.aviso || 'Prestador sem horários cadastrados.') + ' Escolha um horário de sua preferência.';
              aviso.classList.remove('d-none');
              slotsBox.classList.add('d-none');
              horaLivre.classList.remove('d-none');
              dtInput.value = '';
              return;
            }

            datas.forEach(function(dia) {
              var group = document.createElement('optgroup');
              group.label = fmtDia(dia);
              (porData[dia] || []).forEach(function(hora) {
                var opt = document.createElement('option');
                opt.value = dia + '|' + hora;
                opt.textContent = fmtDia(dia) + ' às ' + hora;
                group.appendChild(opt);
              });
              selectEl.appendChild(group);
            });
            selectEl.classList.remove('d-none');
          })
          .catch(function() {
            loading.classList.add('d-none');
            // Em caso de erro de rede, libera escolha livre
            aviso.textContent = 'Não foi possível buscar a agenda do prestador. Escolha um horário de sua preferência.';
            aviso.classList.remove('d-none');
            slotsBox.classList.add('d-none');
            horaLivre.classList.remove('d-none');
            dtInput.value = '';
          });
      } else {
        slotsBox.classList.add('d-none');
        horaLivre.classList.remove('d-none');
        dtInput.value = '';
      }
    });

    document.getElementById('reagendar-select-slot').addEventListener('change', function() {
      var val = this.value;
      document.getElementById('reagendar-nova-data-hidden').value = '';
      document.getElementById('reagendar-btn-confirmar').disabled = true;
      if (val) {
        var partes = val.split('|');
        document.getElementById('reagendar-nova-data-hidden').value = partes[0] + ' ' + partes[1] + ':00';
        document.getElementById('reagendar-btn-confirmar').disabled = false;
      }
    });

    document.getElementById('reagendar-datetime').addEventListener('change', function() {
      var val = this.value;
      document.getElementById('reagendar-nova-data-hidden').value = val ? val.replace('T', ' ') + ':00' : '';
      document.getElementById('reagendar-btn-confirmar').disabled = !val;
    });

    document.getElementById('form-reagendar').addEventListener('submit', function(e) {
      var val = document.getElementById('reagendar-nova-data-hidden').value;
      if (!val) { e.preventDefault(); alert('Selecione uma data e horário.'); }
    });
  }
  var modalRecusar = document.getElementById('modalRecusarOrcamento');
  if (modalRecusar) {
    modalRecusar.addEventListener('show.bs.modal', function (e) {
      var btn = e.relatedTarget;
      document.getElementById('recusar-orcamento-id').value = btn.dataset.orcamentoId || '';
      var resumo = document.getElementById('recusar-orcamento-resumo');
      if (resumo) resumo.textContent = 'Recusar orçamento de: ' + (btn.dataset.tecnicoNome || '') + '. O prestador receberá uma notificação com o motivo.';
    });
  }
});
</script>
<button id="fn-dark-toggle" title="Alternar modo escuro" aria-label="Alternar modo escuro">🌙</button>
<script>
(function () {
  var resultado = document.getElementById('svc-resultado');
  if (!resultado) return;

  resultado.addEventListener('click', function (e) {
    var link = e.target.closest('a[href*="dashboardCliente"]');
    if (!link) return;
    e.preventDefault();

    var href = link.getAttribute('href');
    var ajaxUrl = href + (href.includes('?') ? '&' : '?') + 'ajax=1';

    resultado.style.opacity = '0.5';
    resultado.style.pointerEvents = 'none';

    fetch(ajaxUrl)
      .then(function (r) { return r.text(); })
      .then(function (html) {
        resultado.innerHTML = html;
        resultado.style.opacity = '';
        resultado.style.pointerEvents = '';
        history.pushState(null, '', href + '#encontrar-prestador');
        document.getElementById('encontrar-prestador')
          .scrollIntoView({ behavior: 'smooth', block: 'start' });
      })
      .catch(function () {
        window.location = link.href;
      });
  });
})();
</script>
</body>
</html>
