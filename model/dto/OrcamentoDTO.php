<?php

class OrcamentoDTO
{
    public int     $id            = 0;
    public int     $chamadoId     = 0;
    public int     $tecnicoId     = 0;
    public float   $valor         = 0.0;
    public string  $descricao     = '';
    public ?int    $prazoDias     = null;
    public string  $status        = 'Pendente';
    public ?string $motivoRecusa  = null;
    public string  $criadoEm      = '';
    public ?string $tecnicoNome   = null;
    public ?string $clienteNome   = null;
    public ?string $chamadoDesc   = null;
    public ?string $chamadoStatus = null;

    public static function fromArray(array $row): self
    {
        $dto               = new self();
        $dto->id           = (int)($row['id']           ?? 0);
        $dto->chamadoId    = (int)($row['chamado_id']   ?? 0);
        $dto->tecnicoId    = (int)($row['tecnico_id']   ?? 0);
        $dto->valor        = (float)($row['valor']       ?? 0);
        $dto->descricao    = $row['descricao']           ?? '';
        $dto->prazoDias    = isset($row['prazo_dias'])   ? (int)$row['prazo_dias'] : null;
        $dto->status       = $row['status']              ?? 'Pendente';
        $dto->motivoRecusa = $row['motivo_recusa']       ?? null;
        $dto->criadoEm     = $row['criado_em']           ?? '';
        $dto->tecnicoNome  = $row['tecnico_nome']        ?? null;
        $dto->clienteNome  = $row['cliente_nome']        ?? null;
        $dto->chamadoDesc  = $row['chamado_desc']        ?? null;
        $dto->chamadoStatus = $row['chamado_status']     ?? null;
        return $dto;
    }
}
