<?php

class AdminDTO
{
    public int    $id        = 0;
    public string $nome      = '';
    public string $email     = '';
    public string $senha     = '';
    public string $telefone  = '';
    public string $genero    = 'Prefiro não informar';
    public string $perfil    = 'Master';
    public string $fotoPerfil = '';
    public string $criadoEm  = '';

    public static function fromArray(array $row): self
    {
        $dto             = new self();
        $dto->id         = (int)($row['id']         ?? 0);
        $dto->nome       = $row['nome']              ?? '';
        $dto->email      = $row['email']             ?? '';
        $dto->senha      = $row['senha']             ?? '';
        $dto->telefone   = $row['telefone']          ?? '';
        $dto->genero     = $row['genero']            ?? 'Prefiro não informar';
        $dto->perfil     = $row['perfil']            ?? 'Master';
        $dto->fotoPerfil = $row['foto_perfil']       ?? '';
        $dto->criadoEm   = $row['criado_em']         ?? '';
        return $dto;
    }
}
