<?php
session_start();
$paginaAtiva = 'categorias';
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
    if ($acao === 'criar') {
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ativo     = isset($_POST['ativo']) ? 1 : 0;
        if ($nome === '') { $erro = 'O nome da categoria é obrigatório.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO categoria (nome, descricao, ativo) VALUES (?,?,?)')->execute([$nome, $descricao ?: null, $ativo]);
                $mensagem = 'Categoria criada com sucesso.';
            } catch (PDOException $e) { $erro = 'Nome já cadastrado ou erro ao salvar.'; }
        }
    } elseif ($acao === 'editar') {
        $id        = (int)($_POST['id'] ?? 0);
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ativo     = isset($_POST['ativo']) ? 1 : 0;
        if ($id <= 0 || $nome === '') { $erro = 'Dados inválidos.'; }
        else {
            try {
                $pdo->prepare('UPDATE categoria SET nome=?, descricao=?, ativo=? WHERE id=?')->execute([$nome, $descricao ?: null, $ativo, $id]);
                $mensagem = 'Categoria atualizada.';
            } catch (PDOException $e) { $erro = 'Nome já utilizado ou erro ao atualizar.'; }
        }
    } elseif ($acao === 'excluir' && $isMaster) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare('DELETE FROM categoria WHERE id=?');
            $del->execute([$id]);
            $mensagem = $del->rowCount() > 0 ? 'Categoria excluída.' : 'Categoria não encontrada.';
        }
    }
}

$categorias = $pdo->query('SELECT * FROM categoria ORDER BY nome ASC')->fetchAll();
$editando = null;
if (isset($_GET['editar'])) {
    $eid = (int)$_GET['editar'];
    foreach ($categorias as $c) { if ((int)$c['id'] === $eid) { $editando = $c; break; } }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Categorias - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .admin-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}
    .form-label{font-size:.85rem;font-weight:600;margin-bottom:.35rem}
    [data-theme="dark"] .admin-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>🏷 Categorias</h1>
      <p>Gerencie as categorias de serviço disponíveis na plataforma.</p>
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

  <div class="row g-4">
    <!-- Formulário -->
    <div class="col-lg-4">
      <div class="admin-card">
        <div class="secao-titulo">
          <span><?= $editando ? '✏️' : '➕' ?></span>
          <?= $editando ? 'Editar Categoria' : 'Nova Categoria' ?>
        </div>
        <form method="post">
          <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
          <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="nome" class="form-control" maxlength="100" required
              value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" placeholder="Ex: Elétrica, Hidráulica…">
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-control" rows="3" maxlength="255"
              placeholder="Descrição opcional da categoria"><?= htmlspecialchars($editando['descricao'] ?? '') ?></textarea>
          </div>
          <div class="mb-4">
            <div class="form-check form-switch">
              <input type="checkbox" name="ativo" id="chk_ativo" class="form-check-input" value="1"
                <?= (!$editando || $editando['ativo']) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="chk_ativo" style="font-size:.85rem;">Categoria ativa</label>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-bold px-4">
              <?= $editando ? 'Salvar alterações' : 'Criar categoria' ?>
            </button>
            <?php if ($editando): ?>
              <a href="categorias.php" class="btn btn-outline-secondary">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabela -->
    <div class="col-lg-8">
      <div class="admin-card">
        <div class="secao-titulo">
          <span>📋</span>
          Categorias cadastradas
          <span class="badge ms-auto" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem;"><?= count($categorias) ?></span>
        </div>
        <?php if (!$categorias): ?>
          <div class="text-center py-5 text-muted">
            <div style="font-size:2.5rem;margin-bottom:.5rem;">📂</div>
            Nenhuma categoria cadastrada ainda.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle table-sm mb-0">
              <thead class="table-primary">
                <tr><th>#</th><th>Nome</th><th>Descrição</th><th>Status</th><th></th></tr>
              </thead>
              <tbody>
              <?php foreach ($categorias as $c): ?>
                <tr>
                  <td class="text-muted" style="font-size:.82rem;"><?= (int)$c['id'] ?></td>
                  <td class="fw-semibold"><?= htmlspecialchars($c['nome']) ?></td>
                  <td class="text-muted" style="font-size:.83rem;"><?= htmlspecialchars(mb_strimwidth($c['descricao'] ?? '—', 0, 60, '...')) ?></td>
                  <td>
                    <span class="badge" style="font-size:.72rem;background:<?= $c['ativo'] ? '#dcfce7' : '#f3f4f6' ?>;color:<?= $c['ativo'] ? '#16a34a' : '#6b7280' ?>;">
                      <?= $c['ativo'] ? '✔ Ativa' : 'Inativa' ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="categorias.php?editar=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:.78rem;">Editar</a>
                      <?php if ($isMaster): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('Excluir esta categoria?');">
                          <input type="hidden" name="acao" value="excluir">
                          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                          <button class="btn btn-sm btn-outline-danger" style="font-size:.78rem;">Excluir</button>
                        </form>
                      <?php endif; ?>
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
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
