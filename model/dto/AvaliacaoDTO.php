<?php

class AvaliacaoDTO
{
    public int     $id          = 0;
    public int     $chamadoId   = 0;
    public int     $clienteId   = 0;
    public int     $tecnicoId   = 0;
    public int     $nota        = 5;
    public ?string $comentario  = null;
    public string  $criadoEm    = '';
    public ?string $clienteNome = null;
    public ?string $tecnicoNome = null;

    public static function fromArray(array $row): self
    {
        $dto              = new self();
        $dto->id          = (int)($row['id']          ?? 0);
        $dto->chamadoId   = (int)($row['chamado_id']  ?? 0);
        $dto->clienteId   = (int)($row['cliente_id']  ?? 0);
        $dto->tecnicoId   = (int)($row['tecnico_id']  ?? 0);
        $dto->nota        = (int)($row['nota']         ?? 5);
        $dto->comentario  = $row['comentario']         ?? null;
        $dto->criadoEm    = $row['criado_em']          ?? '';
        $dto->clienteNome = $row['cliente_nome']       ?? null;
        $dto->tecnicoNome = $row['tecnico_nome']       ?? null;
        return $dto;
    }
}
