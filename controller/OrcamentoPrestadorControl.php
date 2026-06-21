<?php

require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dto/OrcamentoDTO.php';
require_once __DIR__ . '/../includes/helpers.php';

class OrcamentoPrestadorControl
{
    private OrcamentoDAO   $orcamentoDAO;
    private ChamadoDAO     $chamadoDAO;
    private NotificacaoDAO $notifDAO;

    public int    $tecnicoId          = 0;
    public int    $naoLidas           = 0;
    public string $mensagem           = '';
    public string $erro               = '';
    public array  $chamadosDisponiveis = [];
    public array  $meusOrcamentos     = [];
    public int    $chamadoSelecionado  = 0;

    public function __construct()
    {
        $this->orcamentoDAO = new OrcamentoDAO();
        $this->chamadoDAO   = new ChamadoDAO();
        $this->notifDAO     = new NotificacaoDAO();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];
        fixnow_checar_ativo_prestador($this->tecnicoId, '../login.php');
    }

    public function processar(): void
    {
        $this->verificarSessao();
        $this->chamadoSelecionado = isset($_GET['chamado']) ? (int)$_GET['chamado'] : 0;
        $this->naoLidas = $this->notifDAO->contarNaoLidasTecnico($this->tecnicoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $rows = $this->orcamentoDAO->listarDisponiveisParaTecnico($this->tecnicoId);
        foreach ($rows as &$ch) {
            $fotos = $this->chamadoDAO->listarFotos((int)$ch['id']);
            $ch['fotos'] = array_column($fotos, 'foto_path');
            if (empty($ch['fotos']) && !empty($ch['foto_path'])) {
                $ch['fotos'] = [$ch['foto_path']];
            }
        }
        unset($ch);
        $this->chamadosDisponiveis = $rows;

        $this->meusOrcamentos = $this->orcamentoDAO->listarPorTecnico($this->tecnicoId);
    }

    private function processarPost(): void
    {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'enviar') {
            $chamadoId = (int)($_POST['chamado_id'] ?? 0);
            $valor     = (float)str_replace(',', '.', $_POST['valor'] ?? '0');
            $descricao = trim($_POST['descricao'] ?? '');
            $prazo     = (int)($_POST['prazo_dias'] ?? 0) ?: null;

            if ($chamadoId <= 0 || $valor <= 0) {
                $this->erro = 'Informe o chamado e um valor válido.'; return;
            }
            if ($this->orcamentoDAO->jaEnviou($chamadoId, $this->tecnicoId)) {
                $this->erro = 'Você já enviou um orçamento para este chamado.'; return;
            }

            $dto            = new OrcamentoDTO();
            $dto->chamadoId = $chamadoId;
            $dto->tecnicoId = $this->tecnicoId;
            $dto->valor     = $valor;
            $dto->descricao = $descricao;
            $dto->prazoDias = $prazo;
            $this->orcamentoDAO->inserir($dto);

            $chamado = $this->chamadoDAO->buscarPorId($chamadoId);
            if ($chamado) {
                fixnow_notificar_cliente($chamado->clienteId,
                    'Você recebeu um orçamento de R$ ' . number_format($valor, 2, ',', '.') .
                    ' para o chamado #' . $chamadoId . '.', $chamadoId);
            }
            $this->mensagem = 'Orçamento enviado com sucesso.';

        } elseif ($acao === 'alterar') {
            $oid       = (int)($_POST['orcamento_id'] ?? 0);
            $valor     = (float)str_replace(',', '.', $_POST['valor'] ?? '0');
            $descricao = trim($_POST['descricao'] ?? '');

            if ($oid <= 0 || $valor <= 0) {
                $this->erro = 'Informe um valor válido.'; return;
            }
            if ($this->orcamentoDAO->atualizarPorTecnico($oid, $this->tecnicoId, $valor, $descricao)) {
                $chamadoId = $this->orcamentoDAO->buscarChamadoId($oid, $this->tecnicoId);
                if ($chamadoId !== null) {
                    $chamado = $this->chamadoDAO->buscarPorId($chamadoId);
                    if ($chamado) {
                        $valorFmt = number_format($valor, 2, ',', '.');
                        fixnow_notificar_cliente($chamado->clienteId,
                            "O orçamento do chamado #{$chamado->id} foi atualizado para R$ {$valorFmt}. Acesse o painel para revisar.", $chamado->id);
                    }
                }
                $this->mensagem = 'Orçamento atualizado com sucesso.';
            } else {
                $this->erro = 'Não foi possível alterar (o orçamento pode já ter sido aceito ou recusado).';
            }

        } elseif ($acao === 'cancelar') {
            $oid = (int)($_POST['orcamento_id'] ?? 0);
            if ($oid > 0 && $this->orcamentoDAO->cancelarPorTecnico($oid, $this->tecnicoId)) {
                $this->mensagem = 'Orçamento cancelado.';
            } else {
                $this->erro = 'Não foi possível cancelar.';
            }
        }
    }
}
