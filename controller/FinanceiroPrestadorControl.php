<?php

require_once __DIR__ . '/../model/dao/PagamentoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class FinanceiroPrestadorControl
{
    private PagamentoDAO   $pagamentoDAO;
    private NotificacaoDAO $notifDAO;

    public const TAXA = 0.20;

    public int    $tecnicoId      = 0;
    public int    $naoLidas       = 0;
    public float  $brutoRecebido  = 0.0;
    public float  $brutoPendente  = 0.0;
    public float  $totalEstornado = 0.0;
    public float  $liquidoRecebido= 0.0;
    public float  $liquidoPendente= 0.0;
    public float  $taxaTotal      = 0.0;
    public int    $totalServicos  = 0;
    public array  $mensal         = [];
    public array  $detalhes       = [];
    public string $filtroMes      = '';
    public int    $filtroAno      = 0;

    public function __construct()
    {
        $this->pagamentoDAO = new PagamentoDAO();
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
        $this->filtroMes = $_GET['mes'] ?? '';
        $this->filtroAno = (int)($_GET['ano'] ?? date('Y'));

        $this->carregarResumo();
        $this->mensal   = $this->pagamentoDAO->mensalPorTecnico($this->tecnicoId);
        $this->detalhes = $this->pagamentoDAO->detalhesPorTecnico($this->tecnicoId, $this->filtroMes, $this->filtroAno);
        $this->naoLidas = $this->notifDAO->contarNaoLidasTecnico($this->tecnicoId);
    }

    private function carregarResumo(): void
    {
        $resumo = $this->pagamentoDAO->resumoCompleto($this->tecnicoId);

        $this->totalServicos   = (int)($resumo['total_servicos']  ?? 0);
        $this->brutoRecebido   = (float)($resumo['bruto_recebido']  ?? 0);
        $this->brutoPendente   = (float)($resumo['bruto_pendente']  ?? 0);
        $this->totalEstornado  = (float)($resumo['total_estornado'] ?? 0);
        $this->liquidoRecebido = $this->brutoRecebido  * (1 - self::TAXA);
        $this->liquidoPendente = $this->brutoPendente  * (1 - self::TAXA);
        $this->taxaTotal       = $this->brutoRecebido  * self::TAXA;
    }
}
