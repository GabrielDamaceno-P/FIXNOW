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
        $perfil = $_POST['admin_perfil'] ?? 'Operacoes';
        $tel    = trim($_POST['telefone'] ?? '');
        $end    = trim($_POST['endereco'] ?? '');
        $cep    = trim($_POST['cep'] ?? '');
        $genero = $_POST['genero'] ?? 'Prefiro não informar';
        $perfisOk = ['Master', 'Operacoes', 'Financeiro'];

        if ($nome === '' || $email === '' || $senha === '' || $tel === '' || $end === '' || $cep === '') {
            $erro = 'Preencha todos os campos obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } elseif (!in_array($perfil, $perfisOk, true)) {
            $erro = 'Perfil inválido.';
        } elseif (mb_strlen($senha) < 6) {
            $erro = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                $pdo->prepare('INSERT INTO cliente (nome, email, senha, telefone, endereco, cep, foto_perfil, genero, is_admin, admin_perfil)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)')
                    ->execute([
                        $nome, $email, password_hash($senha, PASSWORD_DEFAULT),
                        $tel, $end, $cep,
                        'assets/img/perfil/default-cliente.jpg',
                        $genero, $perfil
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
        $perfil    = $_POST['admin_perfil'] ?? 'Operacoes';
        $tel       = trim($_POST['telefone'] ?? '');
        $end       = trim($_POST['endereco'] ?? '');
        $cep       = trim($_POST['cep'] ?? '');
        $genero    = $_POST['genero'] ?? 'Prefiro não informar';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $perfisOk  = ['Master', 'Operacoes', 'Financeiro'];

        if ($id <= 0 || $nome === '' || $email === '' || $tel === '' || $end === '' || $cep === '') {
            $erro = 'Preencha todos os campos obrigatórios.';
        } elseif (!in_array($perfil, $perfisOk, true)) {
            $erro = 'Perfil inválido.';
        } else {
            try {
                if ($novaSenha !== '') {
                    if (mb_strlen($novaSenha) < 6) { $erro = 'A nova senha deve ter pelo menos 6 caracteres.'; }
                    else {
                        $pdo->prepare('UPDATE cliente SET nome=?, email=?, senha=?, telefone=?, endereco=?, cep=?, genero=?, admin_perfil=? WHERE id=? AND is_admin=1')
                            ->execute([
                                $nome, $email, password_hash($novaSenha, PASSWORD_DEFAULT),
                                $tel, $end, $cep, $genero, $perfil, $id
                            ]);
                        $mensagem = 'Administrador atualizado.';
                    }
                } else {
                    $pdo->prepare('UPDATE cliente SET nome=?, email=?, telefone=?, endereco=?, cep=?, genero=?, admin_perfil=? WHERE id=? AND is_admin=1')
                        ->execute([$nome, $email, $tel, $end, $cep, $genero, $perfil, $id]);
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

$admins = $pdo->query("SELECT id, nome, email, telefone, genero, admin_perfil, criado_em FROM cliente WHERE is_admin = 1 ORDER BY nome ASC")->fetchAll();

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
  <title>Manter Admins - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Manter Administradores</h2>
  <p class="text-muted mb-4">Cadastre, edite ou remova contas administrativas da plataforma.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5><?= $editando ? 'Editar Admin' : 'Novo Administrador' ?></h5>
          <form method="post">
            <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
            <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>
            <div class="mb-2">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" maxlength="120" required
                value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">E-mail <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" maxlength="150" required
                value="<?= htmlspecialchars($editando['email'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label class="form-label"><?= $editando ? 'Nova Senha (deixe em branco para manter)' : 'Senha *' ?></label>
              <input type="password" name="<?= $editando ? 'nova_senha' : 'senha' ?>" class="form-control" minlength="6"
                <?= $editando ? '' : 'required' ?> autocomplete="new-password">
            </div>
            <div class="mb-2">
              <label class="form-label">Perfil <span class="text-danger">*</span></label>
              <select name="admin_perfil" class="form-select" required>
                <?php foreach (['Master', 'Operacoes', 'Financeiro'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($editando['admin_perfil'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Telefone <span class="text-danger">*</span></label>
              <input type="text" name="telefone" class="form-control" maxlength="20" required
                value="<?= htmlspecialchars($editando['telefone'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Endereço <span class="text-danger">*</span></label>
              <input type="text" name="endereco" class="form-control" maxlength="200" required
                value="<?= htmlspecialchars($editando['endereco'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">CEP <span class="text-danger">*</span></label>
              <input type="text" name="cep" class="form-control" maxlength="10" required
                value="<?= htmlspecialchars($editando['cep'] ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Gênero</label>
              <select name="genero" class="form-select">
                <?php foreach (['Feminino','Masculino','Outro','Prefiro não informar'] as $g): ?>
                  <option value="<?= $g ?>" <?= ($editando['genero'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-warning fw-semibold"><?= $editando ? 'Salvar' : 'Criar' ?></button>
              <?php if ($editando): ?><a href="admins.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3">Administradores cadastrados</h5>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-primary">
                <tr><th>#</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Telefone</th><th></th></tr>
              </thead>
              <tbody>
              <?php foreach ($admins as $a): ?>
                <tr>
                  <td><?= (int)$a['id'] ?></td>
                  <td><?= htmlspecialchars($a['nome']) ?></td>
                  <td><?= htmlspecialchars($a['email']) ?></td>
                  <td><span class="badge bg-primary"><?= htmlspecialchars($a['admin_perfil'] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($a['telefone']) ?></td>
                  <td class="d-flex gap-1">
                    <a href="admins.php?editar=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                    <?php if ((int)$a['id'] !== $adminId): ?>
                      <form method="post" class="d-inline" onsubmit="return confirm('Excluir este administrador?');">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted small">(você)</span>
                    <?php endif; ?>
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

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?= date('Y') ?> Fix Now.</small></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
