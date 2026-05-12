<?php

require_once __DIR__ . '/Conexao.php';

class CategoriaDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function listarAtivas(): array
    {
        return $this->pdo->query("SELECT * FROM categoria WHERE ativo=1 ORDER BY nome ASC")->fetchAll();
    }

    public function listarTodas(): array
    {
        return $this->pdo->query("SELECT * FROM categoria ORDER BY nome ASC")->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categoria WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function nomeExiste(string $nome, int $excluirId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM categoria WHERE nome=? AND id<>?');
        $stmt->execute([$nome, $excluirId]);
        return (bool)$stmt->fetch();
    }

    public function inserir(string $nome, ?string $descricao, ?int $criadoPor = null): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO categoria (nome, descricao, admin_id) VALUES (?, ?, ?)');
        $stmt->execute([$nome, $descricao ?: null, $criadoPor]);
        return (int)$this->pdo->lastInsertId();
    }

    public function atualizar(int $id, string $nome, ?string $descricao, int $ativo): void
    {
        $this->pdo->prepare('UPDATE categoria SET nome=?, descricao=?, ativo=? WHERE id=?')
            ->execute([$nome, $descricao ?: null, $ativo, $id]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM categoria WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
