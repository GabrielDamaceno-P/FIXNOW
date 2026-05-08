<?php
if (!isset($_SESSION['tecnico_id'])) {
    header('Location: ../login.php');
    exit;
}
$paginaAtiva = $paginaAtiva ?? '';
$tecnicoNome = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto = $_SESSION['tecnico_foto'] ?? '';
$_tecnicoIdNav = (int)$_SESSION['tecnico_id'];
try {
    $_stmtNc = $pdo->prepare('SELECT COUNT(*) FROM notificacao WHERE tecnico_id = ? AND tipo_destinatario = \'prestador\' AND lida = 0');
    $_stmtNc->execute([$_tecnicoIdNav]);
    $_notifsNaoLidas = (int)$_stmtNc->fetchColumn();
} catch (PDOException $_e) { $_notifsNaoLidas = 0; }
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <span class="fn-user-badge me-2">
      <?php if ($tecnicoFoto): ?>
        <img src="../<?php echo htmlspecialchars($tecnicoFoto); ?>" alt="Foto" width="44" height="44">
      <?php else: ?>
        <span class="fallback"><?php echo htmlspecialchars(mb_substr($tecnicoNome, 0, 1)); ?></span>
      <?php endif; ?>
      <span><?php echo htmlspecialchars($tecnicoNome); ?></span>
    </span>
    <a class="navbar-brand fw-bold" href="../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#prestMenu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="prestMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'chamados' ? 'active' : ''; ?>" href="dashboard-prestador.php">Chamados</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'calendario' ? 'active' : ''; ?>" href="calendario.php">Calendário</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'orcamentos' ? 'active' : ''; ?>" href="orcamento.php">Orçamentos</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'servicos' ? 'active' : ''; ?>" href="servicos.php">Meus Serviços</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'portfolio' ? 'active' : ''; ?>" href="portfolio.php">Portfólio</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'financeiro' ? 'active' : ''; ?>" href="financeiro.php">Financeiro</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $paginaAtiva === 'suporte' ? 'active' : ''; ?>" href="../suporte.php">Suporte</a></li>
        <li class="nav-item">
          <a class="nav-link <?php echo $paginaAtiva === 'notificacoes' ? 'active' : ''; ?>" href="../notificacoes.php">
            Notificações<?php if ($_notifsNaoLidas > 0): ?><span class="badge bg-danger ms-1"><?php echo $_notifsNaoLidas; ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="../perfil.php">Perfil</a></li>
        <li class="nav-item"><a class="nav-link" href="../logout.php?entidade=prestador">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>
