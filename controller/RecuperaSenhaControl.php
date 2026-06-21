<?php

require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class RecuperaSenhaControl
{
    private ClienteDAO $clienteDAO;
    private TecnicoDAO $tecnicoDAO;

    public string  $mensagem   = '';
    public string  $erro       = '';
    public ?string $senhaTemp  = null;
    public int     $etapa      = 1;

    public function __construct()
    {
        $this->clienteDAO = new ClienteDAO();
        $this->tecnicoDAO = new TecnicoDAO();
    }

    public function processar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $etapa = (int)($_POST['etapa'] ?? 1);

        if ($etapa === 1) {
            $this->verificarIdentidade();
        } elseif ($etapa === 2) {
            $this->gerarSenhaTemporaria();
        }
    }

    private function verificarIdentidade(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (!$email) { $this->erro = 'Informe o e-mail.'; return; }

        if (!$this->clienteDAO->emailExiste($email) && !$this->tecnicoDAO->emailExiste($email)) {
            $this->erro = 'E-mail não encontrado.'; return;
        }

        $_SESSION['recupera_email'] = $email;
        $this->etapa = 2;
        $this->mensagem = 'E-mail confirmado. Clique abaixo para gerar uma senha temporária.';
    }

    private function gerarSenhaTemporaria(): void
    {
        $email = $_SESSION['recupera_email'] ?? '';
        if (!$email) { $this->etapa = 1; return; }

        $chars     = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $senhaTemp = '';
        for ($i = 0; $i < 10; $i++) {
            $senhaTemp .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $hash = password_hash($senhaTemp, PASSWORD_DEFAULT);

        if (!$this->clienteDAO->atualizarSenhaPorEmail($email, $hash)) {
            $this->tecnicoDAO->atualizarSenhaPorEmail($email, $hash);
        }

        unset($_SESSION['recupera_email']);
        $this->senhaTemp = $senhaTemp;
        $this->etapa     = 3;
        $this->mensagem  = 'Senha temporária gerada. Use-a para fazer login e altere no perfil.';
    }
}
