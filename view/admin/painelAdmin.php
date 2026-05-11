<?php
session_start();
$paginaAtiva = 'painel';
require_once __DIR__ . '/../../controller/AdminControl.php';

$ctrl = new AdminControl();
$ctrl->processarPainel();

$stats        = $ctrl->statsGerais;
$pendentes    = $ctrl->prestadoresPendentes;
$chamados     = $ctrl->chamados;
$pagamentos   = $ctrl->pagamentos;
$lucroEmpresa = $ctrl->lucroEmpresa;
$mensagem     = $ctrl->mensagem;
$erro         = $ctrl->erro;
$adminPerfil  = $ctrl->adminPerfil;
$isMaster     = $adminPerfil === 'Master';
$isOps        = in_array($adminPerfil, ['Master', 'Operacoes'], true);
$isFinanc     = in_array($adminPerfil, ['Master', 'Financeiro'], true);

// Usar navbar compartilhado (define $pdo, $adminNome, $naoLidas, etc.)
ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel Admin - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Painel Administrativo</h2>
  <p class="text-muted mb-4">Perfil ativo: <strong><?= htmlspecialchars($adminPerfil) ?></strong></p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body"><small class="text-muted d-block">Clientes</small><h4 class="mb-0"><?= $stats['clientes'] ?></h4></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body"><small class="text-muted d-block">Prestadores ativos</small><h4 class="mb-0"><?= ($stats['prestadores_ativos'] ?? 0) ?> / <?= $stats['prestadores'] ?></h4></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body"><small class="text-muted d-block">Faturamento (pagos)</small><h4 class="mb-0">R$ <?= number_format($stats['faturamento'], 2, ',', '.') ?></h4></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body"><small class="text-muted d-block">Lucro da empresa (20%)</small><h4 class="mb-0 text-success">R$ <?= number_format($lucroEmpresa, 2, ',', '.') ?></h4></div>
      </div>
    </div>
  </div>

  <!-- Gestão de cadastros (pendentes) -->
  <?php if ($isOps): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <h4>Gestão de cadastros (prestadores)</h4>
      <p class="text-muted small">Aprove ou recuse novos prestadores com cadastro pendente de análise.
        <a href="prestadores.php" class="ms-2 btn btn-sm btn-outline-warning">Ver todos os prestadores</a>
      </p>
      <?php if (!$pendentes): ?>
        <p class="text-muted mb-0">Nenhum prestador aguardando aprovação.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-dark">
              <tr><th>#</th><th>Nome</th><th>E-mail</th><th>Especialidade</th><th>Telefone</th><th>Gênero</th><th>Data</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($pendentes as $tp): ?>
              <tr>
                <td><?= (int)$tp['id'] ?></td>
                <td><?= htmlspecialchars($tp['nome']) ?></td>
                <td><?= htmlspecialchars($tp['email'] ?? '—') ?></td>
                <td><?= htmlspecialchars($tp['especialidade'] ?? '—') ?></td>
                <td><?= htmlspecialchars($tp['telefone']) ?></td>
                <td><?= htmlspecialchars($tp['genero'] ?? '—') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($tp['criado_em'])) ?></td>
                <td class="d-flex gap-1">
                  <form method="post" class="d-inline">
                    <input type="hidden" name="acao" value="aprovar_prestador">
                    <input type="hidden" name="tecnico_id" value="<?= (int)$tp['id'] ?>">
                    <button class="btn btn-sm btn-success">Aprovar</button>
                  </form>
                  <form method="post" class="d-inline"
                        onsubmit="return confirm('Recusar este cadastro?')">
                    <input type="hidden" name="acao" value="recusar_prestador">
                    <input type="hidden" name="tecnico_id" value="<?= (int)$tp['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Recusar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Chamados -->
  <?php if ($isOps && $chamados): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <h4>Chamados</h4>
      <input type="search" class="form-control form-control-sm mb-2 js-admin-filter"
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
              <td><?= htmlspecialchars($c['status']) ?></td>
              <td><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
              <td>
                <div class="d-flex flex-column gap-1">
                  <form method="post" class="d-flex gap-1">
                    <input type="hidden" name="acao" value="alterar_status_chamado">
                    <input type="hidden" name="chamado_id" value="<?= (int)$c['id'] ?>">
                    <select name="status" class="form-select form-select-sm">
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
                    <button class="btn btn-sm btn-outline-danger w-100">Negar serviço</button>
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
  </div>
  <?php endif; ?>

  <!-- Financeiro / Pagamentos -->
  <?php if ($isFinanc): ?>
  <div class="card shadow-sm border-0 mb-4" id="secao-financeiro">
    <div class="card-body">
      <h4>Pagamentos</h4>
      <input type="search" class="form-control form-control-sm mb-2 js-admin-filter"
             placeholder="Filtrar pagamentos..." data-filter-target="#tabela-pagamentos">
      <div class="table-responsive">
        <table class="table table-striped align-middle table-sm" id="tabela-pagamentos">
          <thead class="table-primary">
            <tr><th># Pag.</th><th>Chamado</th><th>Cliente</th><th>Método</th><th>Valor</th><th>Status</th><th>Pago em</th></tr>
          </thead>
          <tbody>
          <?php foreach ($pagamentos as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>#<?= (int)$p['chamado_id'] ?></td>
              <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
              <td><?= htmlspecialchars($p['metodo'] ?? '—') ?></td>
              <td>R$ <?= number_format((float)$p['valor'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars($p['status']) ?></td>
              <td><?= $p['pago_em'] ? date('d/m/Y H:i', strtotime($p['pago_em'])) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="text-muted small mt-2 mb-0">Lucro calculado com comissão de 20% (padrão) ou 15% (prestadores em destaque) sobre pagamentos com status "Pago".</p>
    </div>
  </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/admin-panel.js"></script>
</body>
</html>
