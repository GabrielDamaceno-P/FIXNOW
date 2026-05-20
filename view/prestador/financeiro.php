<?php
session_start();
require_once __DIR__ . '/../../controller/FinanceiroPrestadorControl.php';

$ctrl = new FinanceiroPrestadorControl();
$ctrl->processar();

$tecnicoNome     = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto     = $_SESSION['tecnico_foto'] ?? '';
$naoLidas        = $ctrl->naoLidas;
$brutoRecebido   = $ctrl->brutoRecebido;
$brutoPendente   = $ctrl->brutoPendente;
$liquidoRecebido = $ctrl->liquidoRecebido;
$liquidoPendente = $ctrl->liquidoPendente;
$taxaTotal       = $ctrl->taxaTotal;
$totalServicos   = $ctrl->totalServicos;
$mensal          = $ctrl->mensal;
$detalhes        = $ctrl->detalhes;
$filtroMes       = $ctrl->filtroMes;
$filtroAno       = $ctrl->filtroAno;
$paginaAtiva     = 'financeiro';
$TAXA            = 0.20;
$anos            = range((int)date('Y'), 2024);

$mesesPt = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$catIcons = ['Suporte TI'=>'💻','Elétrica'=>'⚡','Hidráulica'=>'🔧','Pintura'=>'🎨','Marcenaria'=>'🪚'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Financeiro - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-header{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-header::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-header h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-header p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .stat-fin{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.1rem 1.3rem;display:flex;align-items:center;gap:.9rem;height:100%;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .stat-fin-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
    .stat-fin .val{font-size:1.5rem;font-weight:800;line-height:1.1;margin-bottom:.1rem}
    .stat-fin .lbl{font-size:.78rem;color:#667085}
    .stat-fin .sub{font-size:.74rem;color:#9ca3af;margin-top:.15rem}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}
    .filtro-bar{background:#fff;border:1.5px solid #e8ecf3;border-radius:12px;padding:.85rem 1.1rem;display:flex;flex-wrap:wrap;align-items:center;gap:.6rem;margin-bottom:1.2rem}
    .table-fin th,.table-fin td{padding:.55rem .75rem;font-size:.84rem}
    /* DARK */
    [data-theme="dark"] .stat-fin{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .stat-fin .lbl,[data-theme="dark"] .stat-fin .sub{color:#8090b0}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .filtro-bar{background:#1e2538;border-color:#2e3650}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-4 mt-5">

  <div class="page-header">
    <div style="position:relative;z-index:1">
      <h1>💰 Relatório Financeiro</h1>
      <p>A plataforma retém <strong>20%</strong> de cada serviço — o valor líquido (80%) é o que você recebe.</p>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="stat-fin">
        <div class="stat-fin-icon" style="background:#eff6ff;">✅</div>
        <div>
          <div class="val" style="color:#1d4ed8;"><?php echo $totalServicos; ?></div>
          <div class="lbl">Serviços concluídos</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="stat-fin">
        <div class="stat-fin-icon" style="background:#f0fdf4;">💵</div>
        <div>
          <div class="val" style="color:#16a34a;">R$ <?php echo number_format($liquidoRecebido, 2, ',', '.'); ?></div>
          <div class="lbl">Líquido recebido (80%)</div>
          <div class="sub">Bruto: R$ <?php echo number_format($brutoRecebido, 2, ',', '.'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="stat-fin">
        <div class="stat-fin-icon" style="background:#fffbeb;">⏳</div>
        <div>
          <div class="val" style="color:#d97706;">R$ <?php echo number_format($liquidoPendente, 2, ',', '.'); ?></div>
          <div class="lbl">A receber — líquido (80%)</div>
          <div class="sub">Bruto: R$ <?php echo number_format($brutoPendente, 2, ',', '.'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="stat-fin">
        <div class="stat-fin-icon" style="background:#fef2f2;">🏦</div>
        <div>
          <div class="val" style="color:#dc2626;">R$ <?php echo number_format($taxaTotal, 2, ',', '.'); ?></div>
          <div class="lbl">Taxa Fix Now (20%)</div>
          <div class="sub">Sobre valores já pagos</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mensal -->
  <?php if ($mensal): ?>
  <div class="mb-4">
    <div class="secao-titulo"><span>📅</span> Recebimentos por mês</div>
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle table-fin mb-0">
          <thead class="table-primary">
            <tr><th>Mês</th><th>Serviços</th><th>Bruto</th><th>Taxa Fix Now</th><th>Líquido (80%)</th></tr>
          </thead>
          <tbody>
          <?php foreach ($mensal as $m):
            $bm = (float)$m['bruto'];
            $tm = $bm * $TAXA;
            $lm = $bm * (1 - $TAXA);
          ?>
            <tr>
              <td class="fw-semibold"><?php echo htmlspecialchars($m['mes_label']); ?></td>
              <td><?php echo (int)$m['qtd']; ?></td>
              <td class="text-muted">R$ <?php echo number_format($bm, 2, ',', '.'); ?></td>
              <td class="text-danger">− R$ <?php echo number_format($tm, 2, ',', '.'); ?></td>
              <td class="fw-bold text-success">R$ <?php echo number_format($lm, 2, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Detalhamento -->
  <div class="mb-4">
    <div class="secao-titulo"><span>🧾</span> Detalhamento de serviços concluídos</div>

    <!-- Filtros -->
    <div class="filtro-bar">
      <form method="get" class="d-flex flex-wrap gap-2 align-items-center w-100">
        <label class="fw-semibold" style="font-size:.85rem;white-space:nowrap;">Filtrar por:</label>
        <select name="mes" class="form-select form-select-sm" style="width:auto;">
          <option value="">Todos os meses</option>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?php echo $m; ?>" <?php echo $filtroMes === (string)$m ? 'selected' : ''; ?>><?php echo $mesesPt[$m]; ?></option>
          <?php endfor; ?>
        </select>
        <select name="ano" class="form-select form-select-sm" style="width:auto;">
          <?php foreach ($anos as $a): ?>
            <option value="<?php echo $a; ?>" <?php echo $filtroAno === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-warning fw-semibold">Filtrar</button>
        <?php if ($filtroMes !== '' || $filtroAno !== (int)date('Y')): ?>
          <a href="financeiro.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
        <?php endif; ?>
      </form>
    </div>

    <?php if (!$detalhes): ?>
      <div class="text-center py-5 text-muted" style="font-size:.9rem;">
        <div style="font-size:2.5rem;margin-bottom:.5rem;">📭</div>
        Nenhum serviço concluído no período selecionado.
      </div>
    <?php else: ?>
      <?php $totBruto = 0.0; $totLiq = 0.0; ?>
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover align-middle table-fin mb-0">
            <thead class="table-primary">
              <tr>
                <th>#</th><th>Cliente</th><th>Categoria</th>
                <th>Bruto</th><th>Taxa</th><th>Líquido</th>
                <th>Método</th><th>Pgto</th><th>Pago em</th><th>Concluído</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($detalhes as $d):
              $vb = (float)($d['pag_valor'] ?? $d['preco_sugerido']);
              $vt = $vb * $TAXA;
              $vl = $vb * (1 - $TAXA);
              $bc = '';
              if ($d['pag_status'] === 'Pago')      { $bc = 'success'; $totBruto += $vb; $totLiq += $vl; }
              elseif ($d['pag_status'] === 'Pendente')  $bc = 'warning text-dark';
              elseif ($d['pag_status'] === 'Estornado') $bc = 'danger';
              $cat = $d['categoria'] ?? '';
            ?>
              <tr>
                <td class="text-muted"><?php echo (int)$d['chamado_id']; ?></td>
                <td class="fw-semibold"><?php echo htmlspecialchars($d['cliente_nome']); ?></td>
                <td><?php echo ($catIcons[$cat] ?? '🔩') . ' ' . htmlspecialchars($cat); ?></td>
                <td class="text-muted">R$ <?php echo number_format($vb, 2, ',', '.'); ?></td>
                <td class="text-danger" style="font-size:.8rem;">− R$ <?php echo number_format($vt, 2, ',', '.'); ?></td>
                <td class="fw-bold <?php echo $d['pag_status'] === 'Pago' ? 'text-success' : ''; ?>">R$ <?php echo number_format($vl, 2, ',', '.'); ?></td>
                <td class="text-muted"><?php echo $d['metodo'] ? htmlspecialchars($d['metodo']) : '—'; ?></td>
                <td>
                  <?php if ($d['pag_status']): ?>
                    <span class="badge bg-<?php echo $bc; ?>" style="font-size:.72rem;"><?php echo htmlspecialchars($d['pag_status']); ?></span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-muted"><?php echo $d['pago_em'] ? date('d/m/Y', strtotime($d['pago_em'])) : '—'; ?></td>
                <td class="text-muted"><?php echo date('d/m/Y', strtotime($d['concluido_em'])); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="fw-bold" style="background:#f8f9fc;">
                <td colspan="3" class="text-end" style="font-size:.84rem;">Total (pagos):</td>
                <td class="text-muted">R$ <?php echo number_format($totBruto, 2, ',', '.'); ?></td>
                <td class="text-danger" style="font-size:.8rem;">− R$ <?php echo number_format($totBruto * $TAXA, 2, ',', '.'); ?></td>
                <td class="text-success">R$ <?php echo number_format($totLiq, 2, ',', '.'); ?></td>
                <td colspan="4"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
