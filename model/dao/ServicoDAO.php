<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/ServicoDTO.php';

class ServicoDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    /** @return ServicoDTO[] */
    public function listarPorTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, c.nome AS categoria_nome
            FROM servico s
            LEFT JOIN categoria c ON c.id = s.categoria_id
            WHERE s.tecnico_id = ?
            ORDER BY s.criado_em DESC
        ");
        $stmt->execute([$tecnicoId]);
        return array_map([ServicoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    public function buscarPorId(int $id, int $tecnicoId): ?ServicoDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, c.nome AS categoria_nome
            FROM servico s LEFT JOIN categoria c ON c.id = s.categoria_id
            WHERE s.id = ? AND s.tecnico_id = ?
        ");
        $stmt->execute([$id, $tecnicoId]);
        $row = $stmt->fetch();
        return $row ? ServicoDTO::fromArray($row) : null;
    }

    public function inserir(ServicoDTO $dto): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO servico (tecnico_id, categoria_id, nome, descricao, preco, ativo)
            VALUES (?, ?, ?, ?, 0, ?)
        ');
        $stmt->execute([
            $dto->tecnicoId, $dto->categoriaId, $dto->nome,
            $dto->descricao ?: null, $dto->ativo,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function atualizar(ServicoDTO $dto): void
    {
        $this->pdo->prepare('
            UPDATE servico SET nome=?, descricao=?, categoria_id=?, ativo=?
            WHERE id=? AND tecnico_id=?
        ')->execute([
            $dto->nome, $dto->descricao ?: null,
            $dto->categoriaId, $dto->ativo, $dto->id, $dto->tecnicoId,
        ]);
    }

    public function excluir(int $id, int $tecnicoId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM servico WHERE id=? AND tecnico_id=?');
        $stmt->execute([$id, $tecnicoId]);
        return $stmt->rowCount() > 0;
    }

    /** Nomes das categorias dos serviços ativos do técnico */
    public function listarCategoriasDoTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT cat.id, COALESCE(cat.nome, s.nome) AS nome
            FROM servico s
            LEFT JOIN categoria cat ON cat.id = s.categoria_id
            WHERE s.tecnico_id = ? AND s.ativo = 1
            ORDER BY nome
        ");
        $stmt->execute([$tecnicoId]);
        return $stmt->fetchAll();
    }

    /** Nomes (string) das categorias ativas do técnico */
    public function listarNomesCategoriasDoTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT cat.nome
            FROM servico s
            INNER JOIN categoria cat ON cat.id = s.categoria_id
            WHERE s.tecnico_id = ? AND s.ativo = 1
            ORDER BY cat.nome
        ");
        $stmt->execute([$tecnicoId]);
        return array_column($stmt->fetchAll(), 'nome');
    }
}
