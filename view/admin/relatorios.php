<?php
session_start();
$paginaAtiva = 'relatorios';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

if (isset($_GET['exportar'])) {
    $tipo = $_GET['exportar'];
    $dados = [];
    $cabecalho = [];
    $nomeArq = 'relatorio_' . $tipo . '_' . date('Ymd_His') . '.csv';

    if ($tipo === 'chamados') {
        $cabecalho = ['ID','Cliente','Técnico','Categoria','Preço','Status','Data'];
        $dados = $pdo->query("
            SELECT c.id, cl.nome, COALESCE(t.nome,'A definir'), c.categoria,
                   c.preco_sugerido, c.status,
                   DATE_FORMAT(c.criado_em,'%d/%m/%Y %H:%i')
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN tecnico t ON t.id = c.tecnico_id
            ORDER BY c.criado_em DESC
        ")->fetchAll(PDO::FETCH_NUM);

    } elseif ($tipo === 'pagamentos' && ($isMaster || true)) {
        $cabecalho = ['ID','Chamado','Cliente','Método','Valor','Status','Pago Em'];
        $dados = $pdo->query("
            SELECT p.id, p.chamado_id, cl.nome, p.metodo,
                   REPLACE(FORMAT(p.valor,2),',',''), p.status,
                   COALESCE(DATE_FORMAT(p.pago_em,'%d/%m/%Y %H:%i'),'—')
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            ORDER BY p.criado_em DESC
        ")->fetchAll(PDO::FETCH_NUM);

    } elseif ($tipo === 'clientes') {
        $cabecalho = ['ID','Nome','E-mail','Telefone','Gênero','Endereço','CEP','Desde'];
        $dados = $pdo->query("
            SELECT id, nome, email, telefone, genero, endereco, cep,
                   DATE_FORMAT(criado_em,'%d/%m/%Y')
            FROM cliente ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_NUM);

    } elseif ($tipo === 'prestadores') {
        $cabecalho = ['ID','Nome','E-mail','Especialidade','Telefone','Avaliação','Ativo','Status','Destaque'];
        $dados = $pdo->query("
            SELECT id, nome, COALESCE(email,'—'), especialidade, telefone,
                   FORMAT(avaliacao_media,2), ativo, status_cadastro,
                   IF(destaque=1,'Sim','Não')
            FROM tecnico ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_NUM);

    } elseif ($tipo === 'avaliacoes') {
        $cabecalho = ['ID','Chamado','Cliente','Técnico','Nota','Comentário','Data'];
        $dados = $pdo->query("
            SELECT a.id, a.chamado_id, cl.nome, t.nome, a.nota,
                   COALESCE(a.comentario,'—'),
                   DATE_FORMAT(a.criado_em,'%d/%m/%Y')
            FROM avaliacao a
            INNER JOIN cliente cl ON cl.id = a.cliente_id
            INNER JOIN tecnico t ON t.id = a.tecnico_id
            ORDER BY a.criado_em DESC
        ")->fetchAll(PDO::FETCH_NUM);
    }

    if ($cabecalho) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArq . '"');
        header('Cache-Control: no-cache');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, $cabecalho, ';');
        foreach ($dados as $row) { fputcsv($out, $row, ';'); }
        fclose($out);
        exit;
    }
}

$totClientes    = (int)$pdo->query("SELECT COUNT(*) FROM cliente")->fetchColumn();
$totPrestadores = (int)$pdo->query("SELECT COUNT(*) FROM tecnico")->fetchColumn();
$totChamados    = (int)$pdo->query("SELECT COUNT(*) FROM chamado")->fetchColumn();
$totConcluidos  = (int)$pdo->query("SELECT COUNT(*) FROM chamado WHERE status='Concluído'")->fetchColumn();
$faturamento    = (float)$pdo->query("SELECT COALESCE(SUM(valor),0) FROM pagamento WHERE status='Pago'")->fetchColumn();
$mediaGeral     = (float)$pdo->query("SELECT COALESCE(AVG(nota),0) FROM avaliacao")->fetchColumn();

$porCategoria = $pdo->query("SELECT categoria, COUNT(*) AS total FROM chamado GROUP BY categoria ORDER BY total DESC")->fetchAll();

$porMes = $pdo->query("
    SELECT DATE_FORMAT(criado_em,'%m/%Y') AS mes, COUNT(*) AS total
    FROM chamado
    WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY criado_em ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Relatórios - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Relatórios</h2>
  <p class="text-muted mb-4">Visualize indicadores e exporte dados da plataforma em formato CSV.</p>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="card shadow-sm border-0 text-center"><div class="card-body py-3">
        <small class="text-muted d-block">Clientes</small><h4 class="mb-0"><?= $totClientes ?></h4>
      </div></div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card shadow-sm border-0 text-center"><div class="card-body py-3">
        <small class="text-muted d-block">Prestadores</small><h4 class="mb-0"><?= $totPrestadores ?></h4>
      </div></div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card shadow-sm border-0 text-center"><div class="card-body py-3">
        <small class="text-muted d-block">Chamados</small><h4 class="mb-0"><?= $totChamados ?></h4>
      </div></div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card shadow-sm border-0 text-center"><div class="card-body py-3">
        <small class="text-muted d-block">Concluídos</small><h4 class="mb-0"><?= $totConcluidos ?></h4>
      </div></div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card shadow-sm border-0 text-center"><div class="card-body py-3">
        <small class="text-muted d-block">Faturamento</small><h5 class="mb-0 text-success">R$ <?= number_format($faturamento, 2, ',', '.') ?></h5>
      </div></div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card shadow-sm border-0 text-center"><div class="card-body py-3">
        <small class="text-muted d-block">Média Aval.</small><h4 class="mb-0"><?= number_format($mediaGeral, 1, ',', '.') ?>/5</h4>
      </div></div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="mb-3">Chamados por categoria</h6>
          <?php foreach ($porCategoria as $pc): ?>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="small"><?= htmlspecialchars($pc['categoria']) ?></span>
              <span class="badge bg-primary"><?= (int)$pc['total'] ?></span>
            </div>
            <div class="progress mb-2" style="height:6px;">
              <div class="progress-bar" style="width:<?= $totChamados > 0 ? round((int)$pc['total'] / $totChamados * 100) : 0 ?>%"></div>
            </div>
          <?php endforeach; ?>
          <?php if (!$porCategoria): ?><p class="text-muted small mb-0">Sem dados.</p><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="mb-3">Chamados por mês (últimos 6 meses)</h6>
          <?php if ($porMes):
            $maxMes = max(array_column($porMes, 'total'));
          ?>
            <?php foreach ($porMes as $pm): ?>
              <div class="d-flex justify-content-between mb-1">
                <span class="small"><?= htmlspecialchars($pm['mes']) ?></span>
                <span class="badge bg-warning text-dark"><?= (int)$pm['total'] ?></span>
              </div>
              <div class="progress mb-2" style="height:6px;">
                <div class="progress-bar bg-warning" style="width:<?= $maxMes > 0 ? round((int)$pm['total'] / $maxMes * 100) : 0 ?>%"></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted small">Sem dados no período.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Exportações CSV -->
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <h5 class="mb-3">Exportar CSV</h5>
      <div class="row g-3">
        <div class="col-md-4">
          <div class="card border">
            <div class="card-body">
              <h6>Chamados</h6>
              <p class="text-muted small mb-3">Todos os chamados com status, técnico e cliente.</p>
              <a href="relatorios.php?exportar=chamados" class="btn btn-warning btn-sm fw-semibold">Exportar CSV</a>
            </div>
          </div>
        </div>
        <?php if ($isMaster || true): ?>
        <div class="col-md-4">
          <div class="card border">
            <div class="card-body">
              <h6>Pagamentos</h6>
              <p class="text-muted small mb-3">Histórico completo de transações e métodos.</p>
              <a href="relatorios.php?exportar=pagamentos" class="btn btn-warning btn-sm fw-semibold">Exportar CSV</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div class="col-md-4">
          <div class="card border">
            <div class="card-body">
              <h6>Clientes</h6>
              <p class="text-muted small mb-3">Lista completa de clientes cadastrados.</p>
              <a href="relatorios.php?exportar=clientes" class="btn btn-warning btn-sm fw-semibold">Exportar CSV</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border">
            <div class="card-body">
              <h6>Prestadores</h6>
              <p class="text-muted small mb-3">Lista de prestadores com avaliação e status.</p>
              <a href="relatorios.php?exportar=prestadores" class="btn btn-warning btn-sm fw-semibold">Exportar CSV</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border">
            <div class="card-body">
              <h6>Avaliações</h6>
              <p class="text-muted small mb-3">Todas as avaliações enviadas pelos clientes.</p>
              <a href="relatorios.php?exportar=avaliacoes" class="btn btn-warning btn-sm fw-semibold">Exportar CSV</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
