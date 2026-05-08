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

    /** @return OrcamentoDTO[] — orçamentos pendentes para o cliente */
    public function listarPendentesParaCliente(int $clienteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, t.nome AS tecnico_nome, c.descricao AS chamado_desc, c.id AS chamado_id
            FROM orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            INNER JOIN tecnico t ON t.id = o.tecnico_id
            WHERE o.status = 'Pendente' AND c.status = 'Pendente'
            ORDER BY o.criado_em DESC
        ");
        $stmt->execute([$clienteId]);
        return array_map([OrcamentoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    /** @return OrcamentoDTO[] — orçamentos enviados pelo técnico */
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
}
