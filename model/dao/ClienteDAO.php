<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/ClienteDTO.php';

class ClienteDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function buscarPorEmail(string $email, int $isAdmin = 0): ?ClienteDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cliente WHERE email = ? AND is_admin = ?');
        $stmt->execute([$email, $isAdmin]);
        $row = $stmt->fetch();
        return $row ? ClienteDTO::fromArray($row) : null;
    }

    public function buscarPorId(int $id): ?ClienteDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cliente WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ClienteDTO::fromArray($row) : null;
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM cliente WHERE email = ?');
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    public function cpfExiste(string $cpf): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM cliente WHERE cpf = ?');
        $stmt->execute([$cpf]);
        return (bool)$stmt->fetch();
    }

    public function inserir(ClienteDTO $dto): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO cliente (nome, email, senha, cpf, telefone, endereco, cep, foto_perfil, genero)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $dto->nome, $dto->email, $dto->senha, $dto->cpf,
            $dto->telefone, $dto->endereco, $dto->cep, $dto->fotoPerfil, $dto->genero,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function atualizar(ClienteDTO $dto): void
    {
        $this->pdo->prepare('
            UPDATE cliente SET nome=?, telefone=?, endereco=?, cep=?, genero=?, cpf=?, foto_perfil=?
            WHERE id=?
        ')->execute([
            $dto->nome, $dto->telefone, $dto->endereco, $dto->cep,
            $dto->genero, $dto->cpf, $dto->fotoPerfil, $dto->id,
        ]);
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $this->pdo->prepare('UPDATE cliente SET senha = ? WHERE id = ?')
            ->execute([$senhaHash, $id]);
    }
}
