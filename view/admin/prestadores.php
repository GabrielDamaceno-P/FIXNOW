<?php
session_start();
$paginaAtiva = 'prestadores';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

// acesso: somente admin

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $tid  = (int)($_POST['tecnico_id'] ?? 0);
    if ($tid > 0) {
        if ($acao === 'aprovar') {
            $up = $pdo->prepare("UPDATE tecnico SET ativo=1, status_cadastro='Aprovado' WHERE id=? AND status_cadastro='Pendente'");
            $up->execute([$tid]);
            if ($up->rowCount() > 0) {
                fixnow_notificar_prestador($pdo, $tid, 'Seu cadastro foi aprovado pela Fix Now! Você já pode acessar o painel e aceitar chamados.');
                $mensagem = 'Prestador aprovado.';
            } else { $erro = 'Não foi possível aprovar (cadastro já processado).'; }
        } elseif ($acao === 'recusar') {
            $up = $pdo->prepare("UPDATE tecnico SET ativo=0, status_cadastro='Recusado' WHERE id=? AND status_cadastro='Pendente'");
            $up->execute([$tid]);
            if ($up->rowCount() > 0) {
                fixnow_notificar_prestador($pdo, $tid, 'Seu cadastro foi recusado pela Fix Now. Entre em contato com o suporte.');
                $mensagem = 'Cadastro recusado.';
            } else { $erro = 'Não foi possível recusar (cadastro já processado).'; }
        } elseif ($acao === 'excluir' && $isMaster) {
            $pdo->prepare('DELETE FROM tecnico WHERE id=?')->execute([$tid]);
            $mensagem = 'Prestador excluído.';
        }
    }
}

$filtro       = trim($_GET['busca'] ?? '');
$filtroStatus = $_GET['status'] ?? '';
$statusOpcoes = ['Pendente', 'Aprovado', 'Recusado'];

$where = [];
$params = [];
if ($filtro !== '') {
    $where[] = '(t.nome LIKE ? OR t.email LIKE ?)';
    $like = '%' . $filtro . '%';
    $params[] = $like; $params[] = $like;
}
if (in_array($filtroStatus, $statusOpcoes, true)) {
    $where[] = 't.status_cadastro = ?';
    $params[] = $filtroStatus;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT t.id, t.nome, t.email, t.telefone, t.genero, t.avaliacao_media,
           t.ativo, t.status_cadastro, t.destaque, t.criado_em, t.documento_path,
           (SELECT GROUP_CONCAT(cat.nome ORDER BY cat.nome SEPARATOR ', ')
            FROM servico s INNER JOIN categoria cat ON cat.id=s.categoria_id
            WHERE s.tecnico_id=t.id AND s.ativo=1) AS categorias_servico
    FROM tecnico t $whereSQL
    ORDER BY t.status_cadastro ASC, t.nome ASC
");
$stmt->execute($params);
$tecnicos = $stmt->fetchAll();
$pendentesCount = count(array_filter($tecnicos, fn($t) => $t['status_cadastro'] === 'Pendente'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Prestadores - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Prestadores</h2>
  <p class="text-muted mb-4">Visualize, aprove, recuse e remova prestadores da plataforma.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <?php if ($pendentesCount > 0 && $filtroStatus !== 'Pendente'): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
      <strong><?= $pendentesCount ?> prestador<?= $pendentesCount > 1 ? 'es' : '' ?> aguardando aprovação.</strong>
      <a href="prestadores.php?status=Pendente" class="btn btn-sm btn-warning ms-2">Ver pendentes</a>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0">Prestadores <span class="badge bg-secondary"><?= count($tecnicos) ?></span></h5>
        <form method="get" class="d-flex gap-2 flex-wrap">
          <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome ou e-mail..."
            value="<?= htmlspecialchars($filtro) ?>" style="width:180px;">
          <select name="status" class="form-select form-select-sm" style="width:140px;">
            <option value="">Todos os status</option>
            <?php foreach ($statusOpcoes as $st): ?>
              <option value="<?= $st ?>" <?= $filtroStatus===$st?'selected':'' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-secondary">Buscar</button>
          <?php if ($filtro || $filtroStatus): ?>
            <a href="prestadores.php" class="btn btn-sm btn-outline-danger">Limpar</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if (!$tecnicos): ?>
        <p class="text-muted mb-0">Nenhum prestador encontrado.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle table-sm">
            <thead class="table-primary">
              <tr>
                <th>#</th><th>Nome</th><th>E-mail</th><th>Gênero</th><th>Categorias</th><th>Telefone</th>
                <th>Avaliação</th><th>Status</th><th>Cadastrado em</th><th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($tecnicos as $t):
              $badgeSt = match($t['status_cadastro']) { 'Aprovado'=>'success','Pendente'=>'warning text-dark',default=>'danger' };
            ?>
              <tr>
                <td><?= (int)$t['id'] ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($t['nome']) ?></td>
                <td><?= htmlspecialchars($t['email'] ?? '—') ?></td>
                <td>
                  <?php
                    $genBadge = match($t['genero'] ?? '') {
                      'Feminino'  => 'bg-pink text-white',
                      'Masculino' => 'bg-primary',
                      default     => 'bg-secondary'
                    };
                  ?>
                  <span class="badge <?= $genBadge ?>"><?= htmlspecialchars($t['genero'] ?? '—') ?></span>
                </td>
                <td><?= htmlspecialchars($t['categorias_servico'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['telefone']) ?></td>
                <td>⭐ <?= number_format((float)$t['avaliacao_media'], 1, ',', '.') ?></td>
                <td><span class="badge bg-<?= $badgeSt ?>"><?= htmlspecialchars($t['status_cadastro']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($t['criado_em'])) ?></td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <?php if ($t['status_cadastro'] === 'Pendente'): ?>
                      <?php if (!empty($t['documento_path'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                          data-bs-toggle="modal" data-bs-target="#modalDoc"
                          data-nome="<?= htmlspecialchars($t['nome'], ENT_QUOTES) ?>"
                          data-genero="<?= htmlspecialchars($t['genero'] ?? '', ENT_QUOTES) ?>"
                          data-doc="<?= htmlspecialchars('../../' . $t['documento_path'], ENT_QUOTES) ?>">
                          <i class="bi bi-file-earmark-person me-1"></i>Ver doc
                        </button>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">Sem documento</span>
                      <?php endif; ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="acao" value="aprovar">
                        <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                        <button class="btn btn-sm btn-success">Aprovar</button>
                      </form>
                      <form method="post" class="d-inline" onsubmit="return confirm('Recusar este cadastro?');">
                        <input type="hidden" name="acao" value="recusar">
                        <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Recusar</button>
                      </form>
                    <?php endif; ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir este prestador e todos os seus dados?');">
                      <input type="hidden" name="acao" value="excluir">
                      <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger">Excluir</button>
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
  </div>
</main>

<!-- Modal documento -->
<div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0" id="modalDocNome"></h5>
          <small class="text-muted">Gênero declarado: <span id="modalDocGenero" class="fw-semibold"></span></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-2">
        <img id="modalDocImg" src="" alt="Documento" class="img-fluid rounded" style="max-height:75vh;">
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
document.getElementById('modalDoc').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('modalDocNome').textContent = btn.dataset.nome;
  document.getElementById('modalDocGenero').textContent = btn.dataset.genero;
  document.getElementById('modalDocImg').src = btn.dataset.doc;
});
</script>
<style>.bg-pink { background-color: #e91e8c !important; }</style>
</body>
</html>
