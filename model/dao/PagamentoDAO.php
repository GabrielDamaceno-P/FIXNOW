<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/PagamentoDTO.php';

class PagamentoDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function buscarPorChamado(int $chamadoId): ?PagamentoDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pagamento WHERE chamado_id=? LIMIT 1');
        $stmt->execute([$chamadoId]);
        $row = $stmt->fetch();
        return $row ? PagamentoDTO::fromArray($row) : null;
    }

    public function inserir(PagamentoDTO $dto): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO pagamento (chamado_id, metodo, valor, status) VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$dto->chamadoId, $dto->metodo, $dto->valor, $dto->status]);
        return (int)$this->pdo->lastInsertId();
    }

    public function marcarPago(int $id, string $metodo): void
    {
        $this->pdo->prepare("
            UPDATE pagamento SET status='Pago', metodo=?, pago_em=NOW() WHERE id=?
        ")->execute([$metodo, $id]);
    }

    /** Receita por técnico — retorna array com totais */
    public function resumoPorTecnico(int $tecnicoId, ?int $mes = null, ?int $ano = null): array
    {
        $where = "c.tecnico_id = ? AND p.status = 'Pago'";
        $params = [$tecnicoId];
        if ($mes && $ano) {
            $where .= ' AND MONTH(p.pago_em)=? AND YEAR(p.pago_em)=?';
            $params[] = $mes;
            $params[] = $ano;
        }
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.descricao AS chamado_desc, cl.nome AS cliente_nome,
                   DATE_FORMAT(p.pago_em, '%d/%m/%Y') AS pago_em_fmt
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE {$where}
            ORDER BY p.pago_em DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
