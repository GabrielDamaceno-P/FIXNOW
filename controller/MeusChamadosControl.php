<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/AvaliacaoDAO.php';
require_once __DIR__ . '/../model/dto/AvaliacaoDTO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class MeusChamadosControl
{
    private ChamadoDAO     $chamadoDAO;
    private AvaliacaoDAO   $avaliacaoDAO;
    private NotificacaoDAO $notifDAO;
    private PDO            $pdo;

    public int    $clienteId = 0;
    public string $mensagem  = '';
    public string $erro      = '';
    public array  $chamados  = [];
    public array  $stats     = ['total_chamados' => 0, 'concluidos' => 0];
    public int    $naoLidas  = 0;
    public string $filtroStatus = '';

    public function __construct()
    {
        $this->chamadoDAO   = new ChamadoDAO();
        $this->avaliacaoDAO = new AvaliacaoDAO();
        $this->notifDAO     = new NotificacaoDAO();
        $this->pdo          = Conexao::getConexao();
    }

    public function processar(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->clienteId = (int)$_SESSION['cliente_id'];

        $this->resolverMensagemGet();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $this->filtroStatus = $_GET['status'] ?? '';
        $this->carregarDados();
    }

    private function resolverMensagemGet(): void
    {
        $msgs = [
            'cancelar_ok'        => 'Agendamento cancelado com sucesso.',
            'alterado_ok'        => 'Agendamento atualizado com sucesso.',
            'orcamento_aceito'   => 'Orçamento aceito! O prestador será notificado.',
            'orcamento_recusado' => 'Orçamento recusado.',
            'reagendado'         => 'Proposta de reagendamento enviada. Aguardando confirmação do prestador.',
            'avaliacao_ok'       => 'Avaliação enviada com sucesso. Obrigado pelo feedback!',
        ];
        foreach ($msgs as $key => $msg) {
            if (isset($_GET[$key])) { $this->mensagem = $msg; break; }
        }
    }

    private function processarPost(): void
    {
        $redir = 'meusChamados.php' . ($this->filtroStatus ? '?status=' . urlencode($this->filtroStatus) : '');

        if (isset($_POST['cancelar_chamado_id'])) {
            $cid = (int)$_POST['cancelar_chamado_id'];
            if ($cid > 0) {
                $up = $this->pdo->prepare("UPDATE chamado SET status='Negado' WHERE id=? AND cliente_id=? AND status='Pendente'");
                $up->execute([$cid, $this->clienteId]);
                header('Location: meusChamados.php?cancelar_ok=1'); exit;
            }

        } elseif (isset($_POST['alterar_chamado_id'])) {
            $cid      = (int)$_POST['alterar_chamado_id'];
            $descricao = trim($_POST['nova_descricao'] ?? '');
            $endereco  = trim($_POST['novo_endereco']  ?? '');
            if ($cid > 0 && $descricao && $endereco) {
                $up = $this->pdo->prepare("UPDATE chamado SET descricao=?, endereco_servico=? WHERE id=? AND cliente_id=? AND status='Pendente'");
                $up->execute([$descricao, $endereco, $cid, $this->clienteId]);
                header('Location: meusChamados.php?alterado_ok=1'); exit;
            }
            $this->erro = 'Não foi possível alterar o chamado.';

        } elseif (isset($_POST['reagendar_chamado_id'])) {
            $cid      = (int)$_POST['reagendar_chamado_id'];
            $novaData = trim($_POST['nova_data_agendamento'] ?? '');
            if ($cid > 0 && $novaData && strtotime($novaData) > time()) {
                $rowTec = $this->pdo->prepare("SELECT tecnico_id FROM chamado WHERE id=? AND cliente_id=? AND status IN ('Pendente','Aguardando Orçamento','Em Andamento')");
                $rowTec->execute([$cid, $this->clienteId]);
                $tec = $rowTec->fetch();
                if ($tec) {
                    $up = $this->pdo->prepare("UPDATE chamado SET data_agendamento=?, data_agendamento_proposta=NULL, reagendamento_pendente=0 WHERE id=? AND cliente_id=?");
                    $up->execute([$novaData, $cid, $this->clienteId]);
                    if (!empty($tec['tecnico_id'])) {
                        fixnow_notificar_prestador($this->pdo, (int)$tec['tecnico_id'],
                            'O cliente reagendou o chamado #' . $cid . ' para ' . date('d/m/Y H:i', strtotime($novaData)) . '.', $cid);
                    }
                    header('Location: meusChamados.php?reagendado=1'); exit;
                }
            }
            $this->erro = 'Não foi possível reagendar.';

        } elseif (isset($_POST['confirmar_pagamento_id'])) {
            $pid    = (int)$_POST['confirmar_pagamento_id'];
            $metodo = $_POST['metodo_pagamento'] ?? 'PIX';
            if (!in_array($metodo, ['PIX', 'Cartão', 'Dinheiro'], true)) $metodo = 'PIX';
            if ($pid > 0) {
                $up = $this->pdo->prepare("
                    UPDATE pagamento p
                    INNER JOIN chamado c ON c.id = p.chamado_id AND c.cliente_id = ?
                    SET p.status = 'Pago', p.pago_em = NOW(), p.metodo = ?
                    WHERE p.id = ? AND p.status = 'Pendente'
                ");
                $up->execute([$this->clienteId, $metodo, $pid]);
                $this->mensagem = $up->rowCount() > 0
                    ? 'Pagamento registrado com sucesso (simulação).'
                    : 'Não foi possível confirmar este pagamento.';
            }

        } elseif (isset($_POST['avaliar_chamado_id'], $_POST['nota_avaliacao'])) {
            $cid        = (int)$_POST['avaliar_chamado_id'];
            $nota       = (int)$_POST['nota_avaliacao'];
            $comentario = trim($_POST['comentario_avaliacao'] ?? '');
            if ($nota >= 1 && $nota <= 5) {
                $chamado = $this->chamadoDAO->buscarPorId($cid);
                if ($chamado && $chamado->clienteId === $this->clienteId
                    && $chamado->status === 'Concluído'
                    && !empty($chamado->tecnicoId)
                    && !$this->avaliacaoDAO->jaAvaliou($cid)) {
                    $av = new AvaliacaoDTO();
                    $av->chamadoId  = $cid;
                    $av->clienteId  = $this->clienteId;
                    $av->tecnicoId  = $chamado->tecnicoId;
                    $av->nota       = $nota;
                    $av->comentario = $comentario ?: null;
                    $this->avaliacaoDAO->inserir($av);
                    header('Location: meusChamados.php?avaliacao_ok=1'); exit;
                }
            }
            $this->erro = 'Não foi possível enviar a avaliação.';
        }
    }

    private function carregarDados(): void
    {
        $todos = $this->chamadoDAO->listarPorCliente($this->clienteId);

        $statusValidos = ['Pendente', 'Aguardando Orçamento', 'Em Andamento', 'Concluído', 'Negado'];
        if ($this->filtroStatus && in_array($this->filtroStatus, $statusValidos, true)) {
            $this->chamados = array_filter($todos, fn($c) => $c->status === $this->filtroStatus);
            $this->chamados = array_values($this->chamados);
        } else {
            $this->chamados = $todos;
        }

        $this->stats    = $this->chamadoDAO->estatisticasCliente($this->clienteId);
        $this->naoLidas = $this->notifDAO->contarNaoLidasCliente($this->clienteId);
    }
}
