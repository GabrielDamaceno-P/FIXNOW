<?php
session_start();
$paginaAtiva = 'categorias';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

if (!$isMaster && !$isOperacoes) { header('Location: painelAdmin.php'); exit; }

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
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Manter Categorias</h2>
  <p class="text-muted mb-4">Gerencie as categorias de serviço disponíveis na plataforma.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5><?= $editando ? 'Editar Categoria' : 'Nova Categoria' ?></h5>
          <form method="post">
            <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
            <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>
            <div class="mb-3">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" maxlength="100" required
                value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" class="form-control" rows="2" maxlength="255"><?= htmlspecialchars($editando['descricao'] ?? '') ?></textarea>
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" name="ativo" id="chk_ativo" class="form-check-input" value="1"
                <?= (!$editando || $editando['ativo']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="chk_ativo">Ativa</label>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-warning fw-semibold"><?= $editando ? 'Salvar' : 'Criar' ?></button>
              <?php if ($editando): ?><a href="categorias.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3">Categorias cadastradas</h5>
          <?php if (!$categorias): ?>
            <p class="text-muted">Nenhuma categoria cadastrada.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-primary">
                  <tr><th>#</th><th>Nome</th><th>Descrição</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($categorias as $c): ?>
                  <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><?= htmlspecialchars($c['nome']) ?></td>
                    <td><?= htmlspecialchars($c['descricao'] ?? '—') ?></td>
                    <td><span class="badge <?= $c['ativo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $c['ativo'] ? 'Ativa' : 'Inativa' ?></span></td>
                    <td class="d-flex gap-1">
                      <a href="categorias.php?editar=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                      <?php if ($isMaster): ?>
                        <form method="post" class="d-inline"
                              onsubmit="return confirm('Excluir esta categoria?');">
                          <input type="hidden" name="acao" value="excluir">
                          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                          <button class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                      <?php endif; ?>
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
  </div>
</main>

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?= date('Y') ?> Fix Now.</small></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
