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
                fixnow_notificar_prestador($pdo, $tid,
                    'Parabens! Voce foi selecionado como Prestador em Destaque pela Fix Now. Seus servicos aparecem em primeiro lugar no catalogo e na pagina inicial para os clientes.');
                $mensagem = 'Prestador marcado como destaque e notificado.';
            } else {
                fixnow_notificar_prestador($pdo, $tid,
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
    .card-destaque { border: 2px solid #ffc107 !important; box-shadow: 0 6px 20px rgba(255,152,0,.2) !important; }
    .metric-box { background: var(--fix-surface, #f8f9fa); border-radius: 8px; padding: .5rem .75rem; text-align: center; }
    .metric-box .val { font-size: 1.15rem; font-weight: 700; line-height: 1; }
    .metric-box .lbl { font-size: .68rem; color: #6c757d; margin-top: 2px; }
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Definir Destaques</h2>
  <p class="text-muted mb-3">Analise as métricas de cada prestador e decida quem merece o selo de destaque.</p>

  <!-- Benefícios do destaque -->
  <div class="alert alert-warning border-0 mb-4" style="background:rgba(255,193,7,.12)">
    <div class="fw-semibold mb-1"><i class="bi bi-star-fill text-warning me-1"></i>Benefícios do Prestador em Destaque</div>
    <ul class="mb-0 small ps-3">
      <li>Aparece <strong>primeiro</strong> no catálogo e no dashboard dos clientes</li>
      <li>Exibe o badge <strong>★ Destaque Fix Now</strong> no card de apresentação</li>
      <li>Destaque visual com borda dourada nos cards</li>
      <li>Comissão reduzida: <strong>15%</strong> (em vez de 20%) sobre pagamentos</li>
      <li>Recebe notificação ao ser promovido</li>
    </ul>
  </div>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="destaques.php?filtro=aprovados" class="btn btn-sm <?= $filtro === 'aprovados' ? 'btn-primary' : 'btn-outline-primary' ?>">Aprovados</a>
    <a href="destaques.php?filtro=todos"     class="btn btn-sm <?= $filtro === 'todos'     ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
    <a href="destaques.php?filtro=destaque"  class="btn btn-sm <?= $filtro === 'destaque'  ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">★ Em Destaque</a>
  </div>

  <?php if (!$tecnicos): ?>
    <div class="alert alert-info">Nenhum prestador encontrado.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($tecnicos as $t):
        $isDestaque = (bool)$t['destaque'];
        $concluidos = (int)$t['total_concluidos'];
        $chamados   = (int)$t['total_chamados'];
        $receita    = (float)$t['receita_total'];
        $media      = (float)$t['avaliacao_media'];
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 h-100 <?= $isDestaque ? 'card-destaque' : 'shadow-sm' ?>">
          <div class="card-body d-flex flex-column gap-3">

            <!-- Cabeçalho -->
            <div class="d-flex align-items-center gap-3">
              <?php
                $fotoPath = __DIR__ . '/../../' . ltrim($t['foto_perfil'] ?? '', '/');
                if ($t['foto_perfil'] && file_exists($fotoPath)):
              ?>
                <img src="../../<?= htmlspecialchars($t['foto_perfil']) ?>" alt=""
                     width="52" height="52" class="rounded-circle border" style="object-fit:cover;flex-shrink:0">
              <?php else: ?>
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                     style="width:52px;height:52px;">
                  <?= htmlspecialchars(mb_substr($t['nome'], 0, 1)) ?>
                </div>
              <?php endif; ?>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate"><?= htmlspecialchars($t['nome']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($t['especialidade'] ?? '—') ?></div>
                <div class="d-flex gap-1 mt-1 flex-wrap">
                  <?php if ($isDestaque): ?>
                    <span class="badge bg-warning text-dark">★ Destaque</span>
                  <?php endif; ?>
                  <span class="badge bg-light text-dark border"><?= htmlspecialchars($t['genero'] ?? '—') ?></span>
                </div>
              </div>
            </div>

            <!-- Métricas -->
            <div class="row g-2">
              <div class="col-4">
                <div class="metric-box">
                  <div class="val text-success"><?= $concluidos ?></div>
                  <div class="lbl">Concluídos</div>
                </div>
              </div>
              <div class="col-4">
                <div class="metric-box">
                  <div class="val text-warning">⭐ <?= number_format($media, 1, ',', '.') ?></div>
                  <div class="lbl">Avaliação</div>
                </div>
              </div>
              <div class="col-4">
                <div class="metric-box">
                  <div class="val text-primary" style="font-size:.95rem">R$ <?= number_format($receita, 0, ',', '.') ?></div>
                  <div class="lbl">Receita paga</div>
                </div>
              </div>
            </div>

            <!-- Barra de progresso de chamados -->
            <?php if ($chamados > 0): ?>
            <div>
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Taxa de conclusão</span>
                <span><?= $chamados > 0 ? round($concluidos / $chamados * 100) : 0 ?>%</span>
              </div>
              <div class="progress" style="height:6px">
                <div class="progress-bar bg-success" style="width:<?= $chamados > 0 ? round($concluidos / $chamados * 100) : 0 ?>%"></div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Ação -->
            <div class="mt-auto">
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
