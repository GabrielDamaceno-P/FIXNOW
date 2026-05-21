<?php
if (!isset($_SESSION['cliente_id'])) {
    header('Location: ' . str_repeat('../', $_navDepth ?? 1) . 'login.php'); exit;
}
$_d  = $_navDepth ?? 1;
$_r  = str_repeat('../', $_d);          // caminho até a raiz do projeto
$_v  = $_d > 1 ? str_repeat('../', $_d - 1) : ''; // caminho até view/

$paginaAtiva  = $paginaAtiva ?? '';
$clienteNome  = $_SESSION['cliente_nome'] ?? 'Cliente';
$clienteFoto  = $_SESSION['cliente_foto'] ?? '';
$_navNaoLidas = (int)($naoLidas ?? 0);
$_navInicial  = htmlspecialchars(mb_strtoupper(mb_substr($clienteNome, 0, 1)));
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_v ?>dashboardCliente.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#clienteMenu" aria-controls="clienteMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="clienteMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'dashboard' ? 'active' : '' ?>" href="<?= $_v ?>dashboardCliente.php">
            <i class="bi bi-house me-1"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'historico' ? 'active' : '' ?>" href="<?= $_v ?>historico.php">
            <i class="bi bi-clock-history me-1"></i>Histórico
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'rastreamento' ? 'active' : '' ?>" href="<?= $_v ?>rastreamento.php">
            <i class="bi bi-geo-alt me-1"></i>Rastreamento
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'suporte' ? 'active' : '' ?>" href="<?= $_v ?>suporte.php">
            <i class="bi bi-headset me-1"></i>Suporte
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item">
          <a class="nav-link position-relative px-2" href="<?= $_v ?>notificacoes.php" title="Notificações">
            <i class="bi bi-bell fs-5"></i>
            <?php if ($_navNaoLidas > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;padding:.25em .45em"><?= $_navNaoLidas ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($clienteFoto): ?>
              <img src="<?= $_r ?><?= htmlspecialchars($clienteFoto) ?>" alt="Foto" width="32" height="32" class="rounded-circle border border-2 border-white border-opacity-50" style="object-fit:cover">
            <?php else: ?>
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:.85rem"><?= $_navInicial ?></span>
            <?php endif; ?>
            <span class="d-none d-lg-inline text-truncate" style="max-width:120px"><?= htmlspecialchars($clienteNome) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:180px">
            <li class="px-3 py-2 border-bottom">
              <small class="text-muted d-block" style="font-size:.7rem">Logado como</small>
              <strong class="d-block text-truncate" style="font-size:.85rem"><?= htmlspecialchars($clienteNome) ?></strong>
            </li>
            <li><a class="dropdown-item py-2" href="<?= $_v ?>perfil.php"><i class="bi bi-person me-2 text-primary"></i>Meu Perfil</a></li>
            <li><a class="dropdown-item py-2" href="<?= $_v ?>suporte.php"><i class="bi bi-headset me-2 text-primary"></i>Suporte</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="<?= $_r ?>logout.php?entidade=cliente"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
