<?php
session_start();
require_once __DIR__ . '/../controller/CadastroPrestadorControl.php';

if (isset($_SESSION['tecnico_id'])) { header('Location: prestador/dashboardPrestador.php'); exit; }
if (isset($_SESSION['cliente_id'])) { header('Location: dashboardCliente.php'); exit; }

$ctrl = new CadastroPrestadorControl();
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
  <title>Cadastro Prestador - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    /* Banner topo — tom distinto: mais escuro/laranja para diferenciar do cadastro de cliente */
    .cad-hero {
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 45%, #9a3d00 100%);
      padding: 2rem 0 1.8rem;
      position: relative;
      overflow: hidden;
    }
    .cad-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .cad-hero h1 { color: #fff; font-size: clamp(1.2rem, 4vw, 1.7rem); font-weight: 800; margin: 0 0 .25rem; }
    .cad-hero p  { color: rgba(255,255,255,.72); font-size: .9rem; margin: 0; }

    /* Badge de aprovação */
    .badge-aprovacao {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 50px;
      padding: .3rem .9rem;
      font-size: .8rem;
      color: rgba(255,255,255,.85);
      margin-top: .75rem;
    }

    /* Card */
    .cad-card {
      background: #fff;
      border: 1.5px solid #e8ecf3;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 6px 24px rgba(13,27,61,.08);
    }

    /* Seção */
    .form-section {
      border-bottom: 1.5px solid #f0f3fa;
      margin-bottom: 1.4rem;
      padding-bottom: 1.4rem;
    }
    .form-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .form-section-title {
      font-size: .78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #9ca3af;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    /* Upload zone */
    .upload-zone {
      border: 2px dashed #ced4da;
      border-radius: 10px;
      padding: 1rem;
      text-align: center;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      display: block;
    }
    .upload-zone:hover { border-color: #ffc107; background: #fffbf0; }
    .upload-zone input[type="file"] { display: none; }
    .upload-zone .ui { font-size: 1.5rem; }
    .upload-zone p { font-size: .82rem; font-weight: 600; margin: .2rem 0 0; color: #374151; }
    .upload-zone small { font-size: .74rem; color: #9ca3af; }

    /* Termos */
    .termos-check {
      background: #f8faff;
      border: 1.5px solid #e8ecf3;
      border-radius: 10px;
      padding: .9rem 1rem;
    }

    /* Alert info documento */
    .doc-info {
      background: #fffbeb;
      border: 1.5px solid #fcd34d;
      border-radius: 10px;
      padding: .7rem .9rem;
      font-size: .82rem;
      color: #92400e;
      margin-top: .4rem;
    }
    [data-theme="dark"] .doc-info { background: #2d2000; border-color: #78350f; color: #fcd34d; }

    /* Botão */
    .btn-cad { border-radius: 10px; padding: .75rem; font-size: 1rem; }

    /* Dark mode */
    [data-theme="dark"] .cad-card { background: #1e2538; border-color: #2e3650; }
    [data-theme="dark"] .form-section { border-bottom-color: #2e3650; }
    [data-theme="dark"] .form-section-title { color: #6b7280; }
    [data-theme="dark"] .upload-zone { border-color: #2e3650; }
    [data-theme="dark"] .upload-zone:hover { background: #252d42; border-color: #ffc107; }
    [data-theme="dark"] .upload-zone p { color: #c8d0e0; }
    [data-theme="dark"] .termos-check { background: #252d42; border-color: #2e3650; }
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
        <li class="nav-item"><a class="nav-link active" href="cadastrarPrestador.php">Sou prestador</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="cad-hero mt-5">
  <div class="container" style="position:relative;z-index:1;">
    <h1>🔧 Cadastro de prestador de serviço</h1>
    <p>Alcance novos clientes e gerencie seus chamados pelo Fix Now.</p>
    <div class="badge-aprovacao">⏳ Aprovação pelo administrador necessária — você será notificado por e-mail.</div>
  </div>
</div>

<main class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">

      <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show js-flash-reload" data-reload-ms="2600"><?php echo htmlspecialchars($mensagem); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>
      <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($erro); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>

      <div class="cad-card">
        <form method="post" enctype="multipart/form-data" class="js-guard-submit js-form-cpf" novalidate>

          <!-- Seção: Dados pessoais -->
          <div class="form-section">
            <div class="form-section-title"><span>👤</span> Dados pessoais</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.88rem;">Nome completo <span class="text-danger">*</span></label>
                <input type="text" name="nome" class="form-control" required placeholder="Seu nome completo"
                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.88rem;">E-mail (login) <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="seu@email.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.88rem;">CPF <span class="text-danger">*</span></label>
                <input type="text" name="cpf" class="form-control js-cpf" required autocomplete="off"
                       maxlength="14" inputmode="numeric" placeholder="000.000.000-00"
                       value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
                <div class="invalid-feedback d-block small js-cpf-feedback"></div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.88rem;">Telefone <span class="text-danger">*</span></label>
                <input type="text" name="telefone" class="form-control js-mask" required inputmode="numeric"
                       placeholder="(11) 98888-8888"
                       value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.88rem;">Gênero <span class="text-danger">*</span></label>
                <select name="genero" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php
                    $gatu = $_POST['genero'] ?? '';
                    foreach (['Feminino' => 'Feminino', 'Masculino' => 'Masculino', 'Outro' => 'Outro'] as $val => $lab) {
                        $s = ($gatu === $val) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($val) . "\" {$s}>" . htmlspecialchars($lab) . '</option>';
                    }
                  ?>
                </select>
                <div class="form-text">Usado para combinar com clientes que pedem só prestadoras mulheres.</div>
              </div>
              <div class="col-md-6">
                <label class="upload-zone w-100" for="f_foto">
                  <div class="ui">📷</div>
                  <p id="fotoLabel">Foto de perfil</p>
                  <small>JPG, PNG ou WEBP</small>
                  <input type="file" name="foto_perfil" id="f_foto" required
                         accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                         onchange="document.getElementById('fotoLabel').textContent = this.files[0]?.name || 'Foto de perfil'">
                </label>
              </div>
            </div>
          </div>

          <!-- Seção: Segurança -->
          <div class="form-section">
            <div class="form-section-title"><span>🔒</span> Segurança</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.88rem;">Senha <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" name="senha" id="campo-senha" class="form-control" required minlength="6"
                         autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                  <button type="button" class="btn btn-outline-secondary" data-action="toggle-password" style="font-size:.8rem;">Mostrar</button>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.88rem;">Confirmar senha <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" name="confirmar_senha" id="campo-confirmar-senha" class="form-control" required minlength="6"
                         autocomplete="new-password" placeholder="Repita a senha">
                  <button type="button" class="btn btn-outline-secondary" data-action="toggle-password" style="font-size:.8rem;">Mostrar</button>
                </div>
                <div class="invalid-feedback d-block small js-senha-feedback"></div>
              </div>
            </div>
          </div>

          <!-- Seção: Verificação de identidade -->
          <div class="form-section">
            <div class="form-section-title"><span>🪪</span> Verificação de identidade</div>
            <div class="row g-3">
              <div class="col-12">
                <label class="upload-zone w-100" for="f_doc">
                  <div class="ui">🪪</div>
                  <p id="docLabel">Documento de identidade (RG ou CNH)</p>
                  <small>Foto legível — JPG, PNG ou WEBP</small>
                  <input type="file" name="documento" id="f_doc" required
                         accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                         onchange="document.getElementById('docLabel').textContent = this.files[0]?.name || 'Documento de identidade (RG ou CNH)'">
                </label>
                <div class="doc-info mt-2">
                  🔐 Usado <strong>somente</strong> pelo administrador para verificar sua identidade e gênero antes da aprovação. Não fica visível para clientes.
                </div>
              </div>
            </div>
          </div>

          <!-- Termos -->
          <div class="mb-4">
            <div class="termos-check">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="aceitar_termos" id="aceitar-termos" required value="1"
                  <?php echo !empty($_POST['aceitar_termos']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="aceitar-termos" style="font-size:.88rem;">
                  Li e concordo com os
                  <a href="#" class="fw-semibold text-warning text-decoration-none" data-termos="termos">Termos de Uso</a>
                  e a
                  <a href="#" class="fw-semibold text-warning text-decoration-none" data-termos="privacidade">Política de Privacidade (LGPD)</a>,
                  incluindo o tratamento dos meus dados pessoais e do documento de identidade para verificação.
                  <span class="text-danger">*</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Ações -->
          <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn btn-warning fw-bold btn-cad">Solicitar cadastro</button>
            <div class="d-flex gap-2">
              <a href="login.php" class="btn btn-outline-secondary btn-sm flex-grow-1" style="border-radius:8px;">Já tenho conta</a>
              <a href="cadastrarCliente.php" class="btn btn-outline-secondary btn-sm flex-grow-1" style="border-radius:8px;">Sou cliente</a>
            </div>
          </div>

        </form>
      </div>

    </div>
  </div>
</main>

<!-- Modal Termos/Privacidade -->
<div class="modal fade" id="modalTermos" tabindex="-1" aria-labelledby="modalTermosLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-fullscreen-sm-down modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTermosLabel">Termos de Uso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="iframeTermos" src="" style="width:100%;height:70vh;border:none;" loading="lazy"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/forms-helpers.js?v=4"></script>
<script src="../assets/js/cpf-validation-reload.js"></script>
<script>
document.querySelectorAll('[data-termos]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    var tipo = this.dataset.termos;
    var titulo = tipo === 'privacidade' ? 'Política de Privacidade (LGPD)' : 'Termos de Uso';
    document.getElementById('modalTermosLabel').textContent = titulo;
    var tema = localStorage.getItem('fn-theme') === 'dark' ? '&theme=dark' : '';
    document.getElementById('iframeTermos').src = 'termos.php?embed=1&tipo=' + tipo + tema;
    new bootstrap.Modal(document.getElementById('modalTermos')).show();
  });
});
</script>
</body>
</html>
