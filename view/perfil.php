<?php
session_start();
require_once __DIR__ . '/../controller/PerfilControl.php';

$ctrl = new PerfilControl();
$ctrl->processar();

$usuario     = $ctrl->usuario;
$historico   = $ctrl->historico;
$avaliacoes  = $ctrl->avaliacoes;
$cpfMascara  = $ctrl->cpfMascara;
$titulo      = $ctrl->titulo;
$mensagem    = $ctrl->mensagem;
$erro        = $ctrl->erro;
$usuarioTipo = $ctrl->usuarioTipo;

$dashLink = $usuarioTipo === 'prestador' ? 'prestador/dashboardPrestador.php' : 'dashboardCliente.php';
$sairLink = $usuarioTipo === 'prestador' ? '../logout.php?entidade=prestador' : '../logout.php';
$fotoUrl  = $usuario['fotoPerfil'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meu Perfil - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#perfilMenu" aria-controls="perfilMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="perfilMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?php echo $dashLink; ?>"><i class="bi bi-house me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="perfil.php"><i class="bi bi-person me-1"></i>Perfil</a></li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($fotoUrl): ?>
              <img src="../<?php echo htmlspecialchars($fotoUrl); ?>" alt="" width="32" height="32" class="rounded-circle border border-2 border-white border-opacity-50" style="object-fit:cover">
            <?php else: ?>
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:.85rem"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($usuario['nome'] ?? '', 0, 1))); ?></span>
            <?php endif; ?>
            <span class="d-none d-lg-inline text-truncate" style="max-width:120px"><?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:180px">
            <li class="px-3 py-2 border-bottom">
              <small class="text-muted d-block" style="font-size:.7rem">Logado como</small>
              <strong class="d-block text-truncate" style="font-size:.85rem"><?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></strong>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="<?php echo $sairLink; ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <h2 class="mb-3"><?php echo htmlspecialchars($titulo); ?></h2>

  <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- Sidebar com foto -->
    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <?php if ($fotoUrl): ?>
            <img src="../<?php echo htmlspecialchars($fotoUrl); ?>" alt="Foto do perfil"
                 class="rounded-circle border shadow-sm mb-2"
                 style="width:140px;height:140px;object-fit:cover;">
          <?php else: ?>
            <div class="rounded-circle bg-secondary-subtle border d-inline-flex align-items-center justify-content-center mb-2"
                 style="width:140px;height:140px;">
              <span class="text-muted small">Sem foto</span>
            </div>
          <?php endif; ?>
          <h5 class="mb-0"><?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></h5>
          <p class="text-muted small mb-0"><?php echo htmlspecialchars($usuario['email'] ?? ''); ?></p>
        </div>
      </div>
    </div>

    <!-- Conteúdo principal -->
    <div class="col-lg-8">

      <!-- Dados pessoais -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h4 class="h6 text-uppercase text-muted mb-3">Dados pessoais</h4>
          <form method="post" enctype="multipart/form-data" class="row g-3" novalidate>
            <input type="hidden" name="acao" value="atualizar">
            <div class="col-md-6">
              <label class="form-label">Nome</label>
              <input type="text" name="nome" class="form-control" required value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" class="form-control" required value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">CPF</label>
              <input type="text" name="cpf" class="form-control" autocomplete="off"
                     value="<?php echo htmlspecialchars($cpfMascara); ?>" maxlength="14" inputmode="numeric">
            </div>
            <div class="col-md-6">
              <label class="form-label">Gênero</label>
              <select name="genero" class="form-select">
                <?php
                  $gopts = $usuarioTipo === 'prestador'
                      ? ['Feminino', 'Masculino', 'Outro']
                      : ['Feminino', 'Masculino', 'Outro', 'Prefiro não informar'];
                  $gAtual = $usuario['genero'] ?? '';
                  foreach ($gopts as $g):
                ?>
                  <option value="<?php echo $g; ?>" <?php echo $gAtual === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($usuarioTipo === 'prestador'): ?>
            <div class="col-12">
              <label class="form-label">Especialidade</label>
              <input type="text" name="especialidade" class="form-control" value="<?php echo htmlspecialchars($usuario['especialidade'] ?? ''); ?>">
            </div>
            <?php else: ?>
            <div class="col-12">
              <label class="form-label">Endereço</label>
              <input type="text" name="endereco" class="form-control" value="<?php echo htmlspecialchars($usuario['endereco'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">CEP</label>
              <input type="text" name="cep" class="form-control" value="<?php echo htmlspecialchars($usuario['cep'] ?? ''); ?>">
            </div>
            <?php endif; ?>
            <div class="col-12">
              <label class="form-label">Nova foto de perfil (opcional)</label>
              <input type="file" name="foto_perfil" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-warning fw-semibold">Salvar alterações</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Alterar senha -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h4 class="h6 text-uppercase text-muted mb-3">Alterar senha</h4>
          <form method="post" class="row g-3" novalidate>
            <input type="hidden" name="acao" value="senha">
            <div class="col-md-4">
              <label class="form-label">Senha atual</label>
              <input type="password" name="senha_atual" class="form-control" required autocomplete="current-password">
            </div>
            <div class="col-md-4">
              <label class="form-label">Nova senha</label>
              <input type="password" name="nova_senha" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
            <div class="col-md-4">
              <label class="form-label">Confirmar nova senha</label>
              <input type="password" name="confirmar_nova_senha" class="form-control" required autocomplete="new-password">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-outline-primary fw-semibold">Alterar senha</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Histórico de serviços -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h4 class="h6 text-uppercase text-muted mb-3">Histórico de serviços</h4>
          <?php if (!$historico): ?>
            <p class="text-muted mb-0">Nenhum registro ainda.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th>#</th><th>Categoria</th><th>Status</th><th>Data</th><th>Outra parte</th></tr></thead>
                <tbody>
                <?php foreach ($historico as $h): ?>
                  <tr>
                    <td><?php echo (int)$h['id']; ?></td>
                    <td><?php echo htmlspecialchars($h['categoria']); ?></td>
                    <td><?php echo htmlspecialchars($h['status']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($h['criado_em'])); ?></td>
                    <td><?php echo htmlspecialchars($h['outra_parte'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Avaliações -->
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h4 class="h6 text-uppercase text-muted mb-3">
            <?php echo $usuarioTipo === 'prestador' ? 'Avaliações recebidas' : 'Avaliações enviadas'; ?>
          </h4>
          <?php if (!$avaliacoes): ?>
            <p class="text-muted mb-0">Nenhuma avaliação registrada.</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($avaliacoes as $a): ?>
                <li class="list-group-item px-0">
                  <strong><?php echo (int)$a['nota']; ?>/5</strong>
                  — Chamado #<?php echo (int)$a['chamado_id']; ?>
                  <span class="text-muted small">(<?php echo htmlspecialchars($a['outra_parte'] ?? ''); ?>)</span>
                  <?php if (!empty($a['comentario'])): ?>
                    <div class="small text-muted"><?php echo htmlspecialchars($a['comentario']); ?></div>
                  <?php endif; ?>
                  <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($a['criado_em'])); ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
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
