<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/TecnicoDTO.php';

class TecnicoDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function buscarPorEmail(string $email): ?TecnicoDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tecnico WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? TecnicoDTO::fromArray($row) : null;
    }

    public function buscarPorId(int $id): ?TecnicoDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tecnico WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? TecnicoDTO::fromArray($row) : null;
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM tecnico WHERE email = ?');
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    public function cpfExiste(string $cpf): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM tecnico WHERE cpf = ?');
        $stmt->execute([$cpf]);
        return (bool)$stmt->fetch();
    }

    public function inserir(TecnicoDTO $dto): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO tecnico (nome, email, senha, cpf, especialidade, telefone, genero, foto_perfil, status_cadastro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $dto->nome, $dto->email, $dto->senha, $dto->cpf ?: null,
            $dto->especialidade, $dto->telefone, $dto->genero,
            $dto->fotoPerfil, $dto->statusCadastro,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function atualizar(TecnicoDTO $dto): void
    {
        $this->pdo->prepare('
            UPDATE tecnico SET nome=?, telefone=?, genero=?, especialidade=?, foto_perfil=?, cpf=?
            WHERE id=?
        ')->execute([$dto->nome, $dto->telefone, $dto->genero, $dto->especialidade, $dto->fotoPerfil, $dto->cpf, $dto->id]);
    }

    public function atualizarSenha(int $id, string $hash): void
    {
        $this->pdo->prepare('UPDATE tecnico SET senha=? WHERE id=?')->execute([$hash, $id]);
    }

    /** @return TecnicoDTO[] */
    public function listarAprovados(?string $categoria = null): array
    {
        $sql = "
            SELECT DISTINCT t.*,
              (SELECT GROUP_CONCAT(DISTINCT cat.nome ORDER BY cat.nome SEPARATOR ', ')
               FROM servico sv INNER JOIN categoria cat ON cat.id = sv.categoria_id
               WHERE sv.tecnico_id = t.id AND sv.ativo = 1) AS categorias
            FROM tecnico t
            INNER JOIN servico s ON s.tecnico_id = t.id AND s.ativo = 1
            INNER JOIN categoria cat ON cat.id = s.categoria_id
            WHERE t.ativo = 1 AND t.status_cadastro = 'Aprovado'
        ";
        $params = [];
        if ($categoria) {
            $sql .= " AND cat.nome = ?";
            $params[] = $categoria;
        }
        $sql .= " ORDER BY t.destaque DESC, t.avaliacao_media DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([TecnicoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    public function estatisticas(): array
    {
        return $this->pdo->query("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN status_cadastro='Aprovado' THEN 1 ELSE 0 END) AS aprovados,
                   SUM(CASE WHEN status_cadastro='Pendente' THEN 1 ELSE 0 END) AS pendentes
            FROM tecnico
        ")->fetch() ?: ['total' => 0, 'aprovados' => 0, 'pendentes' => 0];
    }
}
