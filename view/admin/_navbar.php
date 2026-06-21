<?php
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php'); exit;
}

$adminId          = (int)$_SESSION['admin_id'];
$adminNome        = $_SESSION['admin_nome'] ?? 'Admin';
$isMaster         = true;
$pdo              = Conexao::getConexao();
$primaryAdminId   = (int)$pdo->query("SELECT MIN(id) FROM admin")->fetchColumn();
$isCurrentPrimary = ($adminId === $primaryAdminId);
$paginaAtiva = $paginaAtiva ?? '';

try { $naoLidas  = (int)$pdo->query("SELECT COUNT(*) FROM notificacao WHERE tipo_destinatario='admin' AND lida=0")->fetchColumn(); }
catch (Exception $e) { $naoLidas = 0; }

try { $supAbertos = (int)$pdo->query("SELECT COUNT(*) FROM suporte WHERE status='Aberto'")->fetchColumn(); }
catch (Exception $e) { $supAbertos = 0; }

$_navInicial = htmlspecialchars(mb_strtoupper(mb_substr($adminNome, 0, 1)));
$gestaoAtiva = in_array($paginaAtiva, ['clientes','prestadores','categorias','destaques']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="../../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#adminMenu" aria-controls="adminMenu" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminMenu">
      <ul class="navbar-nav me-auto">

        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'painel' ? 'active' : '' ?>" href="painelAdmin.php">
            <i class="bi bi-speedometer2 me-1"></i>Painel
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $gestaoAtiva ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-grid me-1"></i>Gestão
          </a>
          <ul class="dropdown-menu shadow border-0">
            <li><a class="dropdown-item <?= $paginaAtiva === 'clientes' ? 'active' : '' ?>" href="clientes.php"><i class="bi bi-people me-2 text-primary"></i>Clientes</a></li>
            <li><a class="dropdown-item <?= $paginaAtiva === 'prestadores' ? 'active' : '' ?>" href="prestadores.php"><i class="bi bi-person-gear me-2 text-primary"></i>Prestadores</a></li>
            <li><a class="dropdown-item <?= $paginaAtiva === 'categorias' ? 'active' : '' ?>" href="categorias.php"><i class="bi bi-tags me-2 text-primary"></i>Categorias</a></li>
            <li><a class="dropdown-item <?= $paginaAtiva === 'destaques' ? 'active' : '' ?>" href="destaques.php"><i class="bi bi-star me-2 text-primary"></i>Destaques</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'suporte' ? 'active' : '' ?>" href="suporteAdmin.php">
            <i class="bi bi-headset me-1"></i>Suporte
            <?php if ($supAbertos > 0): ?>
            <span class="badge bg-warning text-dark ms-1"><?= $supAbertos ?></span>
            <?php endif; ?>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'relatorios' ? 'active' : '' ?>" href="relatorios.php">
            <i class="bi bi-bar-chart me-1"></i>Relatórios
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva === 'admins' ? 'active' : '' ?>" href="admins.php">
            <i class="bi bi-shield-check me-1"></i>Admins
          </a>
        </li>

      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">

        <li class="nav-item">
          <a class="nav-link position-relative px-2" href="../notificacoes.php" title="Notificações">
            <i class="bi bi-bell fs-5"></i>
            <?php if ($naoLidas > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;padding:.25em .45em"><?= $naoLidas ?></span>
            <?php endif; ?>
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:.85rem"><?= $_navInicial ?></span>
            <span class="d-none d-lg-inline text-truncate" style="max-width:120px"><?= htmlspecialchars($adminNome) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width:200px">
            <li class="px-3 py-2 border-bottom">
              <small class="text-muted d-block" style="font-size:.7rem">Logado como</small>
              <strong class="d-block" style="font-size:.85rem"><?= htmlspecialchars($adminNome) ?></strong>
            </li>
            <li><a class="dropdown-item py-2" href="../perfil.php"><i class="bi bi-person me-2 text-primary"></i>Meu Perfil</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="../../logout.php?entidade=admin"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>
