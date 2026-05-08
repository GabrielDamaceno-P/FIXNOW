<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/AvaliacaoDTO.php';

class AvaliacaoDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function jaAvaliou(int $chamadoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM avaliacao WHERE chamado_id=? LIMIT 1');
        $stmt->execute([$chamadoId]);
        return (bool)$stmt->fetch();
    }

    public function inserir(AvaliacaoDTO $dto): void
    {
        $this->pdo->prepare('
            INSERT INTO avaliacao (chamado_id, cliente_id, tecnico_id, nota, comentario)
            VALUES (?, ?, ?, ?, ?)
        ')->execute([
            $dto->chamadoId, $dto->clienteId, $dto->tecnicoId,
            $dto->nota, $dto->comentario ?: null,
        ]);
        $this->recalcularMedia($dto->tecnicoId);
    }

    private function recalcularMedia(int $tecnicoId): void
    {
        $this->pdo->prepare("
            UPDATE tecnico SET avaliacao_media = (
                SELECT ROUND(AVG(nota), 2) FROM avaliacao WHERE tecnico_id = ?
            ) WHERE id = ?
        ")->execute([$tecnicoId, $tecnicoId]);
    }

    /** @return AvaliacaoDTO[] */
    public function listarPorTecnico(int $tecnicoId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, cl.nome AS cliente_nome
            FROM avaliacao a
            INNER JOIN cliente cl ON cl.id = a.cliente_id
            WHERE a.tecnico_id = ?
            ORDER BY a.criado_em DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$tecnicoId]);
        return array_map([AvaliacaoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    public function depoimentoAleatorio(): ?AvaliacaoDTO
    {
        $stmt = $this->pdo->query("
            SELECT a.*, cl.nome AS cliente_nome
            FROM avaliacao a
            INNER JOIN cliente cl ON cl.id = a.cliente_id
            WHERE a.comentario IS NOT NULL AND TRIM(a.comentario) != '' AND a.nota >= 4
            ORDER BY RAND() LIMIT 1
        ");
        $row = $stmt->fetch();
        return $row ? AvaliacaoDTO::fromArray($row) : null;
    }
}
