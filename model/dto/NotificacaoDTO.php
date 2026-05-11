<?php

class NotificacaoDTO
{
    public int     $id               = 0;
    public string  $tipoDestinatario = 'cliente';
    public ?int    $clienteId        = null;
    public ?int    $tecnicoId        = null;
    public ?int    $chamadoId        = null;
    public string  $mensagem         = '';
    public bool    $lida             = false;
    public string  $criadoEm         = '';

    public static function fromArray(array $row): self
    {
        $dto                   = new self();
        $dto->id               = (int)($row['id']                ?? 0);
        $dto->tipoDestinatario = $row['tipo_destinatario']        ?? 'cliente';
        $dto->clienteId        = isset($row['cliente_id'])  ? (int)$row['cliente_id']  : null;
        $dto->tecnicoId        = isset($row['tecnico_id'])  ? (int)$row['tecnico_id']  : null;
        $dto->chamadoId        = isset($row['chamado_id'])  ? (int)$row['chamado_id']  : null;
        $dto->mensagem         = $row['mensagem']                 ?? '';
        $dto->lida             = (bool)($row['lida']              ?? false);
        $dto->criadoEm         = $row['criado_em']                ?? '';
        return $dto;
    }
}
