<?php

require_once __DIR__ . '/Conexao.php';

class DisponibilidadeDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function salvarSemana(int $tecnicoId, array $datas, array $slots, array $horasPermitidas): void
    {
        $ph = implode(',', array_fill(0, count($datas), '?'));
        $this->pdo->prepare("DELETE FROM disponibilidade WHERE tecnico_id=? AND data IN ($ph)")
            ->execute(array_merge([$tecnicoId], $datas));

        if (!$slots) return;

        $ins = $this->pdo->prepare(
            "INSERT IGNORE INTO disponibilidade (tecnico_id, data, hora) VALUES (?,?,?)"
        );
        foreach ($slots as $val) {
            [$data, $hora] = array_pad(explode('|', (string)$val), 2, '');
            if (in_array($data, $datas) && in_array($hora, $horasPermitidas)) {
                $ins->execute([$tecnicoId, $data, $hora . ':00']);
            }
        }
    }

    public function buscarBloqueadosPorDatas(int $tecnicoId, array $datas): array
    {
        $ph   = implode(',', array_fill(0, count($datas), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT data, TIME_FORMAT(hora,'%H:%i') AS hora
             FROM disponibilidade WHERE tecnico_id=? AND data IN ($ph)"
        );
        $stmt->execute(array_merge([$tecnicoId], $datas));

        $result = [];
        foreach ($stmt->fetchAll() as $s) {
            $result[$s['data']][$s['hora']] = true;
        }
        return $result;
    }

    public function buscarBloqueadosFuturos(int $tecnicoId, int $dias = 28): array
    {
        $stmt = $this->pdo->prepare("
            SELECT data, TIME_FORMAT(hora,'%H:%i') AS hora
            FROM disponibilidade
            WHERE tecnico_id = ? AND data >= CURDATE() AND data <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ");
        $stmt->execute([$tecnicoId, $dias]);

        $result = [];
        foreach ($stmt->fetchAll() as $b) {
            $result[$b['data']][$b['hora']] = true;
        }
        return $result;
    }

    public function slotEstaBloqueado(int $tecnicoId, string $data, string $hora): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM disponibilidade WHERE tecnico_id=? AND data=? AND hora=?"
        );
        $stmt->execute([$tecnicoId, $data, $hora . ':00']);
        return (bool)$stmt->fetch();
    }
}
