<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/AvaliacaoDAO.php';
require_once __DIR__ . '/../model/dto/AvaliacaoDTO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';

class HistoricoControl
{
    private ChamadoDAO    $chamadoDAO;
    private AvaliacaoDAO  $avaliacaoDAO;
    private NotificacaoDAO $notifDAO;

    public int    $clienteId        = 0;
    public string $mensagem         = '';
    public string $erro             = '';
    public array  $chamados         = [];
    public int    $naoLidas         = 0;
    public float  $totalGasto       = 0.0;
    public int    $prestadoresUnicos = 0;

    public function __construct()
    {
        $this->chamadoDAO   = new ChamadoDAO();
        $this->avaliacaoDAO = new AvaliacaoDAO();
        $this->notifDAO     = new NotificacaoDAO();
    }

    public function processar(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->clienteId = (int)$_SESSION['cliente_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $this->carregarDados();
    }

    private function processarPost(): void
    {
        if (!isset($_POST['avaliar_chamado_id'], $_POST['nota_avaliacao'])) return;

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
                header('Location: historico.php?avaliacao_ok=1'); exit;
            }
        }
        $this->erro = 'Não foi possível enviar a avaliação.';
    }

    private function carregarDados(): void
    {
        if (isset($_GET['avaliacao_ok'])) {
            $this->mensagem = 'Avaliação enviada com sucesso. Obrigado pelo feedback!';
        }

        $todos = $this->chamadoDAO->listarPorCliente($this->clienteId);
        $this->chamados = array_values(
            array_filter($todos, fn($c) => in_array($c->status, ['Concluído', 'Negado'], true))
        );

        foreach ($this->chamados as $c) {
            if ($c->pagStatus === 'Pago') {
                $this->totalGasto += (float)($c->pagValor ?? 0);
            }
        }

        $tecIds = array_filter(array_unique(array_map(fn($c) => $c->tecnicoId, $this->chamados)));
        $this->prestadoresUnicos = count($tecIds);

        $this->naoLidas = $this->notifDAO->contarNaoLidasCliente($this->clienteId);
    }
}
