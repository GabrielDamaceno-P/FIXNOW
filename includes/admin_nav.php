<?php
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}
$adminPerfil = $adminPerfil ?? (string)($_SESSION['admin_perfil'] ?? 'Master');
$isMaster    = $isMaster    ?? ($adminPerfil === 'Master');
$paginaAtiva = $paginaAtiva ?? '';
$adminNome   = $_SESSION['admin_nome'] ?? 'Admin';
try {
    $_stmtNcAdm = $pdo->query('SELECT COUNT(*) FROM notificacao WHERE tipo_destinatario = \'admin\' AND lida = 0');
    $_notifsAdmNaoLidas = (int)$_stmtNcAdm->fetchColumn();
} catch (PDOException $_e) { $_notifsAdmNaoLidas = 0; }
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="painelAdmin.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#adminMenu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="adminMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'painel' ? 'active' : ''; ?>" href="painel-admin.php">Painel</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'clientes' ? 'active' : ''; ?>" href="clientes.php">Clientes</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'prestadores' ? 'active' : ''; ?>" href="prestadores.php">Prestadores</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'categorias' ? 'active' : ''; ?>" href="categorias.php">Categorias</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'destaques' ? 'active' : ''; ?>" href="destaques.php">Destaques</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'suporte' ? 'active' : ''; ?>" href="suporte.php">Suporte</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'relatorios' ? 'active' : ''; ?>" href="relatorios.php">Relatórios</a></li>
        <?php if ($isMaster): ?>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'admins' ? 'active' : ''; ?>" href="admins.php">Admins</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo $paginaAtiva === 'notificacoes' ? 'active' : ''; ?>" href="../notificacoes.php">
            Notificações<?php if ($_notifsAdmNaoLidas > 0): ?><span class="badge bg-danger ms-1"><?php echo $_notifsAdmNaoLidas; ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="../perfil.php">Perfil (<?php echo htmlspecialchars($adminNome); ?>)</a></li>
        <li class="nav-item"><a class="nav-link" href="../logout.php?entidade=admin">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>
