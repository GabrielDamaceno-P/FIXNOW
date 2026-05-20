<?php
session_start();
require_once __DIR__ . '/../controller/LoginControl.php';

$ctrl = new LoginControl();
$ctrl->jaLogado();
$ctrl->processar();
$erro = $ctrl->erro;

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
  <title>Login - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    html, body { height: 100%; }
    body { min-height: 100vh; display: flex; flex-direction: column; }

    .auth-wrap {
      flex: 1;
      display: flex;
      min-height: calc(100vh - 56px);
      margin-top: 56px;
    }

    /* Painel esquerdo */
    .auth-hero {
      background: linear-gradient(145deg, #0d1b3d 0%, #1a2b63 55%, #c95e00 100%);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3rem 2.5rem;
    }
    .auth-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .auth-hero .brand {
      font-size: 2rem;
      font-weight: 900;
      color: #fff;
      letter-spacing: -0.5px;
      margin-bottom: .3rem;
      position: relative;
      z-index: 1;
    }
    .auth-hero .brand span { color: #ffc107; }
    .auth-hero .tagline {
      color: rgba(255,255,255,.72);
      font-size: .95rem;
      margin-bottom: 2rem;
      position: relative;
      z-index: 1;
    }
    .auth-feat {
      list-style: none;
      padding: 0;
      margin: 0;
      position: relative;
      z-index: 1;
    }
    .auth-feat li {
      color: rgba(255,255,255,.82);
      font-size: .9rem;
      padding: .45rem 0;
      display: flex;
      align-items: center;
      gap: .7rem;
      border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .auth-feat li:last-child { border-bottom: none; }
    .auth-feat li .ico {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: rgba(255,255,255,.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }

    /* Painel direito */
    .auth-form-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem 1.5rem;
      background: var(--bs-body-bg, #f8f9fa);
    }
    .auth-card {
      background: #fff;
      border-radius: 18px;
      padding: 2.2rem 2rem;
      width: 100%;
      max-width: 420px;
      border: 1.5px solid #e8ecf3;
      box-shadow: 0 8px 32px rgba(13,27,61,.1);
    }
    .auth-card-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #0d1b3d;
      margin-bottom: .3rem;
    }
    .auth-card-sub {
      font-size: .86rem;
      color: #6b7280;
      margin-bottom: 1.5rem;
    }
    .auth-input-label {
      font-size: .84rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: .3rem;
    }
    .auth-divider {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin: 1.4rem 0;
      color: #9ca3af;
      font-size: .8rem;
    }
    .auth-divider::before, .auth-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e5e7eb;
    }
    .auth-links {
      display: flex;
      gap: .5rem;
    }
    .auth-links .btn { font-size: .8rem; border-radius: 50px; }

    /* Dark mode */
    [data-theme="dark"] .auth-form-wrap { background: #111827; }
    [data-theme="dark"] .auth-card { background: #1e2538; border-color: #2e3650; box-shadow: 0 8px 32px rgba(0,0,0,.4); }
    [data-theme="dark"] .auth-card-title { color: #e4e8f4; }
    [data-theme="dark"] .auth-card-sub { color: #8090b0; }
    [data-theme="dark"] .auth-input-label { color: #c8d0e0; }
    [data-theme="dark"] .auth-divider { color: #4b5563; }
    [data-theme="dark"] .auth-divider::before, [data-theme="dark"] .auth-divider::after { background: #2e3650; }

    @media (max-width: 767.98px) {
      .auth-hero { padding: 2rem 1.5rem; }
      .auth-feat { display: none; }
      .auth-hero .tagline { margin-bottom: .5rem; }
      .auth-hero { min-height: 120px; justify-content: flex-start; padding: 1.5rem; }
      .auth-wrap { flex-direction: column; }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= $_urlInicio ?>">Início</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastrarCliente.php">Criar conta</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastrarPrestador.php">Sou prestador</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="auth-wrap">
  <!-- Painel esquerdo: hero -->
  <div class="auth-hero col-md-5 col-lg-5 d-none d-md-flex">
    <a href="<?= $_urlInicio ?>" style="text-decoration:none;">
      <div class="brand">Fix<span>Now</span></div>
    </a>
    <p class="tagline">Conectamos você a prestadores de serviço verificados e de confiança.</p>
    <ul class="auth-feat">
      <li>
        <div class="ico">🔍</div>
        <span>Encontre profissionais qualificados na sua região</span>
      </li>
      <li>
        <div class="ico">📋</div>
        <span>Acompanhe seus chamados em tempo real</span>
      </li>
      <li>
        <div class="ico">💬</div>
        <span>Chat direto com o prestador pelo app</span>
      </li>
      <li>
        <div class="ico">⭐</div>
        <span>Avalie e confie em prestadores com histórico comprovado</span>
      </li>
    </ul>
  </div>

  <!-- Painel direito: formulário -->
  <div class="auth-form-wrap col-12 col-md-7 col-lg-7">
    <div class="auth-card">
      <div class="auth-card-title">Bem-vindo de volta 👋</div>
      <div class="auth-card-sub">Use o mesmo login para cliente, prestador ou administrador.</div>

      <?php if ($erro): ?>
        <div class="alert alert-danger py-2 px-3" style="font-size:.88rem;border-radius:10px;"><?php echo htmlspecialchars($erro); ?></div>
      <?php endif; ?>

      <form method="post" class="js-guard-submit" novalidate>
        <div class="mb-3">
          <label class="auth-input-label">E-mail</label>
          <input type="email" name="email" class="form-control" required autocomplete="username"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 placeholder="seu@email.com">
        </div>
        <div class="mb-1">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="auth-input-label mb-0">Senha</label>
            <a href="recuperaSenha.php" class="text-warning text-decoration-none" style="font-size:.78rem;font-weight:600;">Esqueceu a senha?</a>
          </div>
          <div class="input-group">
            <input type="password" name="senha" class="form-control" required autocomplete="current-password" placeholder="••••••••">
            <button type="button" class="btn btn-outline-secondary" data-action="toggle-password" style="font-size:.8rem;">Mostrar</button>
          </div>
        </div>

        <button type="submit" class="btn btn-warning fw-bold w-100 mt-4" style="border-radius:10px;padding:.7rem;">Entrar</button>
      </form>

      <div class="auth-divider">ou</div>

      <div class="auth-links">
        <a href="cadastrarCliente.php" class="btn btn-outline-secondary flex-grow-1">Criar conta cliente</a>
        <a href="cadastrarPrestador.php" class="btn btn-outline-secondary flex-grow-1">Sou prestador</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js"></script>
</body>
</html>
