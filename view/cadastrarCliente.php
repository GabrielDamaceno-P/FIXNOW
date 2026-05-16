<?php
session_start();
require_once __DIR__ . '/../controller/CadastroClienteControl.php';

if (isset($_SESSION['tecnico_id'])) {
    header('Location: prestador/dashboardPrestador.php'); exit;
}

$ctrl = new CadastroClienteControl();
$ctrl->processar();
$erro     = $ctrl->erro;
$mensagem = $ctrl->mensagem;

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
  <title>Cadastro - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= $_urlInicio ?>">Início</a></li>
        <li class="nav-item"><a class="nav-link active" href="cadastrarCliente.php">Cadastro</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h2 class="mb-3">Criar conta</h2>

          <?php if ($mensagem): ?>
            <div class="alert alert-success js-flash-reload" data-reload-ms="2400"><?php echo htmlspecialchars($mensagem); ?></div>
          <?php endif; ?>
          <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" class="row g-3 js-guard-submit js-form-cpf" novalidate>
            <div class="col-md-6">
              <label class="form-label">Nome completo</label>
              <input type="text" name="nome" class="form-control" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">CPF</label>
              <input type="text" name="cpf" class="form-control js-cpf" required autocomplete="off" maxlength="14" inputmode="numeric" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
              <div class="invalid-feedback d-block small js-cpf-feedback"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Senha</label>
              <div class="input-group">
                <input type="password" name="senha" id="campo-senha" class="form-control" required minlength="6" autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary" data-action="toggle-password">Mostrar</button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirmar senha</label>
              <div class="input-group">
                <input type="password" name="confirmar_senha" id="campo-confirmar-senha" class="form-control" required minlength="6" autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary" data-action="toggle-password">Mostrar</button>
              </div>
              <div class="invalid-feedback d-block small js-senha-feedback"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" class="form-control js-mask" required inputmode="numeric" placeholder="(11) 98888-8888" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Foto de perfil <span class="text-danger">*</span></label>
              <input type="file" name="foto_perfil" class="form-control" required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>
            <div class="col-md-4">
              <label class="form-label">CEP</label>
              <input type="text" name="cep" id="js-cep" class="form-control js-mask" required inputmode="numeric" placeholder="00000-000" autocomplete="postal-code" value="<?php echo htmlspecialchars($_POST['cep'] ?? ''); ?>">
              <div class="small mt-1 js-cep-status text-muted"></div>
            </div>
            <div class="col-md-8">
              <label class="form-label">Logradouro</label>
              <input type="text" name="logradouro" id="js-logradouro" class="form-control" required value="<?php echo htmlspecialchars($_POST['logradouro'] ?? ''); ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label">Bairro</label>
              <input type="text" name="bairro" id="js-bairro" class="form-control" required value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label">Cidade</label>
              <input type="text" name="cidade" id="js-cidade" class="form-control" required value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">UF</label>
              <input type="text" name="estado" id="js-estado" class="form-control" required maxlength="2" value="<?php echo htmlspecialchars($_POST['estado'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Gênero</label>
              <select name="genero" class="form-select" required>
                <?php
                  $gopts  = ['Feminino', 'Masculino', 'Outro', 'Prefiro não informar'];
                  $gatual = $_POST['genero'] ?? 'Prefiro não informar';
                  foreach ($gopts as $val) {
                      $s = $gatual === $val ? 'selected' : '';
                      echo "<option value=\"{$val}\" {$s}>{$val}</option>";
                  }
                ?>
              </select>
              <small class="text-muted">Clientes mulheres podem solicitar apenas prestadoras mulheres ao abrir chamados.</small>
            </div>
            <div class="col-12">
              <div class="form-check border rounded p-3 bg-light">
                <input class="form-check-input" type="checkbox" name="aceitar_termos" id="aceitar-termos" required value="1"
                  <?php echo !empty($_POST['aceitar_termos']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="aceitar-termos">
                  Li e concordo com os
                  <a href="termos.php?tipo=termos" target="_blank" class="text-primary fw-semibold">Termos de Uso</a>
                  e a
                  <a href="termos.php?tipo=privacidade" target="_blank" class="text-primary fw-semibold">Política de Privacidade (LGPD)</a>.
                  <span class="text-danger">*</span>
                </label>
              </div>
              <div class="invalid-feedback d-block small text-danger" id="termos-feedback" style="display:none!important"></div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-warning fw-semibold">Cadastrar</button>
              <a href="login.php" class="btn btn-outline-primary">Já tenho conta</a>
              <a href="cadastrarPrestador.php" class="btn btn-link">Sou prestador</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js?v=4"></script>
<script src="../assets/js/cpf-validation-reload.js"></script>
</body>
</html>
