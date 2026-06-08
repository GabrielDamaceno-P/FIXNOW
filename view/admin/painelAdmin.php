<?php
session_start();
$paginaAtiva = 'painel';
require_once __DIR__ . '/../../controller/AdminControl.php';

$ctrl = new AdminControl();
$ctrl->processarPainel();

$stats             = $ctrl->statsGerais;
$pendentes         = $ctrl->prestadoresPendentes;
$chamados          = $ctrl->chamados;
$pagamentos        = $ctrl->pagamentos;
$lucroEmpresa      = $ctrl->lucroEmpresa;
$totalEstornado    = $ctrl->totalEstornado;
$lucroEstornado    = $ctrl->lucroEstornado;
$faturamentoMensal = $ctrl->faturamentoMensal;
$topPrestadores    = $ctrl->topPrestadores;
$atividadeRecente  = $ctrl->atividadeRecente;
$mensagem          = $ctrl->mensagem;
$erro              = $ctrl->erro;
$adminPerfil       = $ctrl->adminPerfil;
$isMaster          = $adminPerfil === 'Master';
$isOps             = in_array($adminPerfil, ['Master', 'Operacoes'], true);
$isFinanc          = in_array($adminPerfil, ['Master', 'Financeiro'], true);

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

// Dados para o gráfico
$mesesAbrev = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
               '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
$chartLabels = [];
$chartBruto  = [];
$chartLucro  = [];
foreach ($faturamentoMensal as $fm) {
    [$ano, $mes] = explode('-', $fm['mes']);
    $chartLabels[] = ($mesesAbrev[$mes] ?? $mes) . '/' . substr($ano, 2);
    $chartBruto[]  = (float)$fm['bruto'];
    $chartLucro[]  = (float)$fm['lucro'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel Admin - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .admin-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .admin-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .admin-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .admin-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .perfil-badge{display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:50px;padding:.25rem .75rem;font-size:.78rem;color:#fff;font-weight:600;backdrop-filter:blur(4px)}

    .kpi-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.1rem 1.3rem;display:flex;align-items:center;gap:.9rem;height:100%;box-shadow:0 3px 10px rgba(13,27,61,.05);transition:transform .18s,box-shadow .18s}
    .kpi-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(13,27,61,.1)}
    .kpi-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
    .kpi-val{font-size:1.5rem;font-weight:800;line-height:1.1;margin-bottom:.1rem}
    .kpi-lbl{font-size:.78rem;color:#667085}
    .kpi-sub{font-size:.74rem;color:#9ca3af;margin-top:.1rem}

    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}

    .feed-item{display:flex;align-items:flex-start;gap:.75rem;padding:.65rem 0;border-bottom:1px solid #f0f3fa}
    .feed-item:last-child{border-bottom:none}
    .feed-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
    .feed-txt{font-size:.83rem;color:#374151;line-height:1.4}
    .feed-time{font-size:.73rem;color:#9ca3af;margin-top:.1rem}

    .top-prest-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f0f3fa}
    .top-prest-item:last-child{border-bottom:none}
    .top-prest-rank{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0}
    .top-prest-foto{width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0}
    .top-prest-nome{font-size:.85rem;font-weight:700;color:#0d1b3d;line-height:1.2}
    .top-prest-esp{font-size:.75rem;color:#667085}

    .chart-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .side-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}

    /* DARK */
    [data-theme="dark"] .kpi-card,[data-theme="dark"] .chart-card,[data-theme="dark"] .side-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .kpi-lbl,[data-theme="dark"] .kpi-sub{color:#8090b0}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .feed-item,[data-theme="dark"] .top-prest-item{border-bottom-color:#2e3650}
    [data-theme="dark"] .feed-txt{color:#c8d0e0}
    [data-theme="dark"] .feed-time,[data-theme="dark"] .top-prest-esp{color:#5a6a8a}
    [data-theme="dark"] .top-prest-nome{color:#e4e8f4}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <!-- Hero -->
  <div class="admin-hero mb-4">
    <div style="position:relative;z-index:1;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
      <div class="flex-grow-1">
        <h1>🛡 Painel Administrativo</h1>
        <p style="margin-top:.4rem;">
          <span class="perfil-badge">
            <?php
              $perfilEmoji = match($adminPerfil) { 'Master'=>'👑', 'Operacoes'=>'⚙️', 'Financeiro'=>'💼', default=>'🔑' };
              echo $perfilEmoji . ' ' . htmlspecialchars($adminPerfil);
            ?>
          </span>
        </p>
      </div>
    </div>
  </div>

  <?php if ($mensagem): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($mensagem) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($erro) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <?php if (count($pendentes) > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
      <span style="font-size:1.5rem;">⚠️</span>
      <div class="flex-grow-1">
        <strong><?= count($pendentes) ?> prestador<?= count($pendentes) > 1 ? 'es' : '' ?> aguardando aprovação.</strong>
      </div>
      <a href="#secao-pendentes" class="btn btn-sm btn-warning fw-semibold">Ver agora</a>
    </div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:#eff6ff;">👥</div>
        <div>
          <div class="kpi-val" style="color:#1d4ed8;"><?= $stats['clientes'] ?></div>
          <div class="kpi-lbl">Clientes cadastrados</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:#f0fdf4;">🔧</div>
        <div>
          <div class="kpi-val" style="color:#16a34a;"><?= $stats['prestadores_ativos'] ?? 0 ?></div>
          <div class="kpi-lbl">Prestadores ativos</div>
          <div class="kpi-sub">Total: <?= $stats['prestadores'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:#fff7ed;">📋</div>
        <div>
          <div class="kpi-val" style="color:#ea580c;"><?= $stats['chamados_pendentes'] ?? 0 ?></div>
          <div class="kpi-lbl">Chamados pendentes</div>
          <div class="kpi-sub">Concluídos este mês: <?= $stats['chamados_mes'] ?? 0 ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:#f0fdf4;">💵</div>
        <div>
          <div class="kpi-val" style="color:#16a34a;">R$ <?= number_format($stats['faturamento'], 2, ',', '.') ?></div>
          <div class="kpi-lbl">Faturamento total (pagos)</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef2f2;">🏦</div>
        <div>
          <div class="kpi-val" style="color:#dc2626;">R$ <?= number_format($lucroEmpresa, 2, ',', '.') ?></div>
          <div class="kpi-lbl">Lucro Fix Now (comissão)</div>
          <div class="kpi-sub">15% destaque · 20% padrão</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:#fefce8;">⭐</div>
        <div>
          <div class="kpi-val" style="color:#ca8a04;"><?= number_format((float)($stats['avaliacao'] ?? 0), 1, ',', '.') ?></div>
          <div class="kpi-lbl">Avaliação média da plataforma</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráfico + Atividade recente -->
  <div class="row g-3 mb-4">
    <div class="col-lg-7">
      <div class="chart-card">
        <div class="secao-titulo"><span>📊</span> Faturamento dos últimos 6 meses</div>
        <?php if (empty($faturamentoMensal)): ?>
          <p class="text-muted text-center py-4" style="font-size:.9rem;">Sem dados de pagamento no período.</p>
        <?php else: ?>
          <canvas id="chartFaturamento" height="120"></canvas>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="side-card h-100">
        <div class="secao-titulo"><span>🕐</span> Atividade recente</div>
        <?php if (!$atividadeRecente): ?>
          <p class="text-muted" style="font-size:.85rem;">Nenhuma atividade registrada.</p>
        <?php else: ?>
          <?php
            $feedCfg = [
              'cliente'   => ['bg'=>'#eff6ff','emoji'=>'👤'],
              'prestador' => ['bg'=>'#f0fdf4','emoji'=>'🔧'],
              'chamado'   => ['bg'=>'#fff7ed','emoji'=>'📋'],
              'pagamento' => ['bg'=>'#f0fdfa','emoji'=>'💵'],
            ];
          ?>
          <?php foreach ($atividadeRecente as $ev):
            $cfg = $feedCfg[$ev['tipo']] ?? ['bg'=>'#f3f4f6','emoji'=>'🔔'];
          ?>
            <div class="feed-item">
              <div class="feed-icon" style="background:<?= $cfg['bg'] ?>;"><?= $cfg['emoji'] ?></div>
              <div>
                <div class="feed-txt"><?= htmlspecialchars($ev['descricao']) ?></div>
                <div class="feed-time"><?= date('d/m/Y H:i', strtotime($ev['criado_em'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Top Prestadores -->
  <?php if ($topPrestadores): ?>
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="side-card">
        <div class="secao-titulo"><span>🏆</span> Top 5 Prestadores</div>
        <div class="row g-0">
        <?php
          $rankColors = ['#ffc107','#adb5bd','#cd7f32','#6c757d','#6c757d'];
          $rankLabels = ['1º','2º','3º','4º','5º'];
        ?>
        <?php foreach ($topPrestadores as $i => $tp): ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div class="top-prest-item" style="padding:.7rem .5rem;">
              <div class="top-prest-rank" style="background:<?= $rankColors[$i] ?? '#6c757d' ?>;color:#fff;">
                <?= $rankLabels[$i] ?? ($i+1).'º' ?>
              </div>
              <img class="top-prest-foto"
                   src="../../<?= htmlspecialchars($tp['foto_perfil'] ?? 'assets/img/perfil/default-prestador.jpg') ?>"
                   alt="foto" onerror="this.src='../../assets/img/perfil/default-prestador.jpg'">
              <div class="flex-grow-1 min-w-0">
                <div class="top-prest-nome text-truncate"><?= htmlspecialchars($tp['nome']) ?></div>
                <div class="top-prest-esp text-truncate"><?= htmlspecialchars($tp['especialidade'] ?? '—') ?></div>
              </div>
              <div class="text-end flex-shrink-0">
                <div style="font-size:.82rem;font-weight:700;color:#ca8a04;">⭐ <?= number_format((float)$tp['avaliacao_media'],1,',','.') ?></div>
                <div style="font-size:.72rem;color:#9ca3af;"><?= (int)$tp['total_concluidos'] ?> serv.</div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Prestadores pendentes -->
  <?php if ($isOps): ?>
  <div class="side-card mb-4" id="secao-pendentes">
    <div class="secao-titulo"><span>⏳</span> Prestadores aguardando aprovação</div>
    <?php if (!$pendentes): ?>
      <p class="text-muted mb-0" style="font-size:.9rem;">✅ Nenhum prestador aguardando aprovação.</p>
    <?php else: ?>
      <p class="text-muted mb-3" style="font-size:.83rem;">
        Aprove ou recuse novos prestadores.
        <a href="prestadores.php" class="ms-2 btn btn-sm btn-outline-warning">Ver todos →</a>
      </p>
      <div class="table-responsive">
        <table class="table table-hover align-middle table-sm">
          <thead class="table-primary">
            <tr><th>#</th><th>Nome</th><th>E-mail</th><th>Especialidade</th><th>Telefone</th><th>Gênero</th><th>Data</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach ($pendentes as $tp): ?>
            <tr>
              <td><?= (int)$tp['id'] ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($tp['nome']) ?></td>
              <td><?= htmlspecialchars($tp['email'] ?? '—') ?></td>
              <td><?= htmlspecialchars($tp['especialidade'] ?? '—') ?></td>
              <td><?= htmlspecialchars($tp['telefone']) ?></td>
              <td><?= htmlspecialchars($tp['genero'] ?? '—') ?></td>
              <td><?= date('d/m/Y H:i', strtotime($tp['criado_em'])) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <form method="post" class="d-inline">
                    <input type="hidden" name="acao" value="aprovar_prestador">
                    <input type="hidden" name="tecnico_id" value="<?= (int)$tp['id'] ?>">
                    <button class="btn btn-sm btn-success">Aprovar</button>
                  </form>
                  <form method="post" class="d-inline" onsubmit="return confirm('Recusar este cadastro?')">
                    <input type="hidden" name="acao" value="recusar_prestador">
                    <input type="hidden" name="tecnico_id" value="<?= (int)$tp['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Recusar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Chamados -->
  <?php if ($isOps && $chamados): ?>
  <div class="side-card mb-4">
    <div class="secao-titulo"><span>📋</span> Chamados</div>
    <input type="search" class="form-control form-control-sm mb-3 js-admin-filter"
           placeholder="Filtrar chamados..." data-filter-target="#tabela-chamados">
    <div class="table-responsive">
      <table class="table table-hover align-middle table-sm" id="tabela-chamados">
        <thead class="table-primary">
          <tr><th>#</th><th>Cliente</th><th>Técnico</th><th>Categoria</th><th>Preço</th><th>Só ♀</th><th>Status</th><th>Data</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($chamados as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?= htmlspecialchars($c['cliente_nome']) ?></td>
            <td><?= htmlspecialchars($c['tecnico_nome'] ?? 'A definir') ?></td>
            <td><?= htmlspecialchars($c['categoria']) ?></td>
            <td>R$ <?= number_format((float)$c['preco_sugerido'], 2, ',', '.') ?></td>
            <td><?= !empty($c['prest_feminino']) ? 'Sim' : '—' ?></td>
            <td>
              <?php
                $stBadge = match($c['status']) {
                  'Concluído'    => 'success',
                  'Em Andamento' => 'primary',
                  'Pendente'     => 'warning text-dark',
                  default        => 'danger'
                };
              ?>
              <span class="badge bg-<?= $stBadge ?>" style="font-size:.72rem;"><?= htmlspecialchars($c['status']) ?></span>
            </td>
            <td><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
            <td>
              <div class="d-flex flex-column gap-1">
                <form method="post" class="d-flex gap-1">
                  <input type="hidden" name="acao" value="alterar_status_chamado">
                  <input type="hidden" name="chamado_id" value="<?= (int)$c['id'] ?>">
                  <select name="status" class="form-select form-select-sm" style="width:130px;">
                    <?php foreach (['Pendente','Em Andamento','Concluído','Negado'] as $opt): ?>
                      <option <?= $c['status']===$opt?'selected':'' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-warning">OK</button>
                </form>
                <?php if (in_array($c['status'], ['Pendente','Em Andamento'], true)): ?>
                <form method="post" onsubmit="return confirm('Negar e notificar o cliente?')">
                  <input type="hidden" name="acao" value="negar_chamado">
                  <input type="hidden" name="chamado_id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger w-100">Negar</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Pagamentos -->
  <?php if ($isFinanc): ?>
  <div class="side-card mb-4">
    <div class="secao-titulo"><span>💳</span> Pagamentos</div>
    <input type="search" class="form-control form-control-sm mb-3 js-admin-filter"
           placeholder="Filtrar pagamentos..." data-filter-target="#tabela-pagamentos">
    <div class="table-responsive">
      <table class="table table-hover align-middle table-sm" id="tabela-pagamentos">
        <thead class="table-primary">
          <tr><th># Pag.</th><th>Chamado</th><th>Cliente</th><th>Método</th><th>Valor</th><th>Status</th><th>Pago em</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pagamentos as $p):
          $stPag = match($p['status']) { 'Pago'=>'success','Pendente'=>'warning text-dark',default=>'danger' };
        ?>
          <tr>
            <td><?= (int)$p['id'] ?></td>
            <td>#<?= (int)$p['chamado_id'] ?></td>
            <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
            <td><?= htmlspecialchars($p['metodo'] ?? '—') ?></td>
            <td class="fw-semibold">R$ <?= number_format((float)$p['valor'], 2, ',', '.') ?></td>
            <td><span class="badge bg-<?= $stPag ?>" style="font-size:.72rem;"><?= htmlspecialchars($p['status']) ?></span></td>
            <td><?= $p['pago_em'] ? date('d/m/Y H:i', strtotime($p['pago_em'])) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="text-muted mb-0 mt-2" style="font-size:.8rem;">Comissão: 20% padrão · 15% prestadores em destaque.</p>
  </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/admin-panel.js"></script>
<?php if (!empty($faturamentoMensal)): ?>
<script>
(function() {
  var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  var gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
  var textColor = isDark ? '#8090b0' : '#667085';

  new Chart(document.getElementById('chartFaturamento'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [
        {
          label: 'Faturamento Bruto',
          data: <?= json_encode($chartBruto) ?>,
          backgroundColor: 'rgba(13,27,61,0.75)',
          borderRadius: 6,
          borderSkipped: false
        },
        {
          label: 'Lucro Fix Now',
          data: <?= json_encode($chartLucro) ?>,
          backgroundColor: 'rgba(201,94,0,0.8)',
          borderRadius: 6,
          borderSkipped: false
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top', labels: { color: textColor, font: { size: 12 } } },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              return ' R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2});
            }
          }
        }
      },
      scales: {
        x: { ticks: { color: textColor }, grid: { color: gridColor } },
        y: {
          beginAtZero: true,
          ticks: {
            color: textColor,
            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
          },
          grid: { color: gridColor }
        }
      }
    }
  });
})();
</script>
<?php endif; ?>
</body>
</html>
