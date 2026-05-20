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

$exportacoes = [
    ['tipo'=>'chamados',    'emoji'=>'📋', 'titulo'=>'Chamados',    'desc'=>'Todos os chamados com status, técnico e cliente.'],
    ['tipo'=>'pagamentos',  'emoji'=>'💳', 'titulo'=>'Pagamentos',  'desc'=>'Histórico completo de transações e métodos.'],
    ['tipo'=>'clientes',    'emoji'=>'👥', 'titulo'=>'Clientes',    'desc'=>'Lista completa de clientes cadastrados.'],
    ['tipo'=>'prestadores', 'emoji'=>'🔧', 'titulo'=>'Prestadores', 'desc'=>'Lista de prestadores com avaliação e status.'],
    ['tipo'=>'avaliacoes',  'emoji'=>'⭐', 'titulo'=>'Avaliações',  'desc'=>'Todas as avaliações enviadas pelos clientes.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Relatórios - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .admin-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}
    .kpi-rel{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1rem 1.2rem;display:flex;align-items:center;gap:.8rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .kpi-rel-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
    .kpi-rel-val{font-size:1.35rem;font-weight:800;line-height:1.1}
    .kpi-rel-lbl{font-size:.76rem;color:#667085}
    .export-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.1rem 1.2rem;transition:box-shadow .18s,transform .18s;box-shadow:0 2px 8px rgba(13,27,61,.04)}
    .export-card:hover{box-shadow:0 8px 20px rgba(13,27,61,.1);transform:translateY(-2px)}
    .export-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;background:#f0f3fa;flex-shrink:0}
    .bar-item{margin-bottom:.6rem}
    .bar-item .bar-label{display:flex;justify-content:space-between;margin-bottom:.25rem;font-size:.82rem}
    [data-theme="dark"] .admin-card,[data-theme="dark"] .kpi-rel,[data-theme="dark"] .export-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .kpi-rel-lbl{color:#8090b0}
    [data-theme="dark"] .export-icon{background:#2e3650}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>📊 Relatórios</h1>
      <p>Visualize indicadores da plataforma e exporte dados em formato CSV.</p>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-rel">
        <div class="kpi-rel-icon" style="background:#eff6ff;">👥</div>
        <div>
          <div class="kpi-rel-val" style="color:#1d4ed8;"><?= $totClientes ?></div>
          <div class="kpi-rel-lbl">Clientes cadastrados</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-rel">
        <div class="kpi-rel-icon" style="background:#f0fdf4;">🔧</div>
        <div>
          <div class="kpi-rel-val" style="color:#16a34a;"><?= $totPrestadores ?></div>
          <div class="kpi-rel-lbl">Prestadores</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-rel">
        <div class="kpi-rel-icon" style="background:#fff7ed;">📋</div>
        <div>
          <div class="kpi-rel-val" style="color:#ea580c;"><?= $totChamados ?></div>
          <div class="kpi-rel-lbl">Total de chamados</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-rel">
        <div class="kpi-rel-icon" style="background:#f0fdf4;">✅</div>
        <div>
          <div class="kpi-rel-val" style="color:#16a34a;"><?= $totConcluidos ?></div>
          <div class="kpi-rel-lbl">Chamados concluídos</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-rel">
        <div class="kpi-rel-icon" style="background:#f0fdf4;">💵</div>
        <div>
          <div class="kpi-rel-val" style="color:#16a34a;font-size:1.1rem;">R$ <?= number_format($faturamento, 2, ',', '.') ?></div>
          <div class="kpi-rel-lbl">Faturamento (pagos)</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="kpi-rel">
        <div class="kpi-rel-icon" style="background:#fefce8;">⭐</div>
        <div>
          <div class="kpi-rel-val" style="color:#ca8a04;"><?= number_format($mediaGeral, 1, ',', '.') ?></div>
          <div class="kpi-rel-lbl">Avaliação média / 5</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficos de barras -->
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="admin-card">
        <div class="secao-titulo"><span>📂</span> Chamados por categoria</div>
        <?php if (!$porCategoria): ?>
          <p class="text-muted small mb-0">Sem dados.</p>
        <?php else: ?>
          <?php foreach ($porCategoria as $pc): ?>
            <div class="bar-item">
              <div class="bar-label">
                <span><?= htmlspecialchars($pc['categoria']) ?></span>
                <span class="fw-bold" style="color:#0d1b3d;"><?= (int)$pc['total'] ?></span>
              </div>
              <div class="progress" style="height:8px;border-radius:6px;">
                <div class="progress-bar" style="width:<?= $totChamados > 0 ? round((int)$pc['total'] / $totChamados * 100) : 0 ?>%;background:linear-gradient(90deg,#0d1b3d,#1a2b63);border-radius:6px;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-6">
      <div class="admin-card">
        <div class="secao-titulo"><span>📅</span> Chamados por mês (últimos 6 meses)</div>
        <?php if ($porMes):
          $maxMes = max(array_column($porMes, 'total'));
        ?>
          <?php foreach ($porMes as $pm): ?>
            <div class="bar-item">
              <div class="bar-label">
                <span><?= htmlspecialchars($pm['mes']) ?></span>
                <span class="fw-bold" style="color:#c95e00;"><?= (int)$pm['total'] ?></span>
              </div>
              <div class="progress" style="height:8px;border-radius:6px;">
                <div class="progress-bar" style="width:<?= $maxMes > 0 ? round((int)$pm['total'] / $maxMes * 100) : 0 ?>%;background:linear-gradient(90deg,#c95e00,#f97316);border-radius:6px;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted small">Sem dados no período.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Exportações CSV -->
  <div class="admin-card">
    <div class="secao-titulo"><span>⬇️</span> Exportar dados CSV</div>
    <div class="row g-3">
      <?php foreach ($exportacoes as $exp): ?>
      <div class="col-sm-6 col-md-4">
        <div class="export-card d-flex align-items-center gap-3">
          <div class="export-icon"><?= $exp['emoji'] ?></div>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold" style="font-size:.9rem;color:#0d1b3d;"><?= $exp['titulo'] ?></div>
            <div class="text-muted" style="font-size:.78rem;margin-top:.1rem;"><?= $exp['desc'] ?></div>
          </div>
          <a href="relatorios.php?exportar=<?= $exp['tipo'] ?>" class="btn btn-sm btn-warning fw-semibold flex-shrink-0">CSV</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
