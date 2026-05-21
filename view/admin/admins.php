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

require_once __DIR__ . '/../../model/dao/AdminDAO.php';
$adminDAO = new AdminDAO();

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
            if ($adminDAO->inserir($nome, $email, password_hash($senha, PASSWORD_DEFAULT), $tel, $genero)) {
                $mensagem = 'Administrador criado com sucesso.';
            } else {
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

        if ($id === $primaryAdminId && !$isCurrentPrimary) {
            $erro = 'Somente o administrador primário pode editar a própria conta.';
        } elseif ($id <= 0 || $nome === '' || $email === '' || $tel === '') {
            $erro = 'Preencha todos os campos obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } else {
            if ($novaSenha !== '' && mb_strlen($novaSenha) < 6) {
                $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
            } else {
                if ($adminDAO->atualizar($id, $nome, $email, $tel, $genero)) {
                    if ($novaSenha !== '') $adminDAO->atualizarSenha($id, password_hash($novaSenha, PASSWORD_DEFAULT));
                    $mensagem = 'Administrador atualizado.';
                } else {
                    $erro = 'E-mail já utilizado ou erro ao atualizar.';
                }
            }
        }
    } elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $primaryAdminId) {
            $erro = 'O administrador primário não pode ser excluído.';
        } elseif ($id > 0 && $id !== $adminId) {
            $mensagem = $adminDAO->excluir($id) ? 'Administrador excluído.' : 'Não encontrado.';
        } else {
            $erro = 'Não é possível excluir a própria conta.';
        }
    }
}

$admins = $adminDAO->listar();

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
  <style>
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .admin-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}
    .admin-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#0d1b3d,#1a2b63);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0}
    .form-label{font-size:.85rem;font-weight:600;margin-bottom:.3rem}
    [data-theme="dark"] .admin-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>👑 Administradores</h1>
      <p>Gerencie as contas com acesso ao painel administrativo da plataforma.</p>
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
          <?= $editando ? 'Editar administrador' : 'Novo administrador' ?>
        </div>
        <form method="post" class="row g-2">
          <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
          <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>

          <div class="col-12">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="nome" class="form-control form-control-sm" maxlength="120" required
              value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" placeholder="Nome completo">
          </div>
          <div class="col-12">
            <label class="form-label">E-mail <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control form-control-sm" maxlength="150" required
              value="<?= htmlspecialchars($editando['email'] ?? '') ?>" placeholder="admin@fixnow.com">
          </div>
          <div class="col-12">
            <label class="form-label"><?= $editando ? 'Nova senha (deixe em branco para manter)' : 'Senha *' ?></label>
            <input type="password" name="<?= $editando ? 'nova_senha' : 'senha' ?>" class="form-control form-control-sm"
              minlength="6" <?= $editando ? '' : 'required' ?> autocomplete="new-password" placeholder="Mínimo 6 caracteres">
          </div>
          <div class="col-12">
            <label class="form-label">Telefone <span class="text-danger">*</span></label>
            <input type="text" name="telefone" class="form-control form-control-sm" maxlength="20" required
              value="<?= htmlspecialchars($editando['telefone'] ?? '') ?>" placeholder="(11) 99999-9999">
          </div>
          <div class="col-12 mt-2 d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-bold"><?= $editando ? 'Salvar' : 'Criar admin' ?></button>
            <?php if ($editando): ?><a href="admins.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>

      <div class="admin-card mt-3" style="background:#fffbeb;border-color:#fde68a;">
        <div class="d-flex gap-2 align-items-start">
          <span style="font-size:1.3rem;">🔒</span>
          <div style="font-size:.82rem;color:#92400e;">
            <strong>Atenção:</strong> Administradores têm acesso total ao painel. Crie contas somente para membros da equipe de confiança. Senhas são armazenadas com hash seguro.
          </div>
        </div>
      </div>
    </div>

    <!-- Tabela -->
    <div class="col-lg-8">
      <div class="admin-card">
        <div class="secao-titulo">
          <span>👥</span>
          Administradores cadastrados
          <span class="badge ms-auto" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem;"><?= count($admins) ?></span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-primary">
              <tr><th></th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Desde</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($admins as $a): ?>
              <tr>
                <td>
                  <div class="admin-avatar"><?= htmlspecialchars(mb_substr($a['nome'], 0, 1)) ?></div>
                </td>
                <td>
                  <div class="fw-semibold" style="font-size:.88rem;">
                    <?= htmlspecialchars($a['nome']) ?>
                    <?php if ((int)$a['id'] === $primaryAdminId): ?>
                      <span class="badge ms-1" style="background:#fef3c7;color:#b45309;font-size:.68rem;">👑 primário</span>
                    <?php endif; ?>
                    <?php if ((int)$a['id'] === $adminId): ?>
                      <span class="badge ms-1" style="background:#dcfce7;color:#16a34a;font-size:.68rem;">você</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td style="font-size:.83rem;"><?= htmlspecialchars($a['email']) ?></td>
                <td style="font-size:.83rem;"><?= htmlspecialchars($a['telefone']) ?></td>
                <td class="text-muted" style="font-size:.78rem;"><?= date('d/m/Y', strtotime($a['criado_em'])) ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <?php
                      $isPrimario = (int)$a['id'] === $primaryAdminId;
                      $podeEditar = !$isPrimario || $isCurrentPrimary;
                      $podeExcluir = !$isPrimario && (int)$a['id'] !== $adminId;
                    ?>
                    <?php if ($podeEditar): ?>
                      <a href="admins.php?editar=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:.78rem;">Editar</a>
                    <?php else: ?>
                      <span class="btn btn-sm btn-outline-secondary disabled" style="font-size:.78rem;" title="Somente o admin primário pode editar a própria conta">🔒</span>
                    <?php endif; ?>
                    <?php if ($podeExcluir): ?>
                      <form method="post" class="d-inline" onsubmit="return confirm('Excluir este administrador?');">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.78rem;">Excluir</button>
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
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
