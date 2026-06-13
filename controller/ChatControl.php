<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/MensagemDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class ChatControl
{
    private ChamadoDAO    $chamadoDAO;
    private MensagemDAO   $mensagemDAO;
    private NotificacaoDAO $notifDAO;
    private PDO           $pdo;

    public int    $usuarioId       = 0;
    public string $usuarioTipo     = '';
    public string $usuarioNome     = '';
    public string $usuarioFoto     = '';
    public int    $naoLidas        = 0;
    public int    $chamadoId       = 0;
    public ?object $chamado        = null;
    public array  $mensagens       = [];
    public string $erro            = '';
    public bool   $migracaoPendente = false;
    public bool   $chatAtivo       = true;

    public function __construct()
    {
        $this->chamadoDAO  = new ChamadoDAO();
        $this->mensagemDAO = new MensagemDAO();
        $this->notifDAO    = new NotificacaoDAO();
        $this->pdo         = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (isset($_SESSION['tecnico_id'])) {
            $this->usuarioId   = (int)$_SESSION['tecnico_id'];
            $this->usuarioTipo = 'prestador';
            $this->usuarioNome = $_SESSION['tecnico_nome'] ?? 'Prestador';
            $this->usuarioFoto = $_SESSION['tecnico_foto'] ?? '';
            fixnow_checar_ativo_prestador($this->usuarioId, '../view/login.php');
        } elseif (isset($_SESSION['cliente_id'])) {
            $this->usuarioId   = (int)$_SESSION['cliente_id'];
            $this->usuarioTipo = 'cliente';
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
        $this->chamadoId = (int)($_GET['chamado'] ?? 0);

        if (!$this->chamadoId) {
            header('Location: ../view/login.php'); exit;
        }

        $isPrestador = ($this->usuarioTipo === 'prestador');

        if (!$this->chamadoDAO->podeAcessarChat($this->chamadoId, $this->usuarioId, $isPrestador)) {
            header('Location: ../view/login.php'); exit;
        }

        $this->migracaoPendente = !$this->mensagemDAO->tabelaExiste();

        $chamadoDTO = $this->chamadoDAO->buscarPorId($this->chamadoId);
        $this->chamado = $chamadoDTO;

        if ($chamadoDTO && in_array($chamadoDTO->status, ['Negado', 'Concluído'], true)) {
            $this->chatAtivo = false;
        }

        if (!$this->migracaoPendente) {
            $tipoOposto = $isPrestador ? 'cliente' : 'prestador';
            $this->mensagemDAO->marcarLidas($this->chamadoId, $tipoOposto);

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->chatAtivo) {
                $texto = trim($_POST['mensagem'] ?? '');
                $arquivoPath = null;
                $arquivoNome = null;

                if (!empty($_FILES['arquivo']['name']) && $_FILES['arquivo']['error'] === 0) {
                    $uploadDir = dirname(__DIR__) . '/assets/img/uploads/chat/';
                    $resultado = fixnow_upload_file($_FILES['arquivo'], $uploadDir, 'chat');
                    if ($resultado) {
                        $arquivoPath = $resultado['path'];
                        $arquivoNome = $resultado['nome'];
                    } else {
                        $this->erro = 'Arquivo inválido ou muito grande (máx. 10 MB). Tipos aceitos: JPG, PNG, WEBP, GIF, PDF, DOC, DOCX, ZIP.';
                    }
                } elseif (!empty($_FILES['arquivo']['name']) && $_FILES['arquivo']['error'] !== 0) {
                    $this->erro = 'Falha ao receber o arquivo (código ' . (int)$_FILES['arquivo']['error'] . '). Verifique o tamanho máximo de upload.';
                }

                if ($this->erro === '' && ($texto !== '' || $arquivoPath !== null)) {
                    $this->mensagemDAO->inserir($this->chamadoId, $this->usuarioTipo, $this->usuarioId, $texto, $arquivoPath, $arquivoNome);
                    if ($isPrestador && $chamadoDTO) {
                        fixnow_notificar_cliente($this->pdo, $chamadoDTO->clienteId,
                            "Nova mensagem no chamado #{$this->chamadoId}.", $this->chamadoId);
                    } elseif (!$isPrestador && $chamadoDTO && $chamadoDTO->tecnicoId) {
                        fixnow_notificar_prestador($this->pdo, $chamadoDTO->tecnicoId,
                            "Nova mensagem no chamado #{$this->chamadoId}.", $this->chamadoId);
                    }
                    header('Location: chat.php?chamado=' . $this->chamadoId); exit;
                } elseif ($this->erro === '') {
                    header('Location: chat.php?chamado=' . $this->chamadoId); exit;
                }
                // Se há erro ($this->erro !== ''), não redireciona — a view exibe o erro
            }

            $this->mensagens = $this->mensagemDAO->listarPorChamado($this->chamadoId);
        }

        if ($this->usuarioTipo === 'prestador') {
            $this->naoLidas = $this->notifDAO->contarNaoLidasTecnico($this->usuarioId);
        } else {
            $this->naoLidas = $this->notifDAO->contarNaoLidasCliente($this->usuarioId);
        }
    }
}
