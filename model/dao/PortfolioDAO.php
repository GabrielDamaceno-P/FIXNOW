<?php

require_once __DIR__ . '/Conexao.php';

class PortfolioDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function listarPorTecnico(int $tecnicoId): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT pf.*, c.nome AS categoria_nome
                FROM portfolio_foto pf
                LEFT JOIN categoria c ON c.id = pf.categoria_id
                WHERE pf.tecnico_id=?
                ORDER BY pf.categoria_id ASC, pf.criado_em DESC
            ');
        } catch (Throwable $e) {
            // Fallback se a coluna categoria_id ainda não existe (migration pendente)
            $stmt = $this->pdo->prepare('
                SELECT *, NULL AS categoria_nome
                FROM portfolio_foto
                WHERE tecnico_id=?
                ORDER BY criado_em DESC
            ');
        }
        $stmt->execute([$tecnicoId]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id, int $tecnicoId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM portfolio_foto WHERE id=? AND tecnico_id=?');
        $stmt->execute([$id, $tecnicoId]);
        return $stmt->fetch() ?: null;
    }

    public function inserir(int $tecnicoId, string $fotoPath, ?string $titulo, ?string $descricao, ?int $categoriaId = null): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO portfolio_foto (tecnico_id, foto_path, titulo, descricao, categoria_id) VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$tecnicoId, $fotoPath, $titulo ?: null, $descricao ?: null, $categoriaId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function atualizar(int $id, int $tecnicoId, ?string $titulo, ?string $descricao, ?int $categoriaId = null, ?string $novoFotoPath = null): void
    {
        if ($novoFotoPath) {
            $this->pdo->prepare('UPDATE portfolio_foto SET titulo=?, descricao=?, categoria_id=?, foto_path=? WHERE id=? AND tecnico_id=?')
                ->execute([$titulo ?: null, $descricao ?: null, $categoriaId, $novoFotoPath, $id, $tecnicoId]);
        } else {
            $this->pdo->prepare('UPDATE portfolio_foto SET titulo=?, descricao=?, categoria_id=? WHERE id=? AND tecnico_id=?')
                ->execute([$titulo ?: null, $descricao ?: null, $categoriaId, $id, $tecnicoId]);
        }
    }

    public function excluir(int $id, int $tecnicoId): ?string
    {
        $row = $this->buscarPorId($id, $tecnicoId);
        if (!$row) return null;
        $this->pdo->prepare('DELETE FROM portfolio_foto WHERE id=? AND tecnico_id=?')->execute([$id, $tecnicoId]);
        return $row['foto_path'];
    }
}
