<?php
session_start();
$paginaAtiva = 'admins';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

if (!$isMaster) { header('Location: painelAdmin.php'); exit; }

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $nome   = trim($_POST['nome'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $senha  = $_POST['senha'] ?? '';
        $tel    = trim($_POST['telefone'] ?? '');
        $genero = $_POST['genero'] ?? 'Prefiro não informar';

        if ($nome === '' || $email === '' || $senha === '' || $tel === '') {
            $erro = 'Preencha todos os campos obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } elseif (mb_strlen($senha) < 6) {
            $erro = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                $pdo->prepare('INSERT INTO cliente (nome, email, senha, telefone, genero, foto_perfil, is_admin, admin_perfil)
                               VALUES (?, ?, ?, ?, ?, ?, 1, ?)')
                    ->execute([
                        $nome, $email, password_hash($senha, PASSWORD_DEFAULT),
                        $tel, $genero,
                        'assets/img/perfil/default-cliente.jpg',
                        'Master'
                    ]);
                $mensagem = 'Administrador criado com sucesso.';
            } catch (PDOException $e) {
                $erro = 'E-mail já cadastrado ou erro ao salvar.';
            }
        }
    } elseif ($acao === 'editar') {
        $id        = (int)($_POST['id'] ?? 0);
        $nome      = trim($_POST['nome'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $tel       = trim($_POST['telefone'] ?? '');
        $genero    = $_POST['genero'] ?? 'Prefiro não informar';
        $novaSenha = $_POST['nova_senha'] ?? '';

        if ($id <= 0 || $nome === '' || $email === '' || $tel === '') {
            $erro = 'Preencha todos os campos obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } else {
            try {
                if ($novaSenha !== '') {
                    if (mb_strlen($novaSenha) < 6) {
                        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
                    } else {
                        $pdo->prepare('UPDATE cliente SET nome=?, email=?, senha=?, telefone=?, genero=? WHERE id=? AND is_admin=1')
                            ->execute([
                                $nome, $email, password_hash($novaSenha, PASSWORD_DEFAULT),
                                $tel, $genero, $id
                            ]);
                        $mensagem = 'Administrador atualizado.';
                    }
                } else {
                    $pdo->prepare('UPDATE cliente SET nome=?, email=?, telefone=?, genero=? WHERE id=? AND is_admin=1')
                        ->execute([$nome, $email, $tel, $genero, $id]);
                    $mensagem = 'Administrador atualizado.';
                }
            } catch (PDOException $e) {
                $erro = 'E-mail já utilizado ou erro ao atualizar.';
            }
        }
    } elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id !== $adminId) {
            $del = $pdo->prepare('DELETE FROM cliente WHERE id = ? AND is_admin = 1');
            $del->execute([$id]);
            $mensagem = $del->rowCount() > 0 ? 'Administrador excluído.' : 'Não encontrado.';
        } else {
            $erro = 'Não é possível excluir a própria conta.';
        }
    }
}

$admins = $pdo->query("SELECT id, nome, email, telefone, genero, criado_em FROM cliente WHERE is_admin = 1 ORDER BY nome ASC")->fetchAll();

$editando = null;
if (isset($_GET['editar'])) {
    $eid = (int)$_GET['editar'];
    foreach ($admins as $a) {
        if ((int)$a['id'] === $eid) { $editando = $a; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administradores - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Administradores</h2>
  <p class="text-muted mb-4">Gerencie as contas com acesso ao painel administrativo.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3"><?= $editando ? 'Editar administrador' : 'Novo administrador' ?></h5>
          <form method="post" class="row g-2">
            <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
            <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>

            <div class="col-12">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" maxlength="120" required
                value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">E-mail <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" maxlength="150" required
                value="<?= htmlspecialchars($editando['email'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label"><?= $editando ? 'Nova senha (deixe em branco para manter)' : 'Senha *' ?></label>
              <input type="password" name="<?= $editando ? 'nova_senha' : 'senha' ?>" class="form-control"
                minlength="6" <?= $editando ? '' : 'required' ?> autocomplete="new-password">
            </div>
            <div class="col-12">
              <label class="form-label">Telefone <span class="text-danger">*</span></label>
              <input type="text" name="telefone" class="form-control" maxlength="20" required
                value="<?= htmlspecialchars($editando['telefone'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Gênero</label>
              <select name="genero" class="form-select">
                <?php foreach (['Feminino','Masculino','Outro','Prefiro não informar'] as $g): ?>
                  <option value="<?= $g ?>" <?= ($editando['genero'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 d-flex gap-2 mt-1">
              <button type="submit" class="btn btn-warning fw-semibold"><?= $editando ? 'Salvar' : 'Criar' ?></button>
              <?php if ($editando): ?><a href="admins.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3">Administradores cadastrados <span class="badge bg-secondary"><?= count($admins) ?></span></h5>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-primary">
                <tr><th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Desde</th><th></th></tr>
              </thead>
              <tbody>
              <?php foreach ($admins as $a): ?>
                <tr>
                  <td><?= (int)$a['id'] ?></td>
                  <td>
                    <?= htmlspecialchars($a['nome']) ?>
                    <?php if ((int)$a['id'] === $adminId): ?>
                      <span class="badge bg-success ms-1">você</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($a['email']) ?></td>
                  <td><?= htmlspecialchars($a['telefone']) ?></td>
                  <td class="text-muted small"><?= date('d/m/Y', strtotime($a['criado_em'])) ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="admins.php?editar=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                      <?php if ((int)$a['id'] !== $adminId): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('Excluir este administrador?');">
                          <input type="hidden" name="acao" value="excluir">
                          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
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
