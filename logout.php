<?php
// Encerra a sessão do usuário logado.

session_start();

$entidade = $_GET['entidade'] ?? 'todas';

if ($entidade === 'cliente') {
    unset($_SESSION['cliente_id'], $_SESSION['cliente_nome'], $_SESSION['cliente_genero'], $_SESSION['is_admin']);
} elseif ($entidade === 'prestador') {
    unset($_SESSION['tecnico_id'], $_SESSION['tecnico_nome'], $_SESSION['tecnico_especialidade'], $_SESSION['tecnico_genero']);
} elseif ($entidade === 'admin') {
    unset($_SESSION['admin_id'], $_SESSION['admin_nome'], $_SESSION['admin_perfil']);
} else {
    session_unset();
    session_destroy();
}

header('Location: index.php');
exit;
