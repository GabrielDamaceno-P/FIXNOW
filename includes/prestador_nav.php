<?php
if (!isset($_SESSION['tecnico_id'])) {
    header('Location: ../../login.php'); exit;
}
$paginaAtiva = $paginaAtiva ?? '';
$tecnicoNome = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto = $_SESSION['tecnico_foto'] ?? '';
$_navNaoLidas = (int)($naoLidas ?? 0);
$_navInicial  = htmlspecialchars(mb_strtoupper(mb_substr($tecnicoNome, 0, 1)));
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#prestMenu" aria-controls="prestMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="prestMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'chamados' ? 'active' : '' ?>" href="dashboardPrestador.php">
            <i class="bi bi-house me-1"></i>Chamados
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'calendario' ? 'active' : '' ?>" href="calendario.php">
            <i class="bi bi-calendar3 me-1"></i>Calendário
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'orcamentos' ? 'active' : '' ?>" href="orcamento.php">
            <i class="bi bi-file-earmark-text me-1"></i>Orçamentos
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'servicos' ? 'active' : '' ?>" href="servicos.php">
            <i class="bi bi-tools me-1"></i>Serviços
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'portfolio' ? 'active' : '' ?>" href="portfolio.php">
            <i class="bi bi-images me-1"></i>Portfólio
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'financeiro' ? 'active' : '' ?>" href="financeiro.php">
            <i class="bi bi-cash-coin me-1"></i>Financeiro
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item">
          <a class="nav-link position-relative px-2" href="../notificacoes.php" title="Notificações">
            <i class="bi bi-bell fs-5"></i>
            <?php if ($_navNaoLidas > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;padding:.25em .45em"><?= $_navNaoLidas ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($tecnicoFoto): ?>
              <img src="../../<?= htmlspecialchars($tecnicoFoto) ?>" alt="Foto" width="32" height="32" class="rounded-circle border border-2 border-white border-opacity-50" style="object-fit:cover">
            <?php else: ?>
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:.85rem"><?= $_navInicial ?></span>
            <?php endif; ?>
            <span class="d-none d-lg-inline text-truncate" style="max-width:120px"><?= htmlspecialchars($tecnicoNome) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:180px">
            <li class="px-3 py-2 border-bottom">
              <small class="text-muted d-block" style="font-size:.7rem">Logado como</small>
              <strong class="d-block text-truncate" style="font-size:.85rem"><?= htmlspecialchars($tecnicoNome) ?></strong>
            </li>
            <li><a class="dropdown-item py-2" href="../perfil.php"><i class="bi bi-person me-2 text-primary"></i>Meu Perfil</a></li>
            <li><a class="dropdown-item py-2" href="../suporte.php"><i class="bi bi-headset me-2 text-primary"></i>Suporte</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="../../logout.php?entidade=prestador"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
