<?php
session_start();
require_once __DIR__ . '/../../controller/FinanceiroPrestadorControl.php';

$ctrl = new FinanceiroPrestadorControl();
$ctrl->processar();

$tecnicoNome    = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto    = $_SESSION['tecnico_foto'] ?? '';
$naoLidas       = $ctrl->naoLidas;
$brutoRecebido  = $ctrl->brutoRecebido;
$brutoPendente  = $ctrl->brutoPendente;
$liquidoRecebido= $ctrl->liquidoRecebido;
$liquidoPendente= $ctrl->liquidoPendente;
$taxaTotal      = $ctrl->taxaTotal;
$totalServicos  = $ctrl->totalServicos;
$mensal         = $ctrl->mensal;
$detalhes       = $ctrl->detalhes;
$filtroMes      = $ctrl->filtroMes;
$filtroAno      = $ctrl->filtroAno;
$paginaAtiva    = 'financeiro';
$TAXA           = 0.20;
$anos           = range((int)date('Y'), 2024);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Financeiro - Prestador Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Relatório Financeiro</h2>
  <p class="text-muted mb-4">A plataforma retém <strong>20%</strong> sobre cada serviço. O valor líquido é o que você recebe (80%).</p>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card fn-stat-card h-100">
        <div class="card-body">
          <p class="text-muted mb-1">Serviços concluídos</p>
          <h3 class="mb-0"><?php echo $totalServicos; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card fn-stat-card h-100 border-success">
        <div class="card-body">
          <p class="text-muted mb-1">Líquido recebido (80%)</p>
          <h3 class="mb-0 text-success">R$ <?php echo number_format($liquidoRecebido, 2, ',', '.'); ?></h3>
          <small class="text-muted">Bruto: R$ <?php echo number_format($brutoRecebido, 2, ',', '.'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card fn-stat-card h-100 border-warning">
        <div class="card-body">
          <p class="text-muted mb-1">A receber — líquido (80%)</p>
          <h3 class="mb-0 text-warning">R$ <?php echo number_format($liquidoPendente, 2, ',', '.'); ?></h3>
          <small class="text-muted">Bruto: R$ <?php echo number_format($brutoPendente, 2, ',', '.'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card fn-stat-card h-100">
        <div class="card-body">
          <p class="text-muted mb-1">Taxa Fix Now (20%)</p>
          <h3 class="mb-0 text-secondary">R$ <?php echo number_format($taxaTotal, 2, ',', '.'); ?></h3>
          <small class="text-muted">Sobre valores já pagos</small>
        </div>
      </div>
    </div>
  </div>

  <?php if ($mensal): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <h5 class="mb-3">Recebimentos por mês</h5>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-primary">
            <tr>
              <th>Mês</th>
              <th>Serviços pagos</th>
              <th>Bruto</th>
              <th>Taxa Fix Now (20%)</th>
              <th>Líquido (80%)</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($mensal as $m):
            $brutoM   = (float)$m['bruto'];
            $taxaM    = $brutoM * $TAXA;
            $liquidoM = $brutoM * (1 - $TAXA);
          ?>
            <tr>
              <td><?php echo htmlspecialchars($m['mes_label']); ?></td>
              <td><?php echo (int)$m['qtd']; ?></td>
              <td class="text-muted">R$ <?php echo number_format($brutoM, 2, ',', '.'); ?></td>
              <td class="text-danger">- R$ <?php echo number_format($taxaM, 2, ',', '.'); ?></td>
              <td class="fw-semibold text-success">R$ <?php echo number_format($liquidoM, 2, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h5 class="mb-0">Detalhamento de serviços concluídos</h5>
        <form method="get" class="d-flex gap-2 align-items-center">
          <select name="mes" class="form-select form-select-sm" style="width: auto;">
            <option value="">Todos os meses</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?php echo $m; ?>" <?php echo $filtroMes === (string)$m ? 'selected' : ''; ?>>
                <?php echo date('F', mktime(0,0,0,$m,1)); ?>
              </option>
            <?php endfor; ?>
          </select>
          <select name="ano" class="form-select form-select-sm" style="width: auto;">
            <?php foreach ($anos as $a): ?>
              <option value="<?php echo $a; ?>" <?php echo $filtroAno === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-warning">Filtrar</button>
          <?php if ($filtroMes !== '' || $filtroAno !== (int)date('Y')): ?>
            <a href="financeiro.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if (!$detalhes): ?>
        <p class="text-muted mb-0">Nenhum serviço concluído no período selecionado.</p>
      <?php else: ?>
        <?php
          $totalBrutoPeriodo   = 0.0;
          $totalLiquidoPeriodo = 0.0;
        ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle table-sm">
            <thead class="table-primary">
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Valor bruto</th>
                <th>Taxa (20%)</th>
                <th>Líquido (80%)</th>
                <th>Método</th>
                <th>Pagamento</th>
                <th>Pago em</th>
                <th>Concluído em</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($detalhes as $d):
              $valorBruto   = (float)($d['pag_valor'] ?? $d['preco_sugerido']);
              $valorTaxa    = $valorBruto * $TAXA;
              $valorLiquido = $valorBruto * (1 - $TAXA);
              $badgePag     = '';
              if ($d['pag_status'] === 'Pago') {
                  $badgePag = 'success';
                  $totalBrutoPeriodo   += $valorBruto;
                  $totalLiquidoPeriodo += $valorLiquido;
              } elseif ($d['pag_status'] === 'Pendente') {
                  $badgePag = 'warning text-dark';
              } elseif ($d['pag_status'] === 'Estornado') {
                  $badgePag = 'danger';
              }
            ?>
              <tr>
                <td><?php echo (int)$d['chamado_id']; ?></td>
                <td><?php echo htmlspecialchars($d['cliente_nome']); ?></td>
                <td><?php echo htmlspecialchars($d['categoria']); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($d['descricao'], 0, 40, '...')); ?></td>
                <td class="text-muted">R$ <?php echo number_format($valorBruto, 2, ',', '.'); ?></td>
                <td class="text-danger small">- R$ <?php echo number_format($valorTaxa, 2, ',', '.'); ?></td>
                <td class="fw-semibold <?php echo $d['pag_status'] === 'Pago' ? 'text-success' : ''; ?>">
                  R$ <?php echo number_format($valorLiquido, 2, ',', '.'); ?>
                </td>
                <td><?php echo $d['metodo'] ? htmlspecialchars($d['metodo']) : '—'; ?></td>
                <td>
                  <?php if ($d['pag_status']): ?>
                    <span class="badge bg-<?php echo $badgePag; ?>"><?php echo htmlspecialchars($d['pag_status']); ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td><?php echo $d['pago_em'] ? date('d/m/Y', strtotime($d['pago_em'])) : '—'; ?></td>
                <td><?php echo date('d/m/Y', strtotime($d['concluido_em'])); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="4" class="text-end">Total no período (pagos):</td>
                <td class="text-muted">R$ <?php echo number_format($totalBrutoPeriodo, 2, ',', '.'); ?></td>
                <td class="text-danger">- R$ <?php echo number_format($totalBrutoPeriodo * $TAXA, 2, ',', '.'); ?></td>
                <td class="text-success">R$ <?php echo number_format($totalLiquidoPeriodo, 2, ',', '.'); ?></td>
                <td colspan="4"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
