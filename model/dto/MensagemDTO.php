<?php

class MensagemDTO
{
    public int     $id             = 0;
    public int     $chamadoId      = 0;
    public ?int    $clienteId      = null;
    public ?int    $tecnicoId      = null;
    public string  $mensagem       = '';
    public ?string $arquivoPath    = null;
    public ?string $arquivoNome    = null;
    public int     $lida           = 0;
    public string  $criadoEm       = '';

    public static function fromArray(array $row): self
    {
        $dto               = new self();
        $dto->id           = (int)($row['id']              ?? 0);
        $dto->chamadoId    = (int)($row['chamado_id']      ?? 0);
        $dto->clienteId    = isset($row['cliente_id'])  ? (int)$row['cliente_id']  : null;
        $dto->tecnicoId    = isset($row['tecnico_id'])  ? (int)$row['tecnico_id']  : null;
        $dto->mensagem     = $row['mensagem']               ?? '';
        $dto->arquivoPath  = $row['arquivo_path']           ?? null;
        $dto->arquivoNome  = $row['arquivo_nome']           ?? null;
        $dto->lida         = (int)($row['lida']             ?? 0);
        $dto->criadoEm     = $row['criado_em']              ?? '';
        return $dto;
    }
}
