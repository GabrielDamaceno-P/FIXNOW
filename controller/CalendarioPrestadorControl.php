<?php

require_once __DIR__ . '/../model/dao/Conexao.php';

class CalendarioPrestadorControl
{
    private PDO $pdo;

    public static array $horas = [
        '08:00','09:00','10:00','11:00','12:00',
        '13:00','14:00','15:00','16:00','17:00',
    ];

    public int    $tecnicoId      = 0;
    public string $mensagem       = '';
    public array  $diasSemana     = [];
    public array  $bloqueados     = [];
    public array  $agendados      = [];  // [data][hora] => ['id', 'categoria', 'cliente']
    public bool   $podePrev       = false;
    public string $prevSegundaStr = '';
    public string $nextSegundaStr = '';
    public string $rangeDisplay   = '';
    public string $inicioStr      = '';
    public int    $naoLidas       = 0;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function processar(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];

        $hoje       = new DateTime('today');
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
        $this->contarNaoLidas();
    }

    private function salvarAgenda(): void
    {
        $datasPost = array_map(fn($d) => $d->format('Y-m-d'), $this->diasSemana);
        $ph        = implode(',', array_fill(0, count($datasPost), '?'));

        $this->pdo->prepare("DELETE FROM disponibilidade WHERE tecnico_id=? AND data IN ($ph)")
            ->execute(array_merge([$this->tecnicoId], $datasPost));

        $slots = $_POST['slot'] ?? [];
        if ($slots) {
            $ins = $this->pdo->prepare(
                "INSERT IGNORE INTO disponibilidade (tecnico_id, data, hora) VALUES (?,?,?)"
            );
            foreach ($slots as $val) {
                [$data, $hora] = array_pad(explode('|', (string)$val), 2, '');
                if (in_array($data, $datasPost) && in_array($hora, self::$horas)) {
                    $ins->execute([$this->tecnicoId, $data, $hora . ':00']);
                }
            }
        }
        $this->mensagem = 'Agenda salva com sucesso.';
    }

    private function carregarBloqueados(): void
    {
        $datasStr = array_map(fn($d) => $d->format('Y-m-d'), $this->diasSemana);
        $ph       = implode(',', array_fill(0, count($datasStr), '?'));
        $stmt     = $this->pdo->prepare(
            "SELECT data, TIME_FORMAT(hora,'%H:%i') AS hora
             FROM disponibilidade WHERE tecnico_id=? AND data IN ($ph)"
        );
        $stmt->execute(array_merge([$this->tecnicoId], $datasStr));
        foreach ($stmt->fetchAll() as $s) {
            $this->bloqueados[$s['data']][$s['hora']] = true;
        }
    }

    private function carregarAgendados(): void
    {
        $datasStr = array_map(fn($d) => $d->format('Y-m-d'), $this->diasSemana);
        $ph       = implode(',', array_fill(0, count($datasStr), '?'));
        $stmt     = $this->pdo->prepare("
            SELECT DATE(c.data_agendamento) AS data,
                   TIME_FORMAT(c.data_agendamento,'%H:%i') AS hora,
                   c.id, c.categoria,
                   cl.nome AS cliente_nome
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.tecnico_id = ?
              AND c.status IN ('Pendente','Em Andamento')
              AND DATE(c.data_agendamento) IN ($ph)
        ");
        $stmt->execute(array_merge([$this->tecnicoId], $datasStr));
        foreach ($stmt->fetchAll() as $row) {
            if (!$row['data'] || !$row['hora']) continue;
            $this->agendados[$row['data']][$row['hora']] = [
                'id'       => $row['id'],
                'categoria'=> $row['categoria'],
                'cliente'  => $row['cliente_nome'],
            ];
        }
    }

    private function contarNaoLidas(): void
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM notificacao
                WHERE tecnico_id=? AND tipo_destinatario='tecnico' AND lida=0
            ");
            $stmt->execute([$this->tecnicoId]);
            $this->naoLidas = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->naoLidas = 0;
        }
    }
}
