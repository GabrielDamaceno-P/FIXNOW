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
                    'Parabéns! Você foi selecionado como Prestador em Destaque pela Fix Now. Seus serviços aparecem em primeiro lugar no catálogo e na página inicial para os clientes.');
                $mensagem = 'Prestador marcado como destaque e notificado.';
            } else {
                fixnow_notificar_prestador($pdo, $tid,
                    'Seu status de Prestador em Destaque foi removido pela plataforma Fix Now.');
                $mensagem = 'Destaque removido. Prestador foi notificado.';
            }
        }
    }
}

$filtro = $_GET['filtro'] ?? 'todos';
$where  = $filtro === 'destaque'  ? "WHERE t.destaque = 1 AND t.ativo = 1 AND t.status_cadastro = 'Aprovado'"
        : ($filtro === 'aprovados' ? "WHERE t.ativo = 1 AND t.status_cadastro = 'Aprovado'" : '');

$tecnicos = $pdo->query("
    SELECT t.id, t.nome, t.email, t.especialidade, t.avaliacao_media,
           t.ativo, t.status_cadastro, t.destaque, t.foto_perfil
    FROM tecnico t
    $where
    ORDER BY t.destaque DESC, t.avaliacao_media DESC, t.nome ASC
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
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Definir Destaques</h2>
  <p class="text-muted mb-4">Marque prestadores para aparecerem em destaque na plataforma para os clientes.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="destaques.php?filtro=todos"     class="btn btn-sm <?= $filtro === 'todos'     ? 'btn-primary'         : 'btn-outline-primary' ?>">Todos</a>
    <a href="destaques.php?filtro=aprovados" class="btn btn-sm <?= $filtro === 'aprovados' ? 'btn-primary'         : 'btn-outline-primary' ?>">Aprovados</a>
    <a href="destaques.php?filtro=destaque"  class="btn btn-sm <?= $filtro === 'destaque'  ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">Em Destaque</a>
  </div>

  <?php if (!$tecnicos): ?>
    <div class="alert alert-info">Nenhum prestador encontrado para o filtro selecionado.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($tecnicos as $t): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card shadow-sm border-0 h-100 <?= $t['destaque'] ? 'border-warning border' : '' ?>">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3 mb-3">
                <?php
                $fotoPath = __DIR__ . '/../../' . ltrim($t['foto_perfil'] ?? '', '/');
                if ($t['foto_perfil'] && file_exists($fotoPath)):
                ?>
                  <img src="../../<?= htmlspecialchars($t['foto_perfil']) ?>" alt=""
                       width="52" height="52" class="rounded-circle border" style="object-fit:cover;">
                <?php else: ?>
                  <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
                       style="width:52px;height:52px;flex-shrink:0;">
                    <?= htmlspecialchars(mb_substr($t['nome'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($t['nome']) ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($t['especialidade']) ?></div>
                </div>
              </div>
              <div class="d-flex gap-2 mb-3 flex-wrap">
                <span class="badge <?= $t['ativo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $t['ativo'] ? 'Ativo' : 'Inativo' ?></span>
                <span class="badge bg-light text-dark border">⭐ <?= number_format((float)$t['avaliacao_media'], 1, ',', '.') ?></span>
                <?php if ($t['destaque']): ?>
                  <span class="badge bg-warning text-dark">Destaque</span>
                <?php endif; ?>
                <span class="badge bg-info text-dark"><?= htmlspecialchars($t['status_cadastro']) ?></span>
              </div>
              <?php if ($t['destaque']): ?>
                <form method="post">
                  <input type="hidden" name="acao" value="remover_destaque">
                  <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger w-100">Remover destaque</button>
                </form>
              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="acao" value="destacar">
                  <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-warning fw-semibold w-100">Marcar como destaque</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?= date('Y') ?> Fix Now.</small></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
