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

if (!$usuario) { header('Location: ' . ($usuarioTipo === 'prestador' ? 'prestador/dashboardPrestador.php' : 'dashboardCliente.php')); exit; }

$dashLink = $usuarioTipo === 'prestador' ? 'prestador/dashboardPrestador.php' : 'dashboardCliente.php';
$sairLink = $usuarioTipo === 'prestador' ? '../logout.php?entidade=prestador' : '../logout.php';
$fotoUrl  = $usuario->fotoPerfil ?? '';
$isPrestador = $usuarioTipo === 'prestador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meu Perfil - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .perfil-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .perfil-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .perfil-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .perfil-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}

    .perfil-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.4rem;box-shadow:0 3px 10px rgba(13,27,61,.05);margin-bottom:1.25rem}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}

    .avatar-wrap{width:120px;height:120px;border-radius:50%;overflow:hidden;border:3px solid #e8ecf3;margin:0 auto .75rem;flex-shrink:0;display:flex;align-items:center;justify-content:center}
    .avatar-wrap img{width:100%;height:100%;object-fit:cover}
    .avatar-init{width:100%;height:100%;background:linear-gradient(135deg,#0d1b3d,#1a2b63);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:2.2rem}
    .avatar-wrap.destaque-border{border-color:#fbbf24;box-shadow:0 0 0 3px rgba(251,191,36,.3)}

    .stat-chip{background:#f8fafc;border:1px solid #e8ecf3;border-radius:10px;padding:.45rem .75rem;display:flex;align-items:center;gap:.4rem;font-size:.82rem}
    .stat-chip .val{font-weight:700;color:#0d1b3d}

    .hist-badge{display:inline-block;padding:.2rem .55rem;border-radius:6px;font-size:.72rem;font-weight:600}
    .hist-badge-concluido{background:#dcfce7;color:#16a34a}
    .hist-badge-aberto{background:#dbeafe;color:#1d4ed8}
    .hist-badge-cancelado{background:#fee2e2;color:#dc2626}
    .hist-badge-andamento{background:#fef9c3;color:#854d0e}
    .hist-badge-default{background:#f3f4f6;color:#374151}

    .aval-item{padding:.75rem 0;border-bottom:1px solid #f0f3fa}
    .aval-item:last-child{border-bottom:none}
    .stars-display{font-size:1rem;letter-spacing:1px}

    [data-theme="dark"] .perfil-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .stat-chip{background:#252e45;border-color:#2e3650}
    [data-theme="dark"] .stat-chip .val{color:#e4e8f4}
    [data-theme="dark"] .aval-item{border-bottom-color:#2e3650}
    [data-theme="dark"] .avatar-wrap{border-color:#2e3650}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top shadow-sm" style="background:linear-gradient(90deg,#0d1b3d,#1a2b63);">
  <div class="container">
    <a class="navbar-brand fw-bold text-white" href="../index.php">Fix Now</a>
    <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#perfilMenu" aria-controls="perfilMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse" id="perfilMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link text-white-50" href="<?= $dashLink ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6-.354.353V14.5A1.5 1.5 0 0 0 2.5 16h4a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5h4a1.5 1.5 0 0 0 1.5-1.5V7.5l-.354-.354z"/></svg>
            Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="perfil.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.029 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
            Meu Perfil
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($fotoUrl): ?>
              <img src="../<?= htmlspecialchars($fotoUrl) ?>" alt="" width="32" height="32" class="rounded-circle border border-2" style="border-color:rgba(255,255,255,.3)!important;object-fit:cover">
            <?php else: ?>
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold flex-shrink-0"
                    style="width:32px;height:32px;font-size:.85rem;background:rgba(255,255,255,.15);color:#fff">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($usuario->nome ?? '', 0, 1))) ?>
              </span>
            <?php endif; ?>
            <span class="d-none d-lg-inline text-white text-truncate" style="max-width:120px"><?= htmlspecialchars($usuario->nome ?? '') ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:180px">
            <li class="px-3 py-2 border-bottom">
              <small class="text-muted d-block" style="font-size:.7rem">Logado como</small>
              <strong class="d-block text-truncate" style="font-size:.85rem"><?= htmlspecialchars($usuario->nome ?? '') ?></strong>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="<?= $sairLink ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-2"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
              Sair
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4 mt-5">

  <!-- Hero -->
  <div class="perfil-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1><?= $isPrestador ? '🔧 Perfil do Prestador' : '👤 Meu Perfil' ?></h1>
      <p><?= $isPrestador ? 'Gerencie seus dados, especialidade e segurança da conta.' : 'Mantenha seus dados atualizados e acompanhe seu histórico.' ?></p>
    </div>
  </div>

  <?php if ($mensagem): ?>
    <div class="alert alert-success alert-dismissible fade show">
      ✅ <?= htmlspecialchars($mensagem) ?>
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

    <!-- Sidebar -->
    <div class="col-lg-4">
      <div class="perfil-card text-center">
        <?php
          $isDestaque = $isPrestador && ($usuario->destaque ?? 0);
        ?>
        <div class="avatar-wrap mx-auto <?= $isDestaque ? 'destaque-border' : '' ?>">
          <?php if ($fotoUrl): ?>
            <img src="../<?= htmlspecialchars($fotoUrl) ?>" alt="Foto do perfil">
          <?php else: ?>
            <div class="avatar-init"><?= htmlspecialchars(mb_strtoupper(mb_substr($usuario->nome ?? '', 0, 1))) ?></div>
          <?php endif; ?>
        </div>

        <h5 class="fw-bold mb-1" style="font-size:1.05rem"><?= htmlspecialchars($usuario->nome ?? '') ?></h5>
        <p class="text-muted mb-2" style="font-size:.83rem"><?= htmlspecialchars($usuario->email ?? '') ?></p>

        <div class="d-flex gap-2 justify-content-center flex-wrap mb-3">
          <?php if ($isPrestador): ?>
            <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:.75rem;">Prestador</span>
            <?php if ($isDestaque): ?>
              <span class="badge" style="background:#fef9c3;color:#713f12;font-size:.75rem;">★ Destaque</span>
            <?php endif; ?>
          <?php else: ?>
            <span class="badge" style="background:#f0fdf4;color:#15803d;font-size:.75rem;">Cliente</span>
            <?php
              $genColor = match($usuario->genero ?? '') {
                'Feminino'  => 'background:#fce7f3;color:#be185d;',
                'Masculino' => 'background:#eff6ff;color:#1d4ed8;',
                default     => 'background:#f3f4f6;color:#374151;'
              };
            ?>
            <span class="badge" style="font-size:.75rem;<?= $genColor ?>"><?= htmlspecialchars($usuario->genero ?? '') ?></span>
          <?php endif; ?>
        </div>

        <?php if ($isPrestador): ?>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <div class="stat-chip">
            <span style="color:#ca8a04;">⭐</span>
            <span class="val"><?= number_format($usuario->avaliacaoMedia ?? 0, 1, ',', '.') ?></span>
            <span class="text-muted" style="font-size:.75rem">avaliação</span>
          </div>
          <?php if ($usuario->especialidade ?? ''): ?>
          <div class="stat-chip">
            <span>🔩</span>
            <span class="val" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($usuario->especialidade) ?>
            </span>
          </div>
          <?php endif; ?>
        </div>
        <?php else: ?>
          <?php if ($usuario->endereco ?? ''): ?>
          <div class="stat-chip justify-content-center">
            <span>📍</span>
            <span class="text-muted" style="font-size:.8rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($usuario->endereco) ?>
            </span>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Resumo rápido -->
      <div class="perfil-card">
        <div class="secao-titulo"><span>📋</span><span>Resumo</span></div>
        <div class="d-flex flex-column gap-2" style="font-size:.85rem">
          <div class="d-flex justify-content-between">
            <span class="text-muted">Chamados no histórico</span>
            <span class="fw-bold"><?= count($historico) ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Avaliações</span>
            <span class="fw-bold"><?= count($avaliacoes) ?></span>
          </div>
          <?php if ($cpfMascara): ?>
          <div class="d-flex justify-content-between">
            <span class="text-muted">CPF</span>
            <span class="fw-semibold" style="font-family:monospace"><?= htmlspecialchars($cpfMascara) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Conteúdo principal -->
    <div class="col-lg-8">

      <!-- Dados pessoais -->
      <div class="perfil-card">
        <div class="secao-titulo"><span>✏️</span><span>Dados pessoais</span></div>
        <form method="post" enctype="multipart/form-data" class="row g-3" novalidate>
          <input type="hidden" name="acao" value="atualizar">
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:.88rem">Nome completo <span class="text-danger">*</span></label>
            <input type="text" name="nome" class="form-control form-control-sm" required
                   value="<?= htmlspecialchars($usuario->nome ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:.88rem">Telefone <span class="text-danger">*</span></label>
            <input type="text" name="telefone" class="form-control form-control-sm" required
                   value="<?= htmlspecialchars($usuario->telefone ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:.88rem">CPF</label>
            <input type="text" class="form-control form-control-sm" readonly
                   style="background:#f8fafc"
                   value="<?= $cpfMascara ? htmlspecialchars($cpfMascara) : '—' ?>">
          </div>

          <?php if ($isPrestador): ?>
          <div class="col-12">
            <label class="form-label fw-semibold" style="font-size:.88rem">Especialidade</label>
            <input type="text" name="especialidade" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($usuario->especialidade ?? '') ?>">
          </div>
          <?php else: ?>
          <div class="col-md-5">
            <label class="form-label fw-semibold" style="font-size:.88rem">CEP</label>
            <input type="text" name="cep" id="perfil-cep" class="form-control form-control-sm"
                   maxlength="9" placeholder="00000-000" autocomplete="off"
                   value="<?php
                     $cepVal = $usuario->cep ?? '';
                     $cepDigits = preg_replace('/\D/', '', $cepVal);
                     echo htmlspecialchars(strlen($cepDigits) === 8 ? substr($cepDigits,0,5).'-'.substr($cepDigits,5) : $cepVal);
                   ?>">
            <small class="js-cep-perfil-status text-muted" style="font-size:.75rem"></small>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" style="font-size:.88rem">Endereço</label>
            <input type="text" name="endereco" id="perfil-endereco" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($usuario->endereco ?? '') ?>">
          </div>
          <?php endif; ?>

          <div class="col-12">
            <label class="form-label fw-semibold" style="font-size:.88rem">Nova foto de perfil <span class="text-muted fw-normal">(opcional)</span></label>
            <input type="file" name="foto_perfil" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-warning btn-sm fw-bold px-4">Salvar alterações</button>
          </div>
        </form>
      </div>

      <!-- Alterar senha -->
      <div class="perfil-card">
        <div class="secao-titulo"><span>🔒</span><span>Alterar senha</span></div>
        <form method="post" class="row g-3" novalidate>
          <input type="hidden" name="acao" value="senha">
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:.88rem">Senha atual</label>
            <input type="password" name="senha_atual" class="form-control form-control-sm" required autocomplete="current-password">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:.88rem">Nova senha</label>
            <input type="password" name="nova_senha" class="form-control form-control-sm" required minlength="6" autocomplete="new-password">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:.88rem">Confirmar</label>
            <input type="password" name="confirmar_nova_senha" class="form-control form-control-sm" required autocomplete="new-password">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold px-4">Alterar senha</button>
          </div>
        </form>
      </div>

      <!-- Histórico de serviços -->
      <div class="perfil-card">
        <div class="secao-titulo">
          <span>📂</span>
          <span>Histórico de serviços</span>
          <span class="badge ms-1" style="background:#e8ecf3;color:#0d1b3d;font-size:.72rem"><?= count($historico) ?></span>
        </div>
        <?php if (!$historico): ?>
          <div class="text-center py-4 text-muted" style="font-size:.88rem">
            <div style="font-size:2rem;margin-bottom:.4rem">📭</div>
            Nenhum registro ainda.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th style="font-size:.78rem">#</th>
                  <th style="font-size:.78rem">Categoria</th>
                  <th style="font-size:.78rem">Status</th>
                  <th style="font-size:.78rem">Data</th>
                  <th style="font-size:.78rem"><?= $isPrestador ? 'Cliente' : 'Prestador' ?></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($historico as $h):
                $statusNorm = mb_strtolower($h['status'] ?? '');
                $badgeClass = match(true) {
                  str_contains($statusNorm, 'conclu')  => 'hist-badge-concluido',
                  str_contains($statusNorm, 'aberto')  => 'hist-badge-aberto',
                  str_contains($statusNorm, 'cancel')  => 'hist-badge-cancelado',
                  str_contains($statusNorm, 'andamento') || str_contains($statusNorm, 'aceito') => 'hist-badge-andamento',
                  default => 'hist-badge-default',
                };
              ?>
                <tr>
                  <td class="text-muted" style="font-size:.8rem"><?= (int)$h['id'] ?></td>
                  <td style="font-size:.83rem"><?= htmlspecialchars($h['categoria']) ?></td>
                  <td><span class="hist-badge <?= $badgeClass ?>"><?= htmlspecialchars($h['status']) ?></span></td>
                  <td class="text-muted" style="font-size:.8rem"><?= date('d/m/Y', strtotime($h['criado_em'])) ?></td>
                  <td style="font-size:.83rem"><?= htmlspecialchars($h['outra_parte'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Avaliações -->
      <div class="perfil-card">
        <div class="secao-titulo">
          <span>⭐</span>
          <span><?= $isPrestador ? 'Avaliações recebidas' : 'Avaliações enviadas' ?></span>
          <span class="badge ms-1" style="background:#e8ecf3;color:#0d1b3d;font-size:.72rem"><?= count($avaliacoes) ?></span>
        </div>
        <?php if (!$avaliacoes): ?>
          <div class="text-center py-4 text-muted" style="font-size:.88rem">
            <div style="font-size:2rem;margin-bottom:.4rem">💬</div>
            Nenhuma avaliação registrada.
          </div>
        <?php else: ?>
          <?php foreach ($avaliacoes as $a):
            $outraParte = $isPrestador ? ($a->clienteNome ?? '') : ($a->tecnicoNome ?? '');
            $nota = (int)($a->nota ?? 0);
            $stars = str_repeat('★', $nota) . str_repeat('☆', max(0, 5 - $nota));
            $starColor = $nota >= 4 ? '#ca8a04' : ($nota >= 3 ? '#d97706' : '#dc2626');
          ?>
          <div class="aval-item">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="stars-display" style="color:<?= $starColor ?>;font-size:.95rem"><?= $stars ?></span>
              <span class="fw-semibold" style="font-size:.85rem"><?= $nota ?>/5</span>
              <span class="text-muted" style="font-size:.8rem">— Chamado #<?= (int)$a->chamadoId ?></span>
              <?php if ($outraParte): ?>
                <span class="badge ms-1" style="background:#f3f4f6;color:#374151;font-size:.7rem">
                  <?= htmlspecialchars($outraParte) ?>
                </span>
              <?php endif; ?>
            </div>
            <?php if (!empty($a->comentario)): ?>
              <p class="mb-1 text-muted" style="font-size:.82rem;font-style:italic">"<?= htmlspecialchars($a->comentario) ?>"</p>
            <?php endif; ?>
            <span class="text-muted" style="font-size:.75rem"><?= date('d/m/Y H:i', strtotime($a->criadoEm)) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
(function () {
  var cepInput = document.getElementById('perfil-cep');
  var endInput = document.getElementById('perfil-endereco');
  var statusEl = document.querySelector('.js-cep-perfil-status');
  if (!cepInput) return;

  function setStatus(msg, erro) {
    if (!statusEl) return;
    statusEl.textContent = msg;
    statusEl.className = 'js-cep-perfil-status small mt-1 ' + (erro ? 'text-danger' : 'text-muted');
  }

  cepInput.addEventListener('input', function () {
    var d = cepInput.value.replace(/\D/g, '').slice(0, 8);
    cepInput.value = d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
    if (d.length < 8) { setStatus('', false); return; }

    setStatus('Buscando endereço...', false);
    fetch('https://viacep.com.br/ws/' + d + '/json/')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.erro) { setStatus('CEP não encontrado.', true); return; }
        var partes = [data.logradouro, data.bairro, data.localidade, data.uf].filter(Boolean);
        if (endInput) endInput.value = partes.join(', ');
        setStatus('Endereço preenchido automaticamente.', false);
      })
      .catch(function () { setStatus('Erro ao buscar CEP.', true); });
  });
})();
</script>
</body>
</html>
