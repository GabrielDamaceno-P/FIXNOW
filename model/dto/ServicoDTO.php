<?php

class ServicoDTO
{
    public int     $id           = 0;
    public int     $tecnicoId    = 0;
    public ?int    $categoriaId  = null;
    public string  $nome         = '';
    public string  $descricao    = '';
    public float   $preco        = 0.0;
    public int     $ativo        = 1;
    public string  $criadoEm     = '';
    public ?string $categoriaNome = null;

    public static function fromArray(array $row): self
    {
        $dto               = new self();
        $dto->id           = (int)($row['id']           ?? 0);
        $dto->tecnicoId    = (int)($row['tecnico_id']   ?? 0);
        $dto->categoriaId  = isset($row['categoria_id']) ? (int)$row['categoria_id'] : null;
        $dto->nome         = $row['nome']                ?? '';
        $dto->descricao    = $row['descricao']           ?? '';
        $dto->preco        = (float)($row['preco']       ?? 0);
        $dto->ativo        = (int)($row['ativo']         ?? 1);
        $dto->criadoEm     = $row['criado_em']           ?? '';
        $dto->categoriaNome = $row['categoria_nome']     ?? null;
        return $dto;
    }
}
