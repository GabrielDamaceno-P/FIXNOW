<?php
session_start();
require_once __DIR__ . '/../controller/LoginControl.php';

$ctrl = new LoginControl();
$ctrl->jaLogado();
$ctrl->processar();
$erro = $ctrl->erro;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="../index.php">Início</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastrarCliente.php">Cadastro cliente</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastrarPrestador.php">Cadastro prestador</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h2 class="mb-2">Entrar no Fix Now</h2>
          <p class="text-muted small mb-3">Use o mesmo login para <strong>cliente</strong>, <strong>prestador</strong> ou <strong>administrador</strong>.</p>

          <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
          <?php endif; ?>

          <form method="post" class="row g-3 js-guard-submit" novalidate>
            <div class="col-12">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" class="form-control" required autocomplete="username"
                     value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Senha</label>
              <div class="input-group">
                <input type="password" name="senha" class="form-control" required autocomplete="current-password">
                <button type="button" class="btn btn-outline-secondary" data-action="toggle-password">Mostrar</button>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-warning w-100 fw-semibold">Entrar</button>
            </div>
          </form>

          <p class="mt-3 mb-0 small">
            <a href="cadastrarCliente.php">Criar conta cliente</a> &middot;
            <a href="cadastrarPrestador.php">Cadastrar como prestador</a> &middot;
            <a href="recuperaSenha.php">Esqueceu a senha?</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js"></script>
</body>
</html>
