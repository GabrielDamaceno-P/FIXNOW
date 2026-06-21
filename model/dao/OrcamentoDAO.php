<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/OrcamentoDTO.php';

class OrcamentoDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function listarPendentesParaCliente(int $clienteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, t.nome AS tecnico_nome, c.descricao AS chamado_desc, c.id AS chamado_id
            FROM orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            INNER JOIN tecnico t ON t.id = o.tecnico_id
            WHERE o.status = 'Pendente' AND c.status IN ('Pendente','Aguardando Orçamento')
            ORDER BY o.criado_em DESC
        ");
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll();
    }

    public function listarPorTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, c.descricao AS chamado_desc, c.status AS chamado_status, cl.nome AS cliente_nome
            FROM orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE o.tecnico_id = ?
            ORDER BY o.criado_em DESC
        ");
        $stmt->execute([$tecnicoId]);
        return array_map([OrcamentoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    public function jaEnviou(int $chamadoId, int $tecnicoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM orcamento WHERE chamado_id=? AND tecnico_id=? LIMIT 1');
        $stmt->execute([$chamadoId, $tecnicoId]);
        return (bool)$stmt->fetch();
    }

    public function inserir(OrcamentoDTO $dto): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO orcamento (chamado_id, tecnico_id, valor, descricao, prazo_dias)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $dto->chamadoId, $dto->tecnicoId, $dto->valor,
            $dto->descricao ?: null, $dto->prazoDias,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function aceitar(int $id, int $clienteId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            SET o.status = 'Aceito'
            WHERE o.id = ? AND o.status = 'Pendente'
        ");
        $stmt->execute([$clienteId, $id]);
        return $stmt->rowCount() > 0;
    }

    public function recusar(int $id, int $clienteId, ?string $motivo): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            SET o.status = 'Recusado', o.motivo_recusa = ?
            WHERE o.id = ? AND o.status = 'Pendente'
        ");
        $stmt->execute([$clienteId, $motivo, $id]);
        return $stmt->rowCount() > 0;
    }

    public function atualizarPorTecnico(int $id, int $tecnicoId, float $valor, ?string $descricao): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE orcamento SET valor=?, descricao=?
            WHERE id=? AND tecnico_id=? AND status='Pendente'
        ");
        $stmt->execute([$valor, $descricao ?: null, $id, $tecnicoId]);
        return $stmt->rowCount() > 0;
    }

    public function cancelarPorTecnico(int $id, int $tecnicoId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM orcamento WHERE id=? AND tecnico_id=? AND status='Pendente'
        ");
        $stmt->execute([$id, $tecnicoId]);
        return $stmt->rowCount() > 0;
    }

    public function buscarPorId(int $id): ?OrcamentoDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, t.nome AS tecnico_nome, cl.nome AS cliente_nome,
                   c.descricao AS chamado_desc, c.status AS chamado_status
            FROM orcamento o
            INNER JOIN tecnico t ON t.id = o.tecnico_id
            INNER JOIN chamado c ON c.id = o.chamado_id
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? OrcamentoDTO::fromArray($row) : null;
    }

    public function aceitarComTransacao(int $clienteId, int $orcamentoId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.id, o.valor, o.tecnico_id, o.chamado_id
            FROM orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            WHERE o.id = ? AND o.status = 'Pendente' LIMIT 1
        ");
        $stmt->execute([$clienteId, $orcamentoId]);
        $orc = $stmt->fetch();
        if (!$orc) return null;

        $this->pdo->beginTransaction();
        $this->pdo->prepare("UPDATE orcamento SET status='Aceito' WHERE id=?")->execute([$orc['id']]);
        $this->pdo->prepare("UPDATE orcamento SET status='Recusado' WHERE chamado_id=? AND id!=?")->execute([$orc['chamado_id'], $orc['id']]);
        $this->pdo->prepare("UPDATE chamado SET tecnico_id=?, status='Em Andamento', preco_sugerido=? WHERE id=?")->execute([$orc['tecnico_id'], $orc['valor'], $orc['chamado_id']]);
        $stmtPag = $this->pdo->prepare("SELECT id FROM pagamento WHERE chamado_id=?");
        $stmtPag->execute([$orc['chamado_id']]);
        if ($stmtPag->fetch()) {
            $this->pdo->prepare("UPDATE pagamento SET valor=? WHERE chamado_id=?")->execute([$orc['valor'], $orc['chamado_id']]);
        } else {
            $this->pdo->prepare("INSERT INTO pagamento (chamado_id, metodo, valor, status) VALUES (?, 'PIX', ?, 'Pendente')")->execute([$orc['chamado_id'], $orc['valor']]);
        }
        $this->pdo->commit();

        return [
            'tecnico_id' => (int)$orc['tecnico_id'],
            'chamado_id' => (int)$orc['chamado_id'],
            'valor'      => (float)$orc['valor'],
        ];
    }

    public function recusarRetornandoIds(int $clienteId, int $orcamentoId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.id, o.tecnico_id, o.chamado_id FROM orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            WHERE o.id = ? AND o.status = 'Pendente' LIMIT 1
        ");
        $stmt->execute([$clienteId, $orcamentoId]);
        $orc = $stmt->fetch();
        if (!$orc) return null;

        $chamadoId = (int)$orc['chamado_id'];

        $this->pdo->prepare("DELETE FROM notificacao WHERE chamado_id = ?")->execute([$chamadoId]);
        $this->pdo->prepare("DELETE FROM mensagem_chamado WHERE chamado_id = ?")->execute([$chamadoId]);
        $this->pdo->prepare("DELETE FROM chamado_foto WHERE chamado_id = ?")->execute([$chamadoId]);
        $this->pdo->prepare("DELETE FROM orcamento WHERE chamado_id = ?")->execute([$chamadoId]);
        $this->pdo->prepare("DELETE FROM chamado WHERE id = ?")->execute([$chamadoId]);

        return [
            'tecnico_id' => (int)$orc['tecnico_id'],
            'chamado_id' => $chamadoId,
        ];
    }

    public function listarDisponiveisParaTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
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
        $stmt->execute([$tecnicoId, $tecnicoId, $tecnicoId, $tecnicoId]);
        return $stmt->fetchAll();
    }

    public function buscarChamadoId(int $orcamentoId, int $tecnicoId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT chamado_id FROM orcamento WHERE id=? AND tecnico_id=?");
        $stmt->execute([$orcamentoId, $tecnicoId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['chamado_id'] : null;
    }
}
