<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class CalendarioClienteControl
{
    private ChamadoDAO     $chamadoDAO;
    private NotificacaoDAO $notifDAO;

    public int    $clienteId      = 0;
    public string $mensagem       = '';
    public string $erro           = '';
    public int    $mes            = 0;
    public int    $ano            = 0;
    public array  $chamadosPorDia = [];
    public array  $semAgendamento = [];
    public array  $navMeses       = [];
    public int    $naoLidas       = 0;

    public function __construct()
    {
        $this->chamadoDAO = new ChamadoDAO();
        $this->notifDAO   = new NotificacaoDAO();
    }

    public function processar(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->clienteId = (int)$_SESSION['cliente_id'];
        fixnow_checar_ativo_cliente($this->clienteId, '../login.php');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        if (isset($_GET['reagendado'])) {
            $this->mensagem = 'Serviço reagendado com sucesso.';
        }

        $this->mes = (int)($_GET['mes'] ?? date('n'));
        $this->ano = (int)($_GET['ano'] ?? date('Y'));
        if ($this->mes < 1)  { $this->mes = 12; $this->ano--; }
        if ($this->mes > 12) { $this->mes = 1;  $this->ano++; }

        $this->calcularNavMeses();
        $this->carregarChamados();
        $this->naoLidas = $this->notifDAO->contarNaoLidasCliente($this->clienteId);
    }

    private function processarPost(): void
    {
        $cid      = (int)trim($_POST['reagendar_chamado_id'] ?? 0);
        $novaData = trim($_POST['nova_data'] ?? '');

        if ($cid <= 0 || $novaData === '') {
            $this->erro = 'Selecione uma data e hora válidas.'; return;
        }
        if (strtotime($novaData) <= time()) {
            $this->erro = 'A data deve ser futura.'; return;
        }

        if ($this->chamadoDAO->reagendarDireto($this->clienteId, $cid, $novaData)) {
            $m = (int)date('n', strtotime($novaData));
            $a = (int)date('Y', strtotime($novaData));
            header("Location: calendario.php?mes={$m}&ano={$a}&reagendado=1"); exit;
        }
        $this->erro = 'Não foi possível reagendar. O chamado pode já estar finalizado.';
    }

    private function calcularNavMeses(): void
    {
        $ma = $this->mes - 1; $aa = $this->ano;
        if ($ma < 1)  { $ma = 12; $aa--; }
        $mp = $this->mes + 1; $ap = $this->ano;
        if ($mp > 12) { $mp = 1;  $ap++; }
        $this->navMeses = [
            'anterior' => ['mes' => $ma, 'ano' => $aa],
            'proximo'  => ['mes' => $mp, 'ano' => $ap],
        ];
    }

    private function carregarChamados(): void
    {
        $todos = $this->chamadoDAO->listarParaCalendario($this->clienteId);

        foreach ($todos as $ch) {
            if ($ch['data_agendamento']) {
                $ts  = strtotime($ch['data_agendamento']);
                $dia = (int)date('j', $ts);
                if ((int)date('n', $ts) === $this->mes && (int)date('Y', $ts) === $this->ano) {
                    $this->chamadosPorDia[$dia][] = $ch;
                }
            }
        }

        $this->semAgendamento = array_values(array_filter($todos, fn($c) =>
            !$c['data_agendamento'] && in_array($c['status'], ['Pendente', 'Em Andamento'])
        ));
    }
}
