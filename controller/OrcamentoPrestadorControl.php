<?php

require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dto/OrcamentoDTO.php';
require_once __DIR__ . '/../includes/helpers.php';

class OrcamentoPrestadorControl
{
    private OrcamentoDAO   $orcamentoDAO;
    private ChamadoDAO     $chamadoDAO;
    private NotificacaoDAO $notifDAO;
    private PDO            $pdo;

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
        $this->pdo          = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];
    }

    public function processar(): void
    {
        $this->verificarSessao();
        $this->chamadoSelecionado = isset($_GET['chamado']) ? (int)$_GET['chamado'] : 0;
        $this->naoLidas = $this->notifDAO->contarNaoLidasTecnico($this->tecnicoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $genero = $_SESSION['tecnico_genero'] ?? 'Masculino';

        // Chamados abertos na especialidade (fila + diretos) — status Pendente
        $disponiveis = $this->chamadoDAO->listarDisponiveisPorCategoria($this->tecnicoId, $genero);

        // Chamados aceitos aguardando orçamento (já atribuídos a este técnico)
        $stmtAg = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.tecnico_id = ? AND c.status = 'Aguardando Orçamento'
              AND NOT EXISTS (
                SELECT 1 FROM orcamento o WHERE o.chamado_id = c.id AND o.tecnico_id = ? AND o.status = 'Pendente'
              )
            ORDER BY c.criado_em DESC
        ");
        $stmtAg->execute([$this->tecnicoId, $this->tecnicoId]);
        $aguardando = $stmtAg->fetchAll();

        // Aguardando orçamento aparece primeiro (são prioridade)
        $this->chamadosDisponiveis = array_merge($aguardando, $disponiveis);

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
                fixnow_notificar_cliente($this->pdo, $chamado->clienteId,
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
                // Busca chamado_id para notificar o cliente
                $stmtO = $this->pdo->prepare("SELECT chamado_id FROM orcamento WHERE id=? AND tecnico_id=?");
                $stmtO->execute([$oid, $this->tecnicoId]);
                $row = $stmtO->fetch();
                if ($row) {
                    $chamado = $this->chamadoDAO->buscarPorId((int)$row['chamado_id']);
                    if ($chamado) {
                        fixnow_notificar_cliente($this->pdo, $chamado->clienteId,
                            'O orçamento do chamado #' . $chamado->id . ' foi atualizado para R$ ' .
                            number_format($valor, 2, ',', '.') . '. Acesse o painel para revisar.', $chamado->id);
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
