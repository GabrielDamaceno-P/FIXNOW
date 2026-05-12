<?php

require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';

class NotificacoesControl
{
    private NotificacaoDAO $notifDAO;

    public string $usuarioTipo  = '';
    public int    $usuarioId    = 0;
    public string $usuarioNome  = '';
    public string $usuarioFoto  = '';
    public int    $naoLidas     = 0;
    public array  $notificacoes = [];

    public function __construct()
    {
        $this->notifDAO = new NotificacaoDAO();
    }

    public function verificarSessao(): void
    {
        if (isset($_SESSION['admin_id'])) {
            $this->usuarioTipo = 'admin';
            $this->usuarioId   = (int)$_SESSION['admin_id'];
            $this->usuarioNome = $_SESSION['admin_nome'] ?? 'Admin';
        } elseif (isset($_SESSION['tecnico_id'])) {
            $this->usuarioTipo = 'prestador';
            $this->usuarioId   = (int)$_SESSION['tecnico_id'];
            $this->usuarioNome = $_SESSION['tecnico_nome'] ?? 'Prestador';
            $this->usuarioFoto = $_SESSION['tecnico_foto'] ?? '';
        } elseif (isset($_SESSION['cliente_id'])) {
            $this->usuarioTipo = 'cliente';
            $this->usuarioId   = (int)$_SESSION['cliente_id'];
            $this->usuarioNome = $_SESSION['cliente_nome'] ?? 'Cliente';
            $this->usuarioFoto = $_SESSION['cliente_foto'] ?? '';
        } else {
            header('Location: ../view/login.php'); exit;
        }
    }

    public function processar(): void
    {
        $this->verificarSessao();

        if (isset($_GET['lida'])) {
            $nid = (int)$_GET['lida'];
            if ($nid > 0) {
                match($this->usuarioTipo) {
                    'admin'     => $this->notifDAO->marcarLidaAdmin($nid),
                    'prestador' => $this->notifDAO->marcarLidaTecnico($nid, $this->usuarioId),
                    default     => $this->notifDAO->marcarLidaCliente($nid, $this->usuarioId),
                };
            }
            header('Location: notificacoes.php'); exit;
        }

        if (isset($_POST['marcar_todas'])) {
            match($this->usuarioTipo) {
                'admin'     => $this->notifDAO->marcarTodasLidasAdmin(),
                'prestador' => $this->notifDAO->marcarTodasLidasTecnico($this->usuarioId),
                default     => $this->notifDAO->marcarTodasLidasCliente($this->usuarioId),
            };
            header('Location: notificacoes.php'); exit;
        }

        $this->notificacoes = match($this->usuarioTipo) {
            'admin'     => $this->notifDAO->listarAdmin(60),
            'prestador' => $this->notifDAO->listarPorTecnico($this->usuarioId, 60),
            default     => $this->notifDAO->listarPorCliente($this->usuarioId, 60),
        };

        $this->naoLidas = count(array_filter($this->notificacoes, fn($n) => !$n->lida));
    }
}
