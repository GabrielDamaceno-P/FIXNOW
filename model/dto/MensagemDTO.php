<?php

class MensagemDTO
{
    public int     $id             = 0;
    public int     $chamadoId      = 0;
    public string  $remetenteType  = 'cliente';
    public int     $remetenteId    = 0;
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
        $dto->remetenteType = $row['remetente_tipo']        ?? 'cliente';
        $dto->remetenteId  = (int)($row['remetente_id']    ?? 0);
        $dto->mensagem     = $row['mensagem']               ?? '';
        $dto->arquivoPath  = $row['arquivo_path']           ?? null;
        $dto->arquivoNome  = $row['arquivo_nome']           ?? null;
        $dto->lida         = (int)($row['lida']             ?? 0);
        $dto->criadoEm     = $row['criado_em']              ?? '';
        return $dto;
    }
}
