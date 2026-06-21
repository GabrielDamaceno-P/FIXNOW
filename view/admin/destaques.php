<?php
session_start();
$paginaAtiva = 'destaques';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $tid  = (int)($_POST['tecnico_id'] ?? 0);

    if ($tid > 0 && in_array($acao, ['destacar', 'remover_destaque'], true)) {
        $val = $acao === 'destacar' ? 1 : 0;
        $up  = $pdo->prepare("UPDATE tecnico SET destaque = ? WHERE id = ?");
        $up->execute([$val, $tid]);
        if ($up->rowCount() > 0) {
            if ($acao === 'destacar') {
                fixnow_notificar_prestador($tid,
                    'Parabens! Voce foi selecionado como Prestador em Destaque pela Fix Now. Seus servicos aparecem em primeiro lugar no catalogo e na pagina inicial para os clientes.');
                $mensagem = 'Prestador marcado como destaque e notificado.';
            } else {
                fixnow_notificar_prestador($tid,
                    'Seu status de Prestador em Destaque foi removido pela plataforma Fix Now.');
                $mensagem = 'Destaque removido. Prestador foi notificado.';
            }
        }
    }
}

$filtro = $_GET['filtro'] ?? 'aprovados';
$where  = $filtro === 'destaque'  ? "AND t.destaque = 1"
        : ($filtro === 'todos'    ? '' : "AND t.ativo = 1 AND t.status_cadastro = 'Aprovado'");

$tecnicos = $pdo->query("
    SELECT t.id, t.nome, t.email, t.especialidade, t.avaliacao_media,
           t.ativo, t.status_cadastro, t.destaque, t.foto_perfil, t.genero,
           COUNT(DISTINCT CASE WHEN c.status = 'Concluido' THEN c.id END) AS total_concluidos,
           COUNT(DISTINCT c.id)                                             AS total_chamados,
           COALESCE(SUM(CASE WHEN p.status = 'Pago' THEN p.valor ELSE 0 END), 0) AS receita_total
    FROM tecnico t
    LEFT JOIN chamado c   ON c.tecnico_id = t.id
    LEFT JOIN pagamento p ON p.chamado_id = c.id
    WHERE t.status_cadastro = 'Aprovado' $where
    GROUP BY t.id
    ORDER BY t.destaque DESC, receita_total DESC, total_concluidos DESC, t.avaliacao_media DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Destaques - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .dest-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;box-shadow:0 3px 10px rgba(13,27,61,.05);transition:transform .18s,box-shadow .18s;overflow:hidden}
    .dest-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(13,27,61,.1)}
    .dest-card.em-destaque{border-color:#fbbf24;box-shadow:0 4px 16px rgba(251,191,36,.25)}
    .dest-card-header{padding:.85rem 1rem;display:flex;align-items:center;gap:.75rem;border-bottom:1px solid #f0f3fa}
    .dest-card-body{padding:.85rem 1rem}
    .dest-card-footer{padding:.7rem 1rem;border-top:1px solid #f0f3fa}
    .metric-box{background:#f8fafc;border-radius:10px;padding:.55rem .65rem;text-align:center;border:1px solid #f0f3fa}
    .metric-box .val{font-size:1.05rem;font-weight:800;line-height:1.1}
    .metric-box .lbl{font-size:.68rem;color:#6b7280;margin-top:2px}
    .dest-avatar{width:48px;height:48px;border-radius:50%;object-fit:cover;flex-shrink:0}
    .dest-avatar-init{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#0d1b3d,#1a2b63);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.1rem;flex-shrink:0}
    [data-theme="dark"] .dest-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .dest-card.em-destaque{border-color:#fbbf24}
    [data-theme="dark"] .dest-card-header,[data-theme="dark"] .dest-card-footer{border-color:#2e3650}
    [data-theme="dark"] .metric-box{background:#252e45;border-color:#2e3650}
    [data-theme="dark"] .metric-box .lbl{color:#8090b0}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>⭐ Prestadores em Destaque</h1>
      <p>Analise as métricas de cada prestador e gerencie o selo de destaque.</p>
    </div>
  </div>

  <!-- Benefícios -->
  <div class="d-flex gap-3 align-items-start p-3 mb-4" style="background:rgba(251,191,36,.1);border:1.5px solid #fde68a;border-radius:14px;">
    <span style="font-size:1.5rem;flex-shrink:0;">⭐</span>
    <div>
      <div class="fw-bold mb-1" style="color:#92400e;">Benefícios do Prestador em Destaque</div>
      <ul class="mb-0 ps-3" style="font-size:.83rem;color:#78350f;line-height:1.7;">
        <li>Aparece <strong>primeiro</strong> no catálogo e no dashboard dos clientes</li>
        <li>Exibe o badge <strong>★ Destaque Fix Now</strong> no card de apresentação</li>
        <li>Borda dourada de destaque nos cards</li>
        <li>Comissão reduzida: <strong>15%</strong> (em vez de 20%) sobre pagamentos</li>
        <li>Recebe notificação ao ser promovido ou removido</li>
      </ul>
    </div>
  </div>

  <?php if ($mensagem): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= htmlspecialchars($mensagem) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($erro) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
    <span class="text-muted" style="font-size:.84rem;">Exibir:</span>
    <a href="destaques.php?filtro=aprovados" class="btn btn-sm <?= $filtro === 'aprovados' ? 'btn-primary' : 'btn-outline-primary' ?>">Aprovados</a>
    <a href="destaques.php?filtro=todos"     class="btn btn-sm <?= $filtro === 'todos'     ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
    <a href="destaques.php?filtro=destaque"  class="btn btn-sm <?= $filtro === 'destaque'  ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">★ Em Destaque</a>
    <span class="ms-auto text-muted" style="font-size:.82rem;"><?= count($tecnicos) ?> prestador<?= count($tecnicos) !== 1 ? 'es' : '' ?></span>
  </div>

  <?php if (!$tecnicos): ?>
    <div class="text-center py-5 text-muted">
      <div style="font-size:3rem;margin-bottom:.5rem;">🔍</div>
      Nenhum prestador encontrado com os filtros selecionados.
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($tecnicos as $t):
        $isDestaque = (bool)$t['destaque'];
        $concluidos = (int)$t['total_concluidos'];
        $chamados   = (int)$t['total_chamados'];
        $receita    = (float)$t['receita_total'];
        $media      = (float)$t['avaliacao_media'];
        $taxaConc   = $chamados > 0 ? round($concluidos / $chamados * 100) : 0;
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="dest-card <?= $isDestaque ? 'em-destaque' : '' ?>">

          <div class="dest-card-header">
            <?php
              $fotoPath = __DIR__ . '/../../' . ltrim($t['foto_perfil'] ?? '', '/');
              if ($t['foto_perfil'] && file_exists($fotoPath)):
            ?>
              <img src="../../<?= htmlspecialchars($t['foto_perfil']) ?>" alt=""
                   class="dest-avatar border">
            <?php else: ?>
              <div class="dest-avatar-init"><?= htmlspecialchars(mb_substr($t['nome'], 0, 1)) ?></div>
            <?php endif; ?>
            <div class="flex-grow-1 min-w-0">
              <div class="fw-bold text-truncate" style="font-size:.92rem;"><?= htmlspecialchars($t['nome']) ?></div>
              <div class="text-muted text-truncate" style="font-size:.78rem;"><?= htmlspecialchars($t['especialidade'] ?? '—') ?></div>
              <div class="d-flex gap-1 mt-1 flex-wrap">
                <?php if ($isDestaque): ?>
                  <span class="badge" style="background:#fef9c3;color:#713f12;font-size:.68rem;">★ Destaque</span>
                <?php endif; ?>
                <span class="badge" style="background:#f3f4f6;color:#374151;font-size:.67rem;"><?= htmlspecialchars($t['genero'] ?? '—') ?></span>
              </div>
            </div>
          </div>

          <div class="dest-card-body">
            <div class="row g-2 mb-3">
              <div class="col-4">
                <div class="metric-box">
                  <div class="val text-success"><?= $concluidos ?></div>
                  <div class="lbl">Concluídos</div>
                </div>
              </div>
              <div class="col-4">
                <div class="metric-box">
                  <div class="val" style="color:#ca8a04;">⭐ <?= number_format($media, 1, ',', '.') ?></div>
                  <div class="lbl">Avaliação</div>
                </div>
              </div>
              <div class="col-4">
                <div class="metric-box">
                  <div class="val" style="color:#0d1b3d;font-size:.88rem;">R$ <?= number_format($receita, 0, ',', '.') ?></div>
                  <div class="lbl">Receita paga</div>
                </div>
              </div>
            </div>

            <?php if ($chamados > 0): ?>
            <div>
              <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;color:#6b7280;">
                <span>Taxa de conclusão</span>
                <span class="fw-bold"><?= $taxaConc ?>%</span>
              </div>
              <div class="progress" style="height:6px;border-radius:6px;">
                <div class="progress-bar bg-success" style="width:<?= $taxaConc ?>%;border-radius:6px;"></div>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="dest-card-footer">
            <?php if ($isDestaque): ?>
              <form method="post">
                <input type="hidden" name="acao" value="remover_destaque">
                <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger w-100">Remover destaque</button>
              </form>
            <?php else: ?>
              <form method="post">
                <input type="hidden" name="acao" value="destacar">
                <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-warning fw-semibold w-100">★ Marcar como destaque</button>
              </form>
            <?php endif; ?>
          </div>

        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
