<?php
/**
 * Navbar compartilhado das páginas admin.
 * Requer que session_start() já tenha sido chamado antes do include.
 * Expõe: $pdo, $adminId, $adminNome, $adminPerfil, $isMaster, $isOperacoes, $isFinanceiro, $naoLidas, $supAbertos
 */
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php'); exit;
}

$adminId      = (int)$_SESSION['admin_id'];
$adminNome    = $_SESSION['admin_nome'] ?? 'Admin';
$adminPerfil  = (string)($_SESSION['admin_perfil'] ?? 'Master');
if (!in_array($adminPerfil, ['Master', 'Operacoes', 'Financeiro'], true)) $adminPerfil = 'Master';
$isMaster     = $adminPerfil === 'Master';
$isOperacoes  = $adminPerfil === 'Operacoes';
$isFinanceiro = $adminPerfil === 'Financeiro';
$pdo          = Conexao::getConexao();
$paginaAtiva  = $paginaAtiva ?? '';

try { $naoLidas  = (int)$pdo->query("SELECT COUNT(*) FROM notificacao WHERE tipo_destinatario='admin' AND lida=0")->fetchColumn(); }
catch (Exception $e) { $naoLidas = 0; }

try { $supAbertos = (int)$pdo->query("SELECT COUNT(*) FROM suporte WHERE status='Aberto'")->fetchColumn(); }
catch (Exception $e) { $supAbertos = 0; }
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="../../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#adminMenu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="adminMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='painel'?'active':'' ?>" href="painelAdmin.php">Painel</a></li>
        <?php if ($isMaster || $isOperacoes): ?>
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='clientes'?'active':'' ?>" href="clientes.php">Clientes</a></li>
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='prestadores'?'active':'' ?>" href="prestadores.php">Prestadores</a></li>
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='servicos'?'active':'' ?>" href="servicos.php">Serviços</a></li>
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='categorias'?'active':'' ?>" href="categorias.php">Categorias</a></li>
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='destaques'?'active':'' ?>" href="destaques.php">Destaques</a></li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link <?= $paginaAtiva==='suporte'?'active':'' ?>" href="suporteAdmin.php">
            Suporte<?php if ($supAbertos > 0): ?><span class="badge bg-warning text-dark ms-1"><?= $supAbertos ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='relatorios'?'active':'' ?>" href="relatorios.php">Relatórios</a></li>
        <?php if ($isMaster): ?>
        <li class="nav-item"><a class="nav-link <?= $paginaAtiva==='admins'?'active':'' ?>" href="admins.php">Admins</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="../notificacoes.php">
            Notificações<?php if ($naoLidas > 0): ?><span class="badge bg-danger ms-1"><?= $naoLidas ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="../perfil.php">Perfil (<?= htmlspecialchars($adminNome) ?>)</a></li>
        <li class="nav-item"><a class="nav-link" href="../../logout.php?entidade=admin">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>
