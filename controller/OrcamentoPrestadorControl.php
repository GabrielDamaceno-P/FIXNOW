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

        // Chamados disponíveis para orçamento:
        // 1) Aceitos por este prestador (Aguardando Orçamento)
        // 2) Solicitações diretas pendentes
        // 3) Fila aberta — qualquer prestador aprovado pode orçar
        $stmtDisp = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome,
                   CASE WHEN c.status = 'Aguardando Orçamento' THEN 2
                        WHEN c.tecnico_id = ? THEN 1
                        ELSE 0 END AS _ordem
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE (
                (c.tecnico_id = ? AND c.status IN ('Aguardando Orçamento', 'Pendente'))
                OR (
                    c.tecnico_id IS NULL
                    AND c.status = 'Pendente'
                    AND c.categoria IN (
                        SELECT cat.nome FROM servico s
                        INNER JOIN categoria cat ON cat.id = s.categoria_id
                        WHERE s.tecnico_id = ? AND s.ativo = 1
                    )
                )
            )
            AND NOT EXISTS (
                SELECT 1 FROM orcamento o
                WHERE o.chamado_id = c.id AND o.tecnico_id = ? AND o.status = 'Pendente'
            )
            ORDER BY _ordem DESC, c.criado_em ASC
        ");
        $stmtDisp->execute([$this->tecnicoId, $this->tecnicoId, $this->tecnicoId, $this->tecnicoId]);
        $rows = $stmtDisp->fetchAll();
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
                        $valorFmt = number_format($valor, 2, ',', '.');
                        fixnow_notificar_cliente($this->pdo, $chamado->clienteId,
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
