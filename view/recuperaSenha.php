<?php
session_start();
require_once __DIR__ . '/../controller/RecuperaSenhaControl.php';

$ctrl = new RecuperaSenhaControl();
$ctrl->processar();
$mensagem  = $ctrl->mensagem;
$erro      = $ctrl->erro;
$senhaTemp = $ctrl->senhaTemp;
$etapa     = $ctrl->etapa;

$_urlInicio = '../index.php';
if (isset($_SESSION['cliente_id']))      $_urlInicio = 'dashboardCliente.php';
elseif (isset($_SESSION['tecnico_id'])) $_urlInicio = 'prestador/dashboardPrestador.php';
elseif (isset($_SESSION['admin_id']))   $_urlInicio = 'admin/painelAdmin.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar Senha - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h2 class="mb-3">Recuperar senha</h2>

          <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
          <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

          <?php if ($etapa === 1): ?>
          <form method="post" class="row g-3">
            <input type="hidden" name="etapa" value="1">
            <div class="col-12">
              <label class="form-label">E-mail cadastrado</label>
              <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-warning fw-semibold w-100">Verificar e-mail</button>
            </div>
          </form>

          <?php elseif ($etapa === 2): ?>
          <form method="post" class="row g-3">
            <input type="hidden" name="etapa" value="2">
            <div class="col-12">
              <p class="text-muted">E-mail confirmado. Clique abaixo para gerar uma senha temporária.</p>
              <button type="submit" class="btn btn-warning fw-semibold w-100">Gerar senha temporária</button>
            </div>
          </form>

          <?php elseif ($etapa === 3 && $senhaTemp): ?>
          <div class="alert alert-warning fw-semibold fs-5 text-center"><?php echo htmlspecialchars($senhaTemp); ?></div>
          <p class="text-muted small">Copie esta senha e use-a para fazer login. Depois altere-a no perfil.</p>
          <a href="login.php" class="btn btn-warning fw-semibold w-100">Ir para o login</a>
          <?php endif; ?>

          <p class="mt-3 mb-0 small"><a href="login.php">Voltar ao login</a></p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
