<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/AdminDTO.php';

class AdminDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function buscarPorEmail(string $email): ?AdminDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? AdminDTO::fromArray($row) : null;
    }

    public function buscarPorId(int $id): ?AdminDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? AdminDTO::fromArray($row) : null;
    }

    public function listar(): array
    {
        return $this->pdo->query('SELECT * FROM admin ORDER BY nome ASC')->fetchAll();
    }

    public function inserir(string $nome, string $email, string $senhaHash, string $genero): bool
    {
        try {
            $this->pdo->prepare('
                INSERT INTO admin (nome, email, senha, genero)
                VALUES (?, ?, ?, ?)
            ')->execute([$nome, $email, $senhaHash, $genero]);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function atualizar(int $id, string $nome, string $email, string $genero): bool
    {
        try {
            $this->pdo->prepare('
                UPDATE admin SET nome=?, email=?, genero=? WHERE id=?
            ')->execute([$nome, $email, $genero, $id]);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $this->pdo->prepare('UPDATE admin SET senha = ? WHERE id = ?')
            ->execute([$senhaHash, $id]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
