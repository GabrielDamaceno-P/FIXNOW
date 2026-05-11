<?php
session_start();
$paginaAtiva = 'clientes';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

// _navbar.php faz a verificação de sessão e expõe $pdo, $isMaster, true
ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

// acesso: somente admin

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isMaster) {
    $cid = (int)($_POST['cliente_id'] ?? 0);
    if ($cid > 0) {
        $pdo->prepare('DELETE FROM cliente WHERE id = ? AND is_admin = 0')->execute([$cid]);
        $mensagem = 'Cliente excluído.';
    }
}

$filtro = trim($_GET['busca'] ?? '');
if ($filtro !== '') {
    $like = '%' . $filtro . '%';
    $stmt = $pdo->prepare("SELECT id, nome, email, telefone, genero, endereco, cep, criado_em FROM cliente WHERE is_admin=0 AND (nome LIKE ? OR email LIKE ?) ORDER BY nome ASC");
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT id, nome, email, telefone, genero, endereco, cep, criado_em FROM cliente WHERE is_admin=0 ORDER BY nome ASC");
}
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clientes - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Clientes</h2>
  <p class="text-muted mb-4">Visualize e remova contas de clientes da plataforma.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0">Clientes cadastrados <span class="badge bg-secondary"><?= count($clientes) ?></span></h5>
        <form method="get" class="d-flex gap-2">
          <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome ou e-mail..."
            value="<?= htmlspecialchars($filtro) ?>" style="width:200px;">
          <button class="btn btn-sm btn-outline-secondary">Buscar</button>
          <?php if ($filtro): ?><a href="clientes.php" class="btn btn-sm btn-outline-danger">Limpar</a><?php endif; ?>
        </form>
      </div>

      <?php if (!$clientes): ?>
        <p class="text-muted mb-0">Nenhum cliente encontrado.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle table-sm">
            <thead class="table-primary">
              <tr>
                <th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Gênero</th>
                <th>Endereço</th><th>CEP</th><th>Cadastrado em</th>
                <?php if ($isMaster): ?><th></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($clientes as $cl): ?>
              <tr>
                <td><?= (int)$cl['id'] ?></td>
                <td><?= htmlspecialchars($cl['nome']) ?></td>
                <td><?= htmlspecialchars($cl['email']) ?></td>
                <td><?= htmlspecialchars($cl['telefone']) ?></td>
                <td><?= htmlspecialchars($cl['genero']) ?></td>
                <td><?= htmlspecialchars($cl['endereco']) ?></td>
                <td><?= htmlspecialchars($cl['cep']) ?></td>
                <td><?= date('d/m/Y', strtotime($cl['criado_em'])) ?></td>
                <?php if ($isMaster): ?>
                <td>
                  <form method="post" class="d-inline"
                        onsubmit="return confirm('Excluir este cliente e todos os seus chamados?');">
                    <input type="hidden" name="cliente_id" value="<?= (int)$cl['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Excluir</button>
                  </form>
                </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
