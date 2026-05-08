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
$filtroCategoria     = $ctrl->filtroCategoria;
$mensagem            = $ctrl->mensagem;
$erro                = $ctrl->erro;
$naoLidas            = $ctrl->naoLidas;
$clienteNome         = $_SESSION['cliente_nome'] ?? 'Cliente';
$clienteFoto         = $_SESSION['cliente_foto'] ?? '';

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
</head>
<body data-live-update-interval="9000">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <span class="fn-user-badge me-2">
      <?php if ($clienteFoto): ?>
        <img src="../<?php echo htmlspecialchars($clienteFoto); ?>" alt="Foto" width="44" height="44">
      <?php else: ?>
        <span class="fallback"><?php echo htmlspecialchars(mb_substr($clienteNome, 0, 1)); ?></span>
      <?php endif; ?>
      <span><?php echo htmlspecialchars($clienteNome); ?></span>
    </span>
    <a class="navbar-brand fw-bold" href="../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="../index.php">Início</a></li>
        <li class="nav-item"><a class="nav-link active" href="dashboardCliente.php">Dashboard</a></li>
        <li class="nav-item">
          <a class="nav-link" href="notificacoes.php">
            Notificações<?php if ($naoLidas > 0): ?><span class="badge bg-danger ms-1"><?php echo $naoLidas; ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="perfil.php">Perfil</a></li>
        <li class="nav-item"><a class="nav-link" href="rastreamento.php">Rastreamento</a></li>
        <li class="nav-item"><a class="nav-link" href="suporte.php">Suporte</a></li>
        <li class="nav-item"><a class="nav-link" href="../logout.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <section class="fn-hero p-4 p-lg-5 mb-4">
    <div class="row align-items-center g-3">
      <div class="col-lg-8">
        <h1 class="h3 mb-2">Bem-vindo(a) ao Fix Now</h1>
        <p class="mb-0 text-white-50">Conectamos você aos melhores técnicos com agilidade, confiança e atendimento humanizado.</p>
      </div>
      <div class="col-lg-4 text-lg-end">
        <a href="#encontrar-prestador" class="btn btn-light btn-lg px-4">Encontrar prestador</a>
      </div>
    </div>
  </section>

  <section class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card fn-stat-card h-100"><div class="card-body"><p class="text-muted mb-1">Serviços solicitados</p><h3 class="mb-0"><?php echo (int)$stats['total_chamados']; ?></h3></div></div>
    </div>
    <div class="col-md-6">
      <div class="card fn-stat-card h-100"><div class="card-body"><p class="text-muted mb-1">Serviços concluídos</p><h3 class="mb-0"><?php echo (int)$stats['concluidos']; ?></h3></div></div>
    </div>
  </section>

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

  <section id="encontrar-prestador" class="mb-5">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h2 class="mb-0">Encontre um prestador</h2>
        <p class="text-muted mb-0 small">Escolha um prestador e veja os horários disponíveis para agendar.</p>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a href="dashboardCliente.php#encontrar-prestador"
         class="btn btn-sm <?php echo $filtroCategoria === '' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Todos</a>
      <?php foreach ($categorias as $cat): ?>
        <a href="dashboardCliente.php?categoria=<?php echo urlencode($cat['nome']); ?>#encontrar-prestador"
           class="btn btn-sm <?php echo $filtroCategoria === $cat['nome'] ? 'btn-primary' : 'btn-outline-secondary'; ?>">
          <?php echo htmlspecialchars($cat['nome']); ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (!$servicos): ?>
      <div class="alert alert-info">Nenhum prestador encontrado<?php echo $filtroCategoria ? ' para esta categoria' : ''; ?>.</div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($servicos as $s): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 <?php echo $s['destaque'] ? 'shadow' : 'shadow-sm'; ?>"
                 style="<?php echo $s['destaque'] ? 'border: 2px solid #ffc107 !important; box-shadow: 0 8px 24px rgba(255,122,0,.15) !important;' : ''; ?>">
              <div class="card-body d-flex flex-column">

                <?php if ($s['destaque']): ?>
                  <span class="badge bg-warning text-dark mb-2" style="width:fit-content;">★ Destaque Fix Now</span>
                <?php endif; ?>

                <!-- Cabeçalho: foto + nome + avaliação -->
                <div class="d-flex align-items-center gap-3 mb-3">
                  <?php if ($s['foto_perfil']): ?>
                    <img src="../<?php echo htmlspecialchars($s['foto_perfil']); ?>" alt="Foto"
                         width="52" height="52" class="rounded-circle object-fit-cover flex-shrink-0">
                  <?php else: ?>
                    <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                          style="width:52px;height:52px;font-size:1.1rem;">
                      <?php echo htmlspecialchars(mb_substr($s['tecnico_nome'], 0, 1)); ?>
                    </span>
                  <?php endif; ?>
                  <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($s['tecnico_nome']); ?></div>
                    <?php if ($s['media_nota'] > 0): ?>
                      <div class="small text-warning">★ <?php echo number_format((float)$s['media_nota'], 1); ?></div>
                    <?php else: ?>
                      <div class="small text-muted">Sem avaliações</div>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Badges de serviços/categorias -->
                <div class="d-flex flex-wrap gap-1 mb-3">
                  <?php foreach ($s['servicos'] as $sv): ?>
                    <span class="badge bg-light text-secondary border">
                      <?php echo htmlspecialchars($sv['categoria_nome'] ?: $sv['nome']); ?>
                    </span>
                  <?php endforeach; ?>
                </div>

                <!-- Preço -->
                <div class="mt-auto">
                  <div class="mb-3">
                    <?php if ($s['preco_min'] === $s['preco_max']): ?>
                      <span class="fw-bold text-primary fs-5">R$ <?php echo number_format((float)$s['preco_min'], 2, ',', '.'); ?></span>
                    <?php else: ?>
                      <span class="fw-bold text-primary fs-5">
                        R$ <?php echo number_format((float)$s['preco_min'], 2, ',', '.'); ?>
                        <span class="fs-6 fw-normal text-muted">– R$ <?php echo number_format((float)$s['preco_max'], 2, ',', '.'); ?></span>
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex gap-2">
                    <a href="portfolioPublico.php?id=<?php echo (int)$s['tecnico_id']; ?>"
                       class="btn btn-sm btn-outline-secondary flex-fill">Portfólio</a>
                    <a href="cliente/solicitar.php?prestador=<?php echo (int)$s['tecnico_id']; ?>"
                       class="btn btn-sm btn-warning fw-semibold flex-fill">Solicitar</a>
                  </div>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php
    // Chamados que já aparecem na seção de orçamentos: não duplicar na tabela abaixo
    $chamadosComOrcamentoPendente = array_flip(array_column($orcamentosPendentes, 'chamado_id'));
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Meus chamados</h2>
  </div>

  <?php if ($mensagem): ?>
    <?php if (isset($_GET['avaliacao_ok'])): ?>
      <div class="alert alert-success js-flash-reload" data-reload-ms="2200"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php else: ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <?php if ($notificacoes): ?>
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="h5 mb-0">Notificações <span class="badge bg-danger"><?php echo count($notificacoes); ?></span></h3>
          <div class="d-flex gap-2">
            <form method="post" action="notificacoes.php">
              <input type="hidden" name="acao" value="marcar_todas_lidas">
              <button class="btn btn-sm btn-outline-secondary">Marcar todas como lidas</button>
            </form>
            <a href="notificacoes.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
          </div>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($notificacoes as $n): ?>
            <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-2">
              <div>
                <span class="badge bg-primary me-1">Nova</span>
                <span class="small"><?php echo htmlspecialchars($n['mensagem']); ?></span>
                <?php if (!empty($n['chamado_id'])): ?>
                  <div class="small text-muted">Chamado #<?php echo (int)$n['chamado_id']; ?> &middot; <?php echo date('d/m H:i', strtotime($n['criado_em'])); ?></div>
                <?php endif; ?>
              </div>
              <a class="btn btn-sm btn-outline-secondary flex-shrink-0" href="dashboardCliente.php?lida=<?php echo (int)$n['id']; ?>">Marcar lida</a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$chamados): ?>
    <div class="alert alert-info">Você ainda não possui chamados abertos.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>#</th><th>Categoria</th><th>Descrição</th><th>Técnico</th>
            <th>Preço</th><th>Pagamento</th><th>Status</th><th>Agendado</th><th>Data</th><th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($chamados as $c): ?>
          <?php if (isset($chamadosComOrcamentoPendente[$c->id])) continue; ?>
          <?php
            $badge = 'secondary';
            if ($c->status === 'Pendente')     $badge = 'warning text-dark';
            if ($c->status === 'Em Andamento') $badge = 'primary';
            if ($c->status === 'Concluído')    $badge = 'success';
            if ($c->status === 'Negado')       $badge = 'dark';

            $podePagar    = $c->status === 'Concluído' && $c->pagamentoId && $c->pagStatus === 'Pendente';
            $jaAvaliado   = $c->avaliacaoNota !== null;
            $podeAvaliar  = $c->status === 'Concluído' && !empty($c->tecnicoNome) && !$jaAvaliado;
            $reagPendente = $c->reagendamentoPendente;
            $podeReagendar = !empty($c->tecnicoId) && in_array($c->status, ['Pendente','Em Andamento']) && !$reagPendente;
            $valorFmt = 'R$ ' . number_format((float)($c->pagValor ?? $c->precoSugerido), 2, ',', '.');
          ?>
          <tr>
            <td><?php echo $c->id; ?></td>
            <td><?php echo htmlspecialchars($c->categoria); ?></td>
            <td><?php echo htmlspecialchars(mb_strimwidth($c->descricao, 0, 55, '...')); ?></td>
            <td><?php echo htmlspecialchars($c->tecnicoNome ?? 'A definir'); ?></td>
            <td>
              <?php if ($c->precoSugerido > 0): ?>
                R$ <?php echo number_format($c->precoSugerido, 2, ',', '.'); ?>
              <?php else: ?>
                <span class="text-muted">A definir</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$c->pagamentoId): ?>
                <span class="text-muted">—</span>
              <?php elseif ($c->pagStatus === 'Pago'): ?>
                <span class="badge bg-success">Pago</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($c->pagStatus); ?></span>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($c->status); ?></span></td>
            <td>
              <?php if ($reagPendente && $c->dataAgendamentoProposta): ?>
                <span class="text-warning fw-semibold" title="Aguardando prestador confirmar">
                  <?php echo date('d/m/Y H:i', strtotime($c->dataAgendamentoProposta)); ?>
                  <br><small class="text-muted">Aguardando confirmação</small>
                </span>
              <?php elseif ($c->dataAgendamento): ?>
                <?php echo date('d/m/Y H:i', strtotime($c->dataAgendamento)); ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td><?php echo date('d/m/Y H:i', strtotime($c->criadoEm)); ?></td>
            <td class="d-flex flex-wrap gap-1">
              <a class="btn btn-sm btn-outline-primary" href="rastreamento.php?chamado=<?php echo $c->id; ?>">Rastrear</a>
              <?php if ($c->status === 'Pendente' && empty($c->tecnicoId)): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                  data-bs-toggle="modal" data-bs-target="#modalAlterarChamado"
                  data-chamado-id="<?php echo $c->id; ?>"
                  data-descricao="<?php echo htmlspecialchars($c->descricao, ENT_QUOTES); ?>"
                  data-endereco="<?php echo htmlspecialchars($c->enderecoServico, ENT_QUOTES); ?>">Alterar</button>
                <form method="post" class="d-inline js-guard-submit" onsubmit="return confirm('Cancelar este agendamento?');">
                  <input type="hidden" name="cancelar_chamado_id" value="<?php echo $c->id; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                </form>
              <?php endif; ?>
              <?php if ($podeReagendar): ?>
                <button type="button" class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="modal" data-bs-target="#modalReagendarChamado"
                  data-chamado-id="<?php echo $c->id; ?>"
                  data-data-atual="<?php echo htmlspecialchars($c->dataAgendamento ?? ''); ?>">Reagendar</button>
              <?php elseif ($reagPendente): ?>
                <span class="badge bg-warning text-dark">Reagendamento pendente</span>
              <?php endif; ?>
              <?php if ($podePagar): ?>
                <button type="button" class="btn btn-sm btn-success"
                  data-bs-open-pagamento
                  data-pagamento-id="<?php echo $c->pagamentoId; ?>"
                  data-chamado-id="<?php echo $c->id; ?>"
                  data-valor="<?php echo htmlspecialchars($valorFmt); ?>">Pagar</button>
              <?php endif; ?>
              <?php if ($podeAvaliar): ?>
                <button type="button" class="btn btn-sm btn-outline-warning"
                  data-bs-open-avaliacao
                  data-chamado-id="<?php echo $c->id; ?>"
                  data-tecnico-nome="<?php echo htmlspecialchars($c->tecnicoNome ?? ''); ?>">Avaliar prestador</button>
              <?php elseif ($jaAvaliado): ?>
                <span class="badge bg-warning text-dark">Avaliado: <?php echo $c->avaliacaoNota; ?>/5</span>
              <?php endif; ?>
              <a href="chat.php?chamado=<?php echo $c->id; ?>" class="btn btn-sm btn-outline-primary">💬 Chat</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <section class="row g-3 mt-2">
    <?php if ($depoimento): ?>
    <div class="col-md-6">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex flex-column gap-2">
          <div class="d-flex align-items-center gap-2"><span class="fs-5">💬</span><h3 class="h6 mb-0">O que dizem sobre a Fix Now</h3></div>
          <p class="text-muted mb-1 fst-italic">"<?php echo htmlspecialchars($depoimento['comentario']); ?>"</p>
          <div class="mt-auto d-flex align-items-center gap-2">
            <span class="text-warning" style="font-size:.85rem;"><?php echo str_repeat('★', (int)$depoimento['nota']); ?></span>
            <small class="text-muted">— <?php echo htmlspecialchars(mb_substr($depoimento['cliente_nome'], 0, 1) . str_repeat('*', max(0, mb_strlen($depoimento['cliente_nome']) - 2)) . mb_substr($depoimento['cliente_nome'], -1)); ?>, cliente Fix Now</small>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div class="col-md-<?php echo $depoimento ? '6' : '12'; ?>">
      <div class="card h-100 border-0 shadow-sm" style="border-left: 3px solid #ffc107 !important;">
        <div class="card-body d-flex flex-column gap-2">
          <div class="d-flex align-items-center gap-2"><span class="fs-5">💡</span><h3 class="h6 mb-0">Dica para você</h3></div>
          <p class="text-muted mb-0"><?php echo htmlspecialchars($dica); ?></p>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Modal Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pagamento simulado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted" data-pagamento-resumo></p>
        <form method="post" id="form-confirmar-pagamento" class="js-guard-submit">
          <input type="hidden" name="confirmar_pagamento_id" value="">
          <div class="mb-3">
            <label class="form-label">Método</label>
            <select name="metodo_pagamento" class="form-select">
              <option value="PIX">PIX</option>
              <option value="Cartão">Cartão</option>
              <option value="Dinheiro">Dinheiro</option>
            </select>
          </div>
        </form>
        <div class="text-center mb-3 p-3 bg-light rounded border" style="min-height:140px;">
          <div class="small text-muted mb-2">QR Code (demonstração)</div>
          <div style="width:120px;height:120px;margin:0 auto;background:repeating-linear-gradient(45deg,#222 0 4px,#fff 4px 8px);border-radius:8px;" role="img" aria-label="QR Code simulado"></div>
        </div>
        <label class="form-label">Código copia e cola (simulado)</label>
        <textarea class="form-control font-monospace small" id="pix-copia-cola" rows="3" readonly></textarea>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btn-copiar-pix">Copiar código</button>
      </div>
      <div class="modal-footer flex-column align-items-stretch gap-2">
        <button type="submit" class="btn btn-warning fw-semibold" form="form-confirmar-pagamento">Confirmar pagamento (demo)</button>
        <small class="text-muted">Em produção, integre o gateway (Mercado Pago, Stripe, etc.).</small>
      </div>
    </div>
  </div>
</div>

<!-- Modal Avaliação -->
<div class="modal fade" id="modalAvaliacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Avaliar atendimento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3" data-avaliacao-resumo></p>
        <form method="post" id="form-avaliacao" class="js-guard-submit">
          <input type="hidden" name="avaliar_chamado_id" value="">
          <input type="hidden" name="nota_avaliacao" value="5">
          <div class="mb-3">
            <label class="form-label d-block">Nota</label>
            <div class="fn-stars-wrap">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="fn-stars" data-fn-stars></div>
                <span class="fn-star-legend" data-fn-stars-legend>Excelente</span>
              </div>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label">Comentário (opcional)</label>
            <textarea name="comentario_avaliacao" class="form-control" rows="3" maxlength="255" placeholder="Conte como foi o atendimento."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning fw-semibold" form="form-avaliacao">Enviar avaliação</button>
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
      <form method="post" class="js-guard-submit">
        <div class="modal-body">
          <input type="hidden" name="reagendar_chamado_id" id="reagendar-chamado-id">
          <div class="mb-3">
            <label class="form-label">Nova data e hora <span class="text-danger">*</span></label>
            <input type="datetime-local" name="nova_data_agendamento" id="reagendar-nova-data" class="form-control" required
                   min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning fw-semibold">Confirmar reagendamento</button>
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

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?php echo date('Y'); ?> Fix Now.</small></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js"></script>
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
    modalReagendar.addEventListener('show.bs.modal', function (e) {
      var btn = e.relatedTarget;
      document.getElementById('reagendar-chamado-id').value = btn.dataset.chamadoId || '';
      var dataAtual = btn.dataset.dataAtual || '';
      document.getElementById('reagendar-nova-data').value = dataAtual ? dataAtual.slice(0, 16) : '';
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
</body>
</html>
