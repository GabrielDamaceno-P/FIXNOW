<?php

class TecnicoDTO
{
    public int    $id              = 0;
    public string $nome            = '';
    public string $email           = '';
    public string $senha           = '';
    public string $cpf             = '';
    public string $especialidade   = '';
    public string $telefone        = '';
    public string $genero          = 'Masculino';
    public string $fotoPerfil      = '';
    public float  $avaliacaoMedia  = 0.0;
    public int    $ativo           = 1;
    public string $statusCadastro  = 'Pendente';
    public int    $destaque        = 0;
    public string $criadoEm        = '';

    public static function fromArray(array $row): self
    {
        $dto                = new self();
        $dto->id            = (int)($row['id']              ?? 0);
        $dto->nome          = $row['nome']                   ?? '';
        $dto->email         = $row['email']                  ?? '';
        $dto->senha         = $row['senha']                  ?? '';
        $dto->cpf           = $row['cpf']                    ?? '';
        $dto->especialidade = $row['especialidade']          ?? '';
        $dto->telefone      = $row['telefone']               ?? '';
        $dto->genero        = $row['genero']                 ?? 'Masculino';
        $dto->fotoPerfil    = $row['foto_perfil']            ?? '';
        $dto->avaliacaoMedia = (float)($row['avaliacao_media'] ?? 0);
        $dto->ativo         = (int)($row['ativo']            ?? 1);
        $dto->statusCadastro = $row['status_cadastro']       ?? 'Pendente';
        $dto->destaque      = (int)($row['destaque']         ?? 0);
        $dto->criadoEm      = $row['criado_em']              ?? '';
        return $dto;
    }
}
