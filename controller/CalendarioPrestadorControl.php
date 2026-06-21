<?php

require_once __DIR__ . '/../model/dao/DisponibilidadeDAO.php';
require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class CalendarioPrestadorControl
{
    private DisponibilidadeDAO $dispoDAO;
    private ChamadoDAO         $chamadoDAO;
    private NotificacaoDAO     $notifDAO;

    public static array $horas = [
        '08:00','09:00','10:00','11:00','12:00',
        '13:00','14:00','15:00','16:00','17:00',
    ];

    public int    $tecnicoId      = 0;
    public string $mensagem       = '';
    public array  $diasSemana     = [];
    public array  $bloqueados     = [];
    public array  $agendados      = [];
    public bool   $podePrev       = false;
    public string $prevSegundaStr = '';
    public string $nextSegundaStr = '';
    public string $rangeDisplay   = '';
    public string $inicioStr      = '';
    public int    $naoLidas       = 0;

    public function __construct()
    {
        $this->dispoDAO   = new DisponibilidadeDAO();
        $this->chamadoDAO = new ChamadoDAO();
        $this->notifDAO   = new NotificacaoDAO();
    }

    public function processar(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];
        fixnow_checar_ativo_prestador($this->tecnicoId, '../login.php');

        $minSegunda = new DateTime('today');
        $dow        = (int)$minSegunda->format('N');
        if ($dow !== 1) $minSegunda->modify('last monday');

        $inicioParam = $_GET['inicio'] ?? $minSegunda->format('Y-m-d');
        try { $inicioSemana = new DateTime($inicioParam); }
        catch (Exception $e) { $inicioSemana = clone $minSegunda; }
        if ((int)$inicioSemana->format('N') !== 1) $inicioSemana->modify('last monday');
        if ($inicioSemana < $minSegunda) $inicioSemana = clone $minSegunda;

        $this->diasSemana = [];
        for ($i = 0; $i < 7; $i++) {
            $d = clone $inicioSemana;
            $d->modify("+{$i} day");
            $this->diasSemana[] = $d;
        }

        $prevSegunda          = (clone $inicioSemana)->modify('-7 days');
        $nextSegunda          = (clone $inicioSemana)->modify('+7 days');
        $this->podePrev       = $prevSegunda >= $minSegunda;
        $this->prevSegundaStr = $prevSegunda->format('Y-m-d');
        $this->nextSegundaStr = $nextSegunda->format('Y-m-d');
        $this->inicioStr      = $inicioSemana->format('Y-m-d');
        $this->rangeDisplay   = $this->diasSemana[0]->format('d/m')
                              . ' – '
                              . $this->diasSemana[6]->format('d/m/Y');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarAgenda();
        }

        $this->carregarBloqueados();
        $this->carregarAgendados();
        $this->naoLidas = $this->notifDAO->contarNaoLidasTecnico($this->tecnicoId);
    }

    private function salvarAgenda(): void
    {
        $datas = array_map(fn($d) => $d->format('Y-m-d'), $this->diasSemana);
        $slots = $_POST['slot'] ?? [];
        $this->dispoDAO->salvarSemana($this->tecnicoId, $datas, $slots, self::$horas);
        $this->mensagem = 'Agenda salva com sucesso.';
    }

    private function carregarBloqueados(): void
    {
        $datas = array_map(fn($d) => $d->format('Y-m-d'), $this->diasSemana);
        $this->bloqueados = $this->dispoDAO->buscarBloqueadosPorDatas($this->tecnicoId, $datas);
    }

    private function carregarAgendados(): void
    {
        $datas = array_map(fn($d) => $d->format('Y-m-d'), $this->diasSemana);
        $rows  = $this->chamadoDAO->listarAgendadosPorDatas($this->tecnicoId, $datas);
        foreach ($rows as $row) {
            if (!$row['data'] || !$row['hora']) continue;
            $this->agendados[$row['data']][$row['hora']] = [
                'id'       => $row['id'],
                'categoria'=> $row['categoria'],
                'cliente'  => $row['cliente_nome'],
            ];
        }
    }
}
