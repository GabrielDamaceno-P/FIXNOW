<?php

require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dto/OrcamentoDTO.php';
require_once __DIR__ . '/../includes/helpers.php';

class OrcamentoPrestadorControl
{
    private OrcamentoDAO $orcamentoDAO;
    private ChamadoDAO   $chamadoDAO;
    private PDO          $pdo;

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
        $this->pdo          = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];
    }

    private function carregarNaoLidas(): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notificacao WHERE tecnico_id = ? AND tipo_destinatario = 'prestador' AND lida = 0"
        );
        $stmt->execute([$this->tecnicoId]);
        $this->naoLidas = (int)$stmt->fetchColumn();
    }

    public function processar(): void
    {
        $this->verificarSessao();
        $this->chamadoSelecionado = isset($_GET['chamado']) ? (int)$_GET['chamado'] : 0;
        $this->carregarNaoLidas();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $genero = $_SESSION['tecnico_genero'] ?? 'Masculino';
        $this->chamadosDisponiveis = $this->chamadoDAO->listarDisponiveisPorCategoria($this->tecnicoId, $genero);

        $orcamentos = $this->orcamentoDAO->listarPorTecnico($this->tecnicoId);
        $this->meusOrcamentos = array_map(fn($o) => (array)$o, $orcamentos);
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
                fixnow_notificar_cliente($this->pdo, $chamado->clienteId,
                    'Você recebeu um orçamento de R$ ' . number_format($valor, 2, ',', '.') .
                    ' para o chamado #' . $chamadoId . '.', $chamadoId);
            }
            $this->mensagem = 'Orçamento enviado com sucesso.';

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
