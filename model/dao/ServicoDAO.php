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

    public function listarPrestadoresComFiltro(?string $categoria, bool $soMulher): array
    {
        $where  = ['s.ativo = 1', 't.ativo = 1', "t.status_cadastro = 'Aprovado'"];
        $params = [];

        if ($categoria !== null && $categoria !== '') {
            $where[]  = 'c.nome = ?';
            $params[] = $categoria;
        }
        if ($soMulher) {
            $where[] = "t.genero = 'Feminino'";
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $this->pdo->prepare("
            SELECT s.id AS servico_id, s.nome AS servico_nome, s.descricao, s.preco,
                   c.nome AS categoria_nome,
                   t.id AS tecnico_id, t.nome AS tecnico_nome, t.foto_perfil, t.destaque,
                   COALESCE(t.avaliacao_media, 0) AS media_nota
            FROM servico s
            JOIN tecnico t ON t.id = s.tecnico_id
            LEFT JOIN categoria c ON c.id = s.categoria_id
            WHERE $whereSQL
            ORDER BY t.destaque DESC, t.avaliacao_media DESC, s.nome ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $byProvider = [];
        foreach ($rows as $row) {
            $tid = $row['tecnico_id'];
            if (!isset($byProvider[$tid])) {
                $byProvider[$tid] = [
                    'tecnico_id'   => $tid,
                    'tecnico_nome' => $row['tecnico_nome'],
                    'foto_perfil'  => $row['foto_perfil'],
                    'destaque'     => $row['destaque'],
                    'media_nota'   => $row['media_nota'],
                    'servicos'     => [],
                    'preco_min'    => $row['preco'],
                    'preco_max'    => $row['preco'],
                ];
            }
            $byProvider[$tid]['servicos'][] = [
                'nome'           => $row['servico_nome'],
                'descricao'      => $row['descricao'],
                'preco'          => $row['preco'],
                'categoria_nome' => $row['categoria_nome'],
            ];
            $byProvider[$tid]['preco_min'] = min($byProvider[$tid]['preco_min'], (float)$row['preco']);
            $byProvider[$tid]['preco_max'] = max($byProvider[$tid]['preco_max'], (float)$row['preco']);
        }
        return array_values($byProvider);
    }

    public function listarPrestadoresCatalogo(?string $categoria): array
    {
        $sql    = "
            SELECT t.id, t.nome, t.especialidade, t.foto_perfil, t.avaliacao_media, t.destaque,
                   s.nome AS servico_nome, s.descricao AS servico_desc,
                   cat.nome AS categoria_nome
            FROM tecnico t
            INNER JOIN servico s ON s.tecnico_id = t.id AND s.ativo = 1
            INNER JOIN categoria cat ON cat.id = s.categoria_id
            WHERE t.ativo = 1 AND t.status_cadastro = 'Aprovado'
        ";
        $params = [];
        if ($categoria !== null && $categoria !== '') {
            $sql .= " AND cat.nome = ?";
            $params[] = $categoria;
        }
        $sql .= " ORDER BY t.destaque DESC, t.avaliacao_media DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $agrupado = [];
        foreach ($rows as $row) {
            $tid = $row['id'];
            if (!isset($agrupado[$tid])) {
                $agrupado[$tid] = [
                    'id'             => $tid,
                    'nome'           => $row['nome'],
                    'especialidade'  => $row['especialidade'],
                    'foto_perfil'    => $row['foto_perfil'],
                    'avaliacao_media'=> $row['avaliacao_media'],
                    'destaque'       => $row['destaque'],
                    'servicos'       => [],
                ];
            }
            $agrupado[$tid]['servicos'][] = [
                'nome'          => $row['servico_nome'],
                'descricao'     => $row['servico_desc'],
                'categoria_nome'=> $row['categoria_nome'],
            ];
        }
        return array_values($agrupado);
    }

    public function buscarTecnicosDestaquesPorCategoria(string $categoria): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT s.tecnico_id FROM servico s
            INNER JOIN categoria cat ON cat.id = s.categoria_id
            INNER JOIN tecnico t ON t.id = s.tecnico_id
            WHERE cat.nome = ? AND s.ativo = 1 AND t.ativo = 1
              AND t.status_cadastro = 'Aprovado' AND t.destaque = 1
        ");
        $stmt->execute([$categoria]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
