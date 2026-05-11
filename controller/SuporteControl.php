<?php

require_once __DIR__ . '/../model/dao/SuporteDAO.php';

class SuporteControl
{
    private SuporteDAO $suporteDAO;

    public string $usuarioTipo  = '';
    public int    $usuarioId    = 0;
    public string $usuarioNome  = '';
    public string $usuarioFoto  = '';
    public string $mensagem     = '';
    public string $erro         = '';
    /** @var SuporteDTO[] */
    public array  $tickets      = [];

    public function __construct()
    {
        $this->suporteDAO = new SuporteDAO();
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao = $_POST['acao'] ?? '';

            if ($acao === 'abrir') {
                $assunto    = trim($_POST['assunto']    ?? '');
                $texto      = trim($_POST['mensagem']   ?? '');
                $categoria  = in_array($_POST['categoria']  ?? '', SuporteDAO::CATEGORIAS,  true) ? $_POST['categoria']  : 'Outro';
                $prioridade = in_array($_POST['prioridade'] ?? '', SuporteDAO::PRIORIDADES, true) ? $_POST['prioridade'] : 'Normal';

                if (!$assunto || !$texto) {
                    $this->erro = 'Preencha assunto e mensagem.';
                } else {
                    $sid = $this->suporteDAO->abrir($this->usuarioTipo, $this->usuarioId, $assunto, $categoria, $prioridade, $texto);
                    if ($sid > 0) {
                        $this->mensagem = 'Ticket aberto com sucesso. Responderemos em breve.';
                    } else {
                        $this->erro = 'Erro ao criar ticket.';
                    }
                }

            } elseif ($acao === 'mensagem') {
                $sid   = (int)($_POST['suporte_id'] ?? 0);
                $texto = trim($_POST['mensagem'] ?? '');

                if ($sid <= 0 || $texto === '') {
                    $this->erro = 'A mensagem não pode estar vazia.';
                } elseif ($this->suporteDAO->adicionarMensagem($sid, $this->usuarioTipo, $this->usuarioId, $texto)) {
                    $this->mensagem = 'Mensagem enviada.';
                } else {
                    $this->erro = 'Não foi possível enviar a mensagem (ticket fechado ou não encontrado).';
                }
            }
        }

        $this->tickets = $this->suporteDAO->listarPorUsuario($this->usuarioTipo, $this->usuarioId);
    }
}
