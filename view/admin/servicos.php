<?php
session_start();
$paginaAtiva = 'servicos';
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
        $tecnicoId   = (int)($_POST['tecnico_id'] ?? 0);
        $nome        = trim($_POST['nome'] ?? '');
        $descricao   = trim($_POST['descricao'] ?? '');
        $preco       = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
        $categoriaId = (int)($_POST['categoria_id'] ?? 0) ?: null;
        $ativo       = isset($_POST['ativo']) ? 1 : 0;

        if ($tecnicoId <= 0 || $nome === '') {
            $erro = 'Prestador e nome do serviço são obrigatórios.';
        } else {
            $pdo->prepare('INSERT INTO servico (tecnico_id, categoria_id, nome, descricao, preco, ativo) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$tecnicoId, $categoriaId, $nome, $descricao ?: null, $preco, $ativo]);
            $mensagem = 'Serviço cadastrado com sucesso.';
        }
    } elseif ($acao === 'editar') {
        $sid         = (int)($_POST['servico_id'] ?? 0);
        $tecnicoId   = (int)($_POST['tecnico_id'] ?? 0);
        $nome        = trim($_POST['nome'] ?? '');
        $descricao   = trim($_POST['descricao'] ?? '');
        $preco       = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
        $categoriaId = (int)($_POST['categoria_id'] ?? 0) ?: null;
        $ativo       = isset($_POST['ativo']) ? 1 : 0;

        if ($sid <= 0 || $tecnicoId <= 0 || $nome === '') {
            $erro = 'Dados inválidos.';
        } else {
            $pdo->prepare('UPDATE servico SET tecnico_id=?, categoria_id=?, nome=?, descricao=?, preco=?, ativo=? WHERE id=?')
                ->execute([$tecnicoId, $categoriaId, $nome, $descricao ?: null, $preco, $ativo, $sid]);
            $mensagem = 'Serviço atualizado.';
        }
    } elseif ($acao === 'excluir') {
        $sid = (int)($_POST['servico_id'] ?? 0);
        if ($sid > 0) {
            $pdo->prepare('DELETE FROM servico WHERE id = ?')->execute([$sid]);
            $mensagem = 'Serviço excluído.';
        }
    }
}

$filtro     = trim($_GET['busca'] ?? '');
$editandoId = (int)($_GET['editar'] ?? 0);
$editando   = null;

$stmtS = $pdo->query("
    SELECT s.*, t.nome AS tecnico_nome, c.nome AS categoria_nome
    FROM servico s
    INNER JOIN tecnico t ON t.id = s.tecnico_id
    LEFT JOIN categoria c ON c.id = s.categoria_id
    ORDER BY t.nome ASC, s.nome ASC
");
$todosServicos = $stmtS->fetchAll();

$servicos = $filtro !== ''
    ? array_filter($todosServicos, fn($s) => stripos($s['nome'], $filtro) !== false || stripos($s['tecnico_nome'], $filtro) !== false)
    : $todosServicos;

if ($editandoId > 0) {
    foreach ($todosServicos as $s) {
        if ((int)$s['id'] === $editandoId) { $editando = $s; break; }
    }
}

$tecnicos   = $pdo->query("SELECT id, nome, especialidade FROM tecnico WHERE ativo = 1 AND status_cadastro = 'Aprovado' ORDER BY nome")->fetchAll();
$categorias = $pdo->query("SELECT id, nome FROM categoria WHERE ativo = 1 ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Serviços - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Gerenciar Serviços</h2>
  <p class="text-muted mb-4">Cadastre, edite e exclua serviços de qualquer prestador da plataforma.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5><?= $editando ? 'Editar Serviço #' . (int)$editando['id'] : 'Novo Serviço' ?></h5>
          <form method="post">
            <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
            <?php if ($editando): ?><input type="hidden" name="servico_id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>

            <div class="mb-2">
              <label class="form-label">Prestador <span class="text-danger">*</span></label>
              <select name="tecnico_id" class="form-select" required>
                <option value="">-- Selecione --</option>
                <?php foreach ($tecnicos as $t): ?>
                  <option value="<?= (int)$t['id'] ?>"
                    <?= ((int)($editando['tecnico_id'] ?? 0)) === (int)$t['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['nome']) ?> (<?= htmlspecialchars($t['especialidade']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" maxlength="150" required
                value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Categoria</label>
              <select name="categoria_id" class="form-select">
                <option value="">-- Nenhuma --</option>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"
                    <?= ((int)($editando['categoria_id'] ?? 0)) === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" class="form-control" rows="2" maxlength="500"><?= htmlspecialchars($editando['descricao'] ?? '') ?></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">Preço (R$) <span class="text-danger">*</span></label>
              <input type="number" name="preco" class="form-control" min="0" step="0.01" required
                value="<?= number_format((float)($editando['preco'] ?? 0), 2, '.', '') ?>">
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" name="ativo" id="chk_ativo" class="form-check-input" value="1"
                <?= (!$editando || $editando['ativo']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="chk_ativo">Ativo (visível no catálogo)</label>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-warning fw-semibold"><?= $editando ? 'Salvar' : 'Cadastrar' ?></button>
              <?php if ($editando): ?><a href="servicos.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Serviços cadastrados <span class="badge bg-secondary"><?= count($todosServicos) ?></span></h5>
            <form method="get" class="d-flex gap-2">
              <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar..."
                value="<?= htmlspecialchars($filtro) ?>" style="width:180px;">
              <button class="btn btn-sm btn-outline-secondary">Buscar</button>
              <?php if ($filtro): ?><a href="servicos.php" class="btn btn-sm btn-outline-danger">Limpar</a><?php endif; ?>
            </form>
          </div>
          <?php if (!$servicos): ?>
            <p class="text-muted mb-0">Nenhum serviço encontrado.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle table-sm">
                <thead class="table-primary">
                  <tr><th>#</th><th>Nome</th><th>Prestador</th><th>Categoria</th><th>Preço</th><th>Ativo</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($servicos as $s): ?>
                  <tr>
                    <td><?= (int)$s['id'] ?></td>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($s['nome']) ?></div>
                      <?php if ($s['descricao']): ?><div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($s['descricao'], 0, 50, '...')) ?></div><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($s['tecnico_nome']) ?></td>
                    <td><?= htmlspecialchars($s['categoria_nome'] ?? '—') ?></td>
                    <td>R$ <?= number_format((float)$s['preco'], 2, ',', '.') ?></td>
                    <td><span class="badge <?= $s['ativo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $s['ativo'] ? 'Sim' : 'Não' ?></span></td>
                    <td class="d-flex gap-1">
                      <a href="servicos.php?editar=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                      <form method="post" class="d-inline" onsubmit="return confirm('Excluir este serviço?');">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="servico_id" value="<?= (int)$s['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Excluir</button>
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
