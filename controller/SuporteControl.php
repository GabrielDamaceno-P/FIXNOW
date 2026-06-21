<?php

require_once __DIR__ . '/../model/dao/SuporteDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class SuporteControl
{
    private SuporteDAO $suporteDAO;

    public string $usuarioTipo  = '';
    public int    $usuarioId    = 0;
    public string $usuarioNome  = '';
    public string $usuarioFoto  = '';
    public string $mensagem     = '';
    public string $erro         = '';
    public array  $tickets      = [];
    public array  $pagamentos   = [];
    public array  $chamados     = [];

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
            fixnow_checar_ativo_prestador($this->usuarioId, '../view/login.php');
        } elseif (isset($_SESSION['cliente_id'])) {
            $this->usuarioTipo = 'cliente';
            $this->usuarioId   = (int)$_SESSION['cliente_id'];
            $this->usuarioNome = $_SESSION['cliente_nome'] ?? 'Cliente';
            $this->usuarioFoto = $_SESSION['cliente_foto'] ?? '';
            fixnow_checar_ativo_cliente($this->usuarioId, '../view/login.php');
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
                $chamadoId  = ($v = (int)($_POST['chamado_id'] ?? 0)) > 0 ? $v : null;

                if (!$assunto || !$texto) {
                    $this->erro = 'Preencha assunto e mensagem.';
                } else {
                    $sid = $this->suporteDAO->abrir($this->usuarioTipo, $this->usuarioId, $assunto, $categoria, $prioridade, $texto, $chamadoId);
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

            } elseif ($acao === 'reabrir') {
                $sid = (int)($_POST['suporte_id'] ?? 0);
                if ($sid > 0 && $this->suporteDAO->reabrir($sid, $this->usuarioTipo, $this->usuarioId)) {
                    $this->mensagem = 'Ticket reaberto. Nossa equipe irá analisar em breve.';
                } else {
                    $this->erro = 'Não foi possível reabrir o ticket.';
                }
            }
        }

        $this->tickets = $this->suporteDAO->listarPorUsuario($this->usuarioTipo, $this->usuarioId);

        if (in_array($this->usuarioTipo, ['cliente', 'prestador'], true)) {
            $this->pagamentos = $this->suporteDAO->buscarPagamentosParaContexto($this->usuarioTipo, $this->usuarioId);
            $this->chamados   = $this->suporteDAO->buscarChamadosParaContexto($this->usuarioTipo, $this->usuarioId);
        }
    }
}
