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

    /** Totais brutos/pendentes/estornados por técnico (para tela financeiro) */
    public function resumoCompleto(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
              COUNT(DISTINCT c.id) AS total_servicos,
              COALESCE(SUM(CASE WHEN p.status = 'Pago'      THEN p.valor ELSE 0 END), 0) AS bruto_recebido,
              COALESCE(SUM(CASE WHEN p.status = 'Pendente'  THEN p.valor ELSE 0 END), 0) AS bruto_pendente,
              COALESCE(SUM(CASE WHEN p.status = 'Estornado' THEN p.valor ELSE 0 END), 0) AS total_estornado
            FROM chamado c
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            WHERE c.tecnico_id = ? AND c.status IN ('Concluído','Negado')
        ");
        $stmt->execute([$tecnicoId]);
        return $stmt->fetch() ?: ['total_servicos' => 0, 'bruto_recebido' => 0, 'bruto_pendente' => 0, 'total_estornado' => 0];
    }

    /** Agrupamento mensal de pagamentos do técnico (últimos 12 meses) */
    public function mensalPorTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
              DATE_FORMAT(p.pago_em, '%Y-%m')  AS mes_ano,
              DATE_FORMAT(p.pago_em, '%m/%Y')  AS mes_label,
              COUNT(*) AS qtd,
              SUM(p.valor) AS bruto
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            WHERE c.tecnico_id = ? AND p.status = 'Pago'
            GROUP BY mes_ano, mes_label
            ORDER BY mes_ano DESC
            LIMIT 12
        ");
        $stmt->execute([$tecnicoId]);
        return $stmt->fetchAll();
    }

    /** Detalhes dos pagamentos do técnico filtrados por mês/ano */
    public function detalhesPorTecnico(int $tecnicoId, ?string $filtroMes, int $filtroAno): array
    {
        $where  = "AND YEAR(COALESCE(p.pago_em, c.atualizado_em)) = ?";
        $params = [$tecnicoId, $filtroAno];

        if ($filtroMes !== '') {
            $where  = "AND MONTH(COALESCE(p.pago_em, c.atualizado_em)) = ? AND YEAR(COALESCE(p.pago_em, c.atualizado_em)) = ?";
            $params = [$tecnicoId, (int)$filtroMes, $filtroAno];
        }

        $stmt = $this->pdo->prepare("
            SELECT c.id AS chamado_id, c.descricao, c.categoria, c.preco_sugerido,
                   cl.nome AS cliente_nome,
                   p.id AS pag_id, p.valor AS pag_valor, p.status AS pag_status,
                   p.metodo, p.pago_em,
                   c.atualizado_em AS concluido_em
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            WHERE c.tecnico_id = ? AND c.status = 'Concluído'
            {$where}
            ORDER BY c.atualizado_em DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
