<?php

class ClienteDTO
{
    public int    $id        = 0;
    public string $nome      = '';
    public string $email     = '';
    public string $senha     = '';
    public string $cpf       = '';
    public string $telefone  = '';
    public string $endereco  = '';
    public string $cep       = '';
    public string $fotoPerfil = '';
    public string $genero    = 'Prefiro não informar';
    public int    $isAdmin   = 0;

    public static function fromArray(array $row): self
    {
        $dto              = new self();
        $dto->id          = (int)($row['id']          ?? 0);
        $dto->nome        = $row['nome']               ?? '';
        $dto->email       = $row['email']              ?? '';
        $dto->senha       = $row['senha']              ?? '';
        $dto->cpf         = $row['cpf']                ?? '';
        $dto->telefone    = $row['telefone']           ?? '';
        $dto->endereco    = $row['endereco']           ?? '';
        $dto->cep         = $row['cep']                ?? '';
        $dto->fotoPerfil  = $row['foto_perfil']        ?? '';
        $dto->genero      = $row['genero']             ?? 'Prefiro não informar';
        $dto->isAdmin     = (int)($row['is_admin']     ?? 0);
        return $dto;
    }
}
