<?php
session_start();
require_once __DIR__ . '/../../controller/DashboardPrestadorControl.php';

$ctrl = new DashboardPrestadorControl();
$ctrl->processar();

$tecnicoNome       = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto       = $_SESSION['tecnico_foto'] ?? '';
$primeiroNome      = explode(' ', trim($tecnicoNome))[0];
$stats              = $ctrl->stats;
$pendentesCount     = $ctrl->pendentesCount;
$chamadosDisp       = $ctrl->chamadosDisponiveis;
$diretos            = $ctrl->solicitacoesDiretas;
$chamadosAguardando = $ctrl->chamadosAguardando;
$meusChamados       = $ctrl->emAndamento;
$historico          = $ctrl->historico;
$notificacoes       = $ctrl->notificacoes;
$mensagem           = $ctrl->mensagem;
$erro               = $ctrl->erro;
$isDestaque         = $ctrl->isDestaque;
$categoriasServico  = $ctrl->categoriasServico;
$naoLidas           = count(array_filter($notificacoes, fn($n) => !$n->lida));
$paginaAtiva       = 'chamados';

$catIcons = [
    'Suporte TI' => '💻',
    'Elétrica'   => '⚡',
    'Hidráulica' => '🔧',
    'Pintura'    => '🎨',
    'Marcenaria' => '🪚',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meus chamados - Prestador - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    /* ── Hero ── */
    .prest-hero {
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 55%, #c95e00 100%);
      border-radius: 16px;
      padding: 2rem 2rem 1.8rem;
      position: relative;
      overflow: hidden;
      margin-bottom: 1.5rem;
    }
    .prest-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .prest-hero-foto {
      width: 60px; height: 60px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid rgba(255,255,255,.4);
      flex-shrink: 0;
    }
    .prest-hero-fb {
      width: 60px; height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ffc107, #ff7a00);
      color: #0d1b3d;
      font-size: 1.5rem;
      font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      border: 3px solid rgba(255,255,255,.3);
      flex-shrink: 0;
    }
    .prest-hero h1 {
      font-size: clamp(1.15rem, 3vw, 1.55rem);
      font-weight: 800;
      color: #fff;
      margin-bottom: .2rem;
    }
    .prest-hero p { color: rgba(255,255,255,.72); font-size: .9rem; margin: 0; }
    .badge-destaque-hero {
      background: linear-gradient(135deg, #ffc107, #ff7a00);
      color: #0d1b3d;
      font-size: .72rem;
      font-weight: 800;
      padding: .25rem .7rem;
      border-radius: 50px;
    }
    .badge-cat-hero {
      background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.25);
      color: #ffe8bb;
      font-size: .75rem;
      font-weight: 600;
      padding: .25rem .65rem;
      border-radius: 50px;
      backdrop-filter: blur(4px);
    }

    /* ── Stat cards ── */
    .stat-card-prest {
      border-radius: 14px;
      border: 1.5px solid #e8ecf3;
      padding: 1.1rem 1.3rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      background: #fff;
      box-shadow: 0 3px 12px rgba(13,27,61,.06);
      height: 100%;
    }
    .stat-icon-prest {
      width: 48px; height: 48px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }
    .stat-card-prest .val { font-size: 1.8rem; font-weight: 800; line-height: 1; color: #0d1b3d; }
    .stat-card-prest .lbl { font-size: .8rem; color: #667085; margin-top: .2rem; }
    .stat-card-prest .sub { font-size: .75rem; font-weight: 600; margin-top: .2rem; }

    /* ── Banner urgente ── */
    .banner-orcamento {
      background: linear-gradient(90deg, #fff3cd, #ffe69c);
      border: 1.5px solid #ffc107;
      border-radius: 12px;
      padding: .9rem 1.2rem;
      display: flex;
      align-items: center;
      gap: .8rem;
      margin-bottom: 1.25rem;
    }
    .banner-orcamento .icone { font-size: 1.4rem; flex-shrink: 0; }

    /* ── Títulos de seção ── */
    .secao-titulo {
      display: flex;
      align-items: center;
      gap: .6rem;
      font-size: 1rem;
      font-weight: 700;
      color: #0d1b3d;
      margin-bottom: 1rem;
      padding-bottom: .6rem;
      border-bottom: 2px solid #f0f3fa;
    }
    .secao-titulo .count-badge {
      background: #0d1b3d;
      color: #ffc107;
      font-size: .72rem;
      font-weight: 800;
      padding: .18rem .55rem;
      border-radius: 50px;
    }

    /* ── Cards de chamado pendente ── */
    .card-chamado {
      border-radius: 14px;
      border: 1.5px solid #e8ecf3;
      background: #fff;
      box-shadow: 0 3px 12px rgba(13,27,61,.06);
      overflow: hidden;
      transition: transform .18s, box-shadow .18s;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .card-chamado:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(13,27,61,.12); }
    .card-chamado-strip {
      height: 4px;
      flex-shrink: 0;
    }
    .strip-direta  { background: linear-gradient(90deg, #ff7a00, #ffc107); }
    .strip-fila    { background: linear-gradient(90deg, #1a2b63, #0d6efd); }
    .strip-aguard  { background: linear-gradient(90deg, #ffc107, #fd7e14); }
    .strip-andando { background: linear-gradient(90deg, #0d9488, #10b981); }

    .card-chamado-body {
      padding: 1rem 1.1rem;
      flex: 1;
    }
    .cliente-mini {
      display: flex;
      align-items: center;
      gap: .6rem;
      margin-bottom: .7rem;
    }
    .cliente-mini .foto {
      width: 36px; height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #e8ecf3;
      flex-shrink: 0;
    }
    .cliente-mini .fb {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0d1b3d, #1a2b63);
      color: #ffc107;
      font-size: .85rem;
      font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .cliente-mini .nome { font-weight: 700; font-size: .88rem; color: #0d1b3d; line-height: 1.2; }
    .cliente-mini .cat  { font-size: .74rem; color: #667085; }

    .desc-chamado {
      font-size: .83rem;
      color: #374151;
      line-height: 1.5;
      margin-bottom: .6rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .info-chip {
      display: inline-flex;
      align-items: center;
      gap: .3rem;
      font-size: .75rem;
      color: #667085;
      background: #f8f9fc;
      border: 1px solid #e8ecf3;
      border-radius: 6px;
      padding: .18rem .5rem;
      margin: .15rem .15rem .15rem 0;
    }

    .card-chamado-footer {
      padding: .75rem 1.1rem;
      border-top: 1px solid #f0f3fa;
      display: flex;
      flex-wrap: wrap;
      gap: .4rem;
      align-items: center;
    }

    .badge-direta {
      font-size: .68rem;
      font-weight: 700;
      background: linear-gradient(135deg, #ff7a00, #ffc107);
      color: #0d1b3d;
      padding: .2rem .55rem;
      border-radius: 50px;
    }
    .badge-fila {
      font-size: .68rem;
      font-weight: 700;
      background: #e8ecf3;
      color: #374151;
      padding: .2rem .55rem;
      border-radius: 50px;
    }

    /* ── Reagendamento strip ── */
    .reagend-strip {
      background: #fff3cd;
      border: 1px solid #ffc107;
      border-radius: 8px;
      padding: .5rem .75rem;
      font-size: .8rem;
      margin-bottom: .5rem;
    }

    /* ── Histórico table ── */
    .hist-table td, .hist-table th { padding: .55rem .75rem; font-size: .84rem; }

    /* ════ DARK MODE ════ */
    [data-theme="dark"] .stat-card-prest      { background: #1e2538; border-color: #2e3650; }
    [data-theme="dark"] .stat-card-prest .val { color: #e4e8f4; }
    [data-theme="dark"] .stat-card-prest .lbl { color: #8090b0; }
    [data-theme="dark"] .banner-orcamento     { background: #2a2010; border-color: #ffc107; }
    [data-theme="dark"] .banner-orcamento *   { color: #ffe8bb !important; }
    [data-theme="dark"] .secao-titulo         { color: #e4e8f4; border-bottom-color: #2e3650; }
    [data-theme="dark"] .card-chamado         { background: #1e2538; border-color: #2e3650; }
    [data-theme="dark"] .card-chamado:hover   { box-shadow: 0 10px 24px rgba(0,0,0,.35); }
    [data-theme="dark"] .card-chamado-footer  { border-top-color: #2e3650; }
    [data-theme="dark"] .cliente-mini .nome   { color: #e4e8f4; }
    [data-theme="dark"] .cliente-mini .cat    { color: #8090b0; }
    [data-theme="dark"] .cliente-mini .foto   { border-color: #2e3650; }
    [data-theme="dark"] .desc-chamado         { color: #c8d0e0; }
    [data-theme="dark"] .info-chip            { background: #252d42; border-color: #2e3650; color: #8090b0; }
    [data-theme="dark"] .reagend-strip        { background: #2a2010; border-color: #ffc107; color: #ffe8bb; }
    [data-theme="dark"] .badge-fila           { background: #2e3650; color: #c8d0e0; }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-4 mt-5">

  <?php if ($mensagem): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      ✅ <?php echo htmlspecialchars($mensagem); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      ⚠️ <?php echo htmlspecialchars($erro); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Hero personalizado -->
  <div class="prest-hero">
    <div class="row g-3 align-items-center" style="position:relative;z-index:1;">
      <div class="col">
        <div class="d-flex align-items-center gap-3 mb-2">
          <?php if ($tecnicoFoto): ?>
            <img src="../../<?php echo htmlspecialchars($tecnicoFoto); ?>" class="prest-hero-foto" alt="">
          <?php else: ?>
            <div class="prest-hero-fb"><?php echo mb_strtoupper(mb_substr($tecnicoNome, 0, 1)); ?></div>
          <?php endif; ?>
          <div>
            <h1>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋</h1>
            <p>Aqui estão suas oportunidades e chamados ativos de hoje.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-1">
          <?php if ($isDestaque): ?>
            <span class="badge-destaque-hero">⭐ Prestador em Destaque</span>
          <?php endif; ?>
          <?php foreach ($categoriasServico as $cat): ?>
            <span class="badge-cat-hero"><?php echo ($catIcons[$cat] ?? '🔩') . ' ' . htmlspecialchars($cat); ?></span>
          <?php endforeach; ?>
          <?php if (!$categoriasServico): ?>
            <a href="servicos.php" class="badge-cat-hero text-decoration-none">⚠️ Cadastre seus serviços</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="stat-card-prest">
        <div class="stat-icon-prest" style="background:#eff6ff;">📋</div>
        <div>
          <div class="val"><?php echo $pendentesCount; ?></div>
          <div class="lbl">Chamados disponíveis</div>
          <?php if ($chamadosAguardando): ?>
            <div class="sub" style="color:#f59e0b;"><?php echo count($chamadosAguardando); ?> aguardando orçamento</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="stat-card-prest">
        <div class="stat-icon-prest" style="background:#fff7ed;">🔧</div>
        <div>
          <div class="val" style="color:#ea580c;"><?php echo (int)($stats['em_andamento'] ?? 0); ?></div>
          <div class="lbl">Em andamento</div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="stat-card-prest">
        <div class="stat-icon-prest" style="background:#f0fdf4;">✅</div>
        <div>
          <div class="val" style="color:#16a34a;"><?php echo (int)($stats['concluidos'] ?? 0); ?></div>
          <div class="lbl">Concluídos</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Banner urgente: aguardando orçamento -->
  <?php if ($chamadosAguardando): ?>
  <div class="banner-orcamento">
    <div class="icone">📝</div>
    <div class="flex-grow-1">
      <strong><?php echo count($chamadosAguardando); ?> chamado<?php echo count($chamadosAguardando) > 1 ? 's' : ''; ?> aguardando seu orçamento!</strong>
      <div style="font-size:.83rem;color:#92400e;">Clientes estão esperando — envie o orçamento para avançar o serviço.</div>
    </div>
    <a href="orcamento.php?chamado=<?php echo (int)$chamadosAguardando[0]['id']; ?>" class="btn btn-sm btn-warning fw-bold flex-shrink-0">Enviar agora</a>
  </div>
  <?php endif; ?>

  <?php if (!$categoriasServico): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <span>⚠️</span>
    <span>Você ainda não possui serviços cadastrados.
      <a href="servicos.php" class="alert-link fw-semibold">Crie seus serviços</a> para aparecer no catálogo e receber chamados.
    </span>
  </div>
  <?php endif; ?>

  <!-- ── Chamados Pendentes ── -->
  <div class="mb-5">
    <div class="secao-titulo">
      <span>📋</span> Chamados disponíveis
      <?php if ($chamadosDisp): ?>
        <span class="count-badge"><?php echo count($chamadosDisp); ?></span>
      <?php endif; ?>
    </div>
    <?php if (!$chamadosDisp): ?>
      <div class="text-center py-4 text-muted" style="font-size:.9rem;">
        <div style="font-size:2rem;margin-bottom:.4rem;">🔍</div>
        Nenhum chamado pendente no momento. Aguarde novas solicitações.
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($chamadosDisp as $p):
          $isDireto = !empty($p['solicitacao_direta']);
          $cat      = $p['categoria'] ?? '';
          $catIco   = $catIcons[$cat] ?? '🔩';
          $temFoto  = !empty($p['cliente_foto']);
        ?>
        <div class="col-md-6 col-xl-4">
          <div class="card-chamado">
            <div class="card-chamado-strip <?php echo $isDireto ? 'strip-direta' : 'strip-fila'; ?>"></div>
            <div class="card-chamado-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="cliente-mini">
                  <?php if ($temFoto): ?>
                    <img src="../../<?php echo htmlspecialchars($p['cliente_foto']); ?>" class="foto" alt="">
                  <?php else: ?>
                    <div class="fb"><?php echo mb_strtoupper(mb_substr($p['cliente_nome'] ?? '?', 0, 1)); ?></div>
                  <?php endif; ?>
                  <div>
                    <div class="nome"><?php echo htmlspecialchars($p['cliente_nome'] ?? ''); ?></div>
                    <div class="cat"><?php echo $catIco . ' ' . htmlspecialchars($cat); ?></div>
                  </div>
                </div>
                <?php if ($isDireto): ?>
                  <span class="badge-direta">★ Direta</span>
                <?php else: ?>
                  <span class="badge-fila">Fila</span>
                <?php endif; ?>
              </div>

              <div class="desc-chamado"><?php echo htmlspecialchars($p['descricao'] ?? ''); ?></div>

              <div>
                <?php if (!empty($p['endereco_servico'])): ?>
                  <span class="info-chip">📍 <?php echo htmlspecialchars(mb_strimwidth($p['endereco_servico'], 0, 35, '…')); ?></span>
                <?php endif; ?>
                <?php if (!empty($p['data_agendamento'])): ?>
                  <span class="info-chip">🗓 <?php echo date('d/m/Y H:i', strtotime($p['data_agendamento'])); ?></span>
                <?php endif; ?>
                <?php if (!empty($p['fotos'])): ?>
                  <span class="info-chip">📷 <?php echo count($p['fotos']); ?> foto<?php echo count($p['fotos']) > 1 ? 's' : ''; ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="card-chamado-footer">
              <?php if ($isDireto): ?>
                <form method="post" class="d-inline js-confirm-aceitar js-guard-submit">
                  <input type="hidden" name="aceitar_direto_id" value="<?php echo (int)$p['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-warning fw-bold">Aceitar</button>
                </form>
                <form method="post" class="d-inline js-confirm-negar js-guard-submit">
                  <input type="hidden" name="recusar_direto_id" value="<?php echo (int)$p['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Recusar</button>
                </form>
              <?php else: ?>
                <form method="post" class="d-inline js-confirm-aceitar js-guard-submit">
                  <input type="hidden" name="aceitar_id" value="<?php echo (int)$p['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-warning fw-bold">Aceitar</button>
                </form>
                <form method="post" class="d-inline js-confirm-negar js-guard-submit">
                  <input type="hidden" name="negar_id" value="<?php echo (int)$p['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Negar</button>
                </form>
              <?php endif; ?>
              <a href="../chat.php?chamado=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-primary ms-auto">💬</a>
              <button type="button" class="btn btn-sm btn-outline-secondary js-open-cliente-perfil"
                data-cliente-nome="<?php echo htmlspecialchars($p['cliente_nome'], ENT_QUOTES); ?>"
                data-cliente-foto="<?php echo htmlspecialchars($p['cliente_foto'] ?? '', ENT_QUOTES); ?>"
                data-cliente-telefone="<?php echo htmlspecialchars($p['cliente_telefone'] ?? '', ENT_QUOTES); ?>"
                data-cliente-endereco="<?php echo htmlspecialchars($p['endereco_servico'] ?? '', ENT_QUOTES); ?>"
                data-problema-fotos="<?php echo htmlspecialchars(json_encode($p['fotos'] ?? []), ENT_QUOTES); ?>"
                data-resumo="<?php echo htmlspecialchars(mb_strimwidth($p['descricao'], 0, 170, '...'), ENT_QUOTES); ?>">👤</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Aguardando Orçamento ── -->
  <?php if ($chamadosAguardando): ?>
  <div class="mb-5">
    <div class="secao-titulo">
      <span>📝</span> Aguardando orçamento
      <span class="count-badge" style="background:#ffc107;color:#0d1b3d;"><?php echo count($chamadosAguardando); ?></span>
    </div>
    <div class="row g-3">
      <?php foreach ($chamadosAguardando as $aw):
        $temFoto = !empty($aw['cliente_foto']);
        $cat     = $aw['categoria'] ?? '';
        $catIco  = $catIcons[$cat] ?? '🔩';
      ?>
      <div class="col-md-6 col-xl-4">
        <div class="card-chamado">
          <div class="card-chamado-strip strip-aguard"></div>
          <div class="card-chamado-body">
            <div class="cliente-mini mb-2">
              <?php if ($temFoto): ?>
                <img src="../../<?php echo htmlspecialchars($aw['cliente_foto']); ?>" class="foto" alt="">
              <?php else: ?>
                <div class="fb"><?php echo mb_strtoupper(mb_substr($aw['cliente_nome'] ?? '?', 0, 1)); ?></div>
              <?php endif; ?>
              <div>
                <div class="nome"><?php echo htmlspecialchars($aw['cliente_nome'] ?? ''); ?></div>
                <div class="cat"><?php echo $catIco . ' ' . htmlspecialchars($cat); ?></div>
              </div>
            </div>
            <div class="desc-chamado"><?php echo htmlspecialchars($aw['descricao'] ?? ''); ?></div>
            <div>
              <?php if (!empty($aw['endereco_servico'])): ?>
                <span class="info-chip">📍 <?php echo htmlspecialchars(mb_strimwidth($aw['endereco_servico'], 0, 35, '…')); ?></span>
              <?php endif; ?>
              <?php if (!empty($aw['data_agendamento'])): ?>
                <span class="info-chip">🗓 <?php echo date('d/m/Y H:i', strtotime($aw['data_agendamento'])); ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-chamado-footer">
            <a href="orcamento.php?chamado=<?php echo (int)$aw['id']; ?>" class="btn btn-sm btn-warning fw-bold flex-grow-1">Enviar orçamento</a>
            <a href="../chat.php?chamado=<?php echo (int)$aw['id']; ?>" class="btn btn-sm btn-outline-primary">💬</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Em andamento ── -->
  <div class="mb-5">
    <div class="secao-titulo">
      <span>🔧</span> Em andamento
      <?php if ($meusChamados): ?>
        <span class="count-badge" style="background:#ea580c;"><?php echo count($meusChamados); ?></span>
      <?php endif; ?>
    </div>
    <?php if (!$meusChamados): ?>
      <div class="text-center py-4 text-muted" style="font-size:.9rem;">
        <div style="font-size:2rem;margin-bottom:.4rem;">🛠</div>
        Nenhum chamado ativo no momento.
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($meusChamados as $c):
          $temFoto  = !empty($c['cliente_foto']);
          $cat      = $c['categoria'] ?? '';
          $catIco   = $catIcons[$cat] ?? '🔩';
          $reagend  = !empty($c['reagendamento_pendente']) && !empty($c['data_agendamento_proposta']);
          $valorFmt = 'R$ ' . number_format((float)($c['preco_sugerido'] ?? 0), 2, ',', '.');
        ?>
        <div class="col-md-6 col-xl-4">
          <div class="card-chamado">
            <div class="card-chamado-strip strip-andando"></div>
            <div class="card-chamado-body">
              <div class="cliente-mini mb-2">
                <?php if ($temFoto): ?>
                  <img src="../../<?php echo htmlspecialchars($c['cliente_foto']); ?>" class="foto" alt="">
                <?php else: ?>
                  <div class="fb"><?php echo mb_strtoupper(mb_substr($c['cliente_nome'] ?? '?', 0, 1)); ?></div>
                <?php endif; ?>
                <div>
                  <div class="nome"><?php echo htmlspecialchars($c['cliente_nome'] ?? ''); ?></div>
                  <div class="cat"><?php echo $catIco . ' ' . htmlspecialchars($cat); ?></div>
                </div>
              </div>

              <?php if ($reagend): ?>
              <div class="reagend-strip">
                🗓 <strong>Nova proposta de horário:</strong>
                <?php echo date('d/m/Y H:i', strtotime($c['data_agendamento_proposta'])); ?>
                <div class="d-flex gap-1 mt-1">
                  <form method="post" class="d-inline js-guard-submit">
                    <input type="hidden" name="aceitar_reagendamento_id" value="<?php echo (int)$c['id']; ?>">
                    <button class="btn btn-xs btn-success" style="font-size:.75rem;padding:.15rem .55rem;">Aceitar</button>
                  </form>
                  <form method="post" class="d-inline js-guard-submit">
                    <input type="hidden" name="recusar_reagendamento_id" value="<?php echo (int)$c['id']; ?>">
                    <button class="btn btn-xs btn-outline-danger" style="font-size:.75rem;padding:.15rem .55rem;">Recusar</button>
                  </form>
                </div>
              </div>
              <?php endif; ?>

              <div>
                <?php if (!empty($c['endereco_servico'])): ?>
                  <span class="info-chip">📍 <?php echo htmlspecialchars(mb_strimwidth($c['endereco_servico'], 0, 35, '…')); ?></span>
                <?php endif; ?>
                <?php if (!empty($c['data_agendamento']) && !$reagend): ?>
                  <span class="info-chip">🗓 <?php echo date('d/m/Y H:i', strtotime($c['data_agendamento'])); ?></span>
                <?php endif; ?>
                <span class="info-chip">💰 <?php echo $valorFmt; ?></span>
                <?php if (!empty($c['cliente_telefone'])): ?>
                  <span class="info-chip">📞 <?php echo htmlspecialchars($c['cliente_telefone']); ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="card-chamado-footer">
              <form method="post" class="d-flex gap-1 flex-grow-1 js-guard-submit">
                <input type="hidden" name="chamado_id" value="<?php echo (int)$c['id']; ?>">
                <select name="novo_status" class="form-select form-select-sm" style="font-size:.8rem;">
                  <?php foreach (['Em Andamento', 'Concluído'] as $opt): ?>
                    <option <?php echo ($c['status'] ?? '') === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary flex-shrink-0">OK</button>
              </form>
              <a href="../chat.php?chamado=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-outline-primary">💬</a>
              <button type="button"
                class="btn btn-sm btn-warning btn-a-caminho"
                data-chamado-id="<?php echo (int)$c['id']; ?>"
                title="Compartilhar localização">📍</button>
              <button type="button" class="btn btn-sm btn-outline-secondary js-open-cliente-perfil"
                data-cliente-nome="<?php echo htmlspecialchars($c['cliente_nome'] ?? '', ENT_QUOTES); ?>"
                data-cliente-foto="<?php echo htmlspecialchars($c['cliente_foto'] ?? '', ENT_QUOTES); ?>"
                data-cliente-telefone="<?php echo htmlspecialchars($c['cliente_telefone'] ?? '', ENT_QUOTES); ?>"
                data-cliente-endereco="<?php echo htmlspecialchars($c['endereco_servico'] ?? '', ENT_QUOTES); ?>"
                data-problema-fotos="<?php echo htmlspecialchars(json_encode($c['fotos'] ?? []), ENT_QUOTES); ?>"
                data-resumo="<?php echo htmlspecialchars(mb_strimwidth($c['descricao'] ?? '', 0, 170, '...'), ENT_QUOTES); ?>">👤</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Histórico ── -->
  <?php if ($historico): ?>
  <div class="mb-4">
    <div class="secao-titulo d-flex justify-content-between">
      <span class="d-flex align-items-center gap-2">
        <span>📂</span> Histórico de serviços
      </span>
      <a href="financeiro.php" class="btn btn-sm btn-outline-warning fw-semibold" style="font-size:.8rem;">Ver relatório financeiro</a>
    </div>
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle hist-table mb-0">
          <thead class="table-primary">
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Categoria</th>
              <th>Valor</th>
              <th>Pagamento</th>
              <th>Status</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($historico as $h):
            $badgeH   = $h['status'] === 'Concluído' ? 'success' : 'secondary';
            $badgePag = '';
            if ($h['pag_status'] === 'Pago')        $badgePag = 'success';
            elseif ($h['pag_status'] === 'Pendente') $badgePag = 'warning text-dark';
            elseif ($h['pag_status'] === 'Estornado') $badgePag = 'danger';
          ?>
            <tr>
              <td class="text-muted"><?php echo (int)$h['id']; ?></td>
              <td class="fw-semibold"><?php echo htmlspecialchars($h['cliente_nome'] ?? ''); ?></td>
              <td><?php echo ($catIcons[$h['categoria'] ?? ''] ?? '🔩') . ' ' . htmlspecialchars($h['categoria'] ?? ''); ?></td>
              <td>R$ <?php echo number_format((float)($h['pag_valor'] ?? $h['preco_sugerido'] ?? 0), 2, ',', '.'); ?></td>
              <td>
                <?php if ($h['pag_status']): ?>
                  <span class="badge bg-<?php echo $badgePag; ?>"><?php echo htmlspecialchars($h['pag_status']); ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-<?php echo $badgeH; ?>"><?php echo htmlspecialchars($h['status'] ?? ''); ?></span></td>
              <td class="text-muted"><?php echo date('d/m/Y', strtotime($h['atualizado_em'] ?? $h['criado_em'])); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- Modal foto ampliada -->
<div class="modal fade" id="modalFotoFixnow" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-fn-foto-titulo>Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" alt="" class="img-fluid rounded shadow-sm" data-fn-foto-img style="max-height: 70vh;">
      </div>
    </div>
  </div>
</div>

<!-- Modal perfil do cliente -->
<div class="modal fade fn-profile-modal" id="modalClientePerfil" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title">Perfil do cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body pt-0">
        <div class="row g-3 align-items-stretch">
          <div class="col-md-5">
            <div class="fn-profile-photo-frame">
              <img src="" class="fn-profile-main-photo" data-cliente-modal-foto alt="Foto do cliente">
            </div>
          </div>
          <div class="col-md-7">
            <h4 class="h5 mb-2" data-cliente-modal-nome></h4>
            <div class="fn-profile-info-card mb-2">
              <p class="mb-1"><strong>Telefone:</strong> <span data-cliente-modal-tel></span></p>
              <p class="mb-0"><strong>Endereço:</strong> <span data-cliente-modal-end></span></p>
            </div>
            <div class="fn-profile-resumo"><strong>Resumo:</strong> <span data-cliente-modal-resumo></span></div>
          </div>
          <div class="col-12">
            <h6 class="mb-2">Fotos do problema</h6>
            <div id="carouselProblema" class="carousel slide" data-bs-ride="false">
              <div class="carousel-inner" data-cliente-modal-problema-inner style="border-radius:8px;overflow:hidden;background:#000;"></div>
              <button class="carousel-control-prev" type="button" data-bs-target="#carouselProblema" data-bs-slide="prev" id="btnCarouselPrev" style="display:none;">
                <span class="carousel-control-prev-icon"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#carouselProblema" data-bs-slide="next" id="btnCarouselNext" style="display:none;">
                <span class="carousel-control-next-icon"></span>
              </button>
            </div>
            <div class="text-center mt-1" id="carouselProblemaCounter" style="font-size:.75rem;color:#6c757d;display:none;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/forms-helpers.js"></script>
<script src="../../assets/js/foto-lightbox.js"></script>
<script src="../../assets/js/dashboard-prestador.js"></script>
<script>
(function () {
  const btns = document.querySelectorAll('.btn-a-caminho');
  if (!btns.length) return;
  const watches = {};
  btns.forEach(function (btn) {
    const chamadoId = parseInt(btn.dataset.chamadoId, 10);
    btn.addEventListener('click', function () {
      if (watches[chamadoId]) {
        navigator.geolocation.clearWatch(watches[chamadoId]);
        delete watches[chamadoId];
        fetch('../../api/rastreamento.php?chamado=' + chamadoId, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'acao=parar',
        });
        btn.textContent = '📍';
        btn.classList.replace('btn-danger', 'btn-warning');
        btn.title = 'Compartilhar localização';
        return;
      }
      if (!navigator.geolocation) { alert('Seu dispositivo não suporta GPS.'); return; }
      btn.textContent = '⏳';
      btn.disabled = true;
      watches[chamadoId] = navigator.geolocation.watchPosition(
        function (pos) {
          btn.textContent = '🔴';
          btn.title = 'Parar compartilhamento';
          btn.classList.replace('btn-warning', 'btn-danger');
          btn.disabled = false;
          fetch('../../api/rastreamento.php?chamado=' + chamadoId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'acao=posicao&lat=' + pos.coords.latitude + '&lng=' + pos.coords.longitude,
          });
        },
        function () {
          btn.textContent = '📍';
          btn.disabled = false;
          alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
        },
        { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
      );
    });
  });
})();
</script>
</body>
</html>
