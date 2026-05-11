<?php

class SuporteMensagemDTO
{
    public int    $id          = 0;
    public int    $suporteId   = 0;
    public string $autorTipo   = 'cliente';
    public int    $autorId     = 0;
    public string $mensagem    = '';
    public string $criadoEm   = '';

    public static function fromArray(array $row): self
    {
        $dto            = new self();
        $dto->id        = (int)($row['id']          ?? 0);
        $dto->suporteId = (int)($row['suporte_id']  ?? 0);
        $dto->autorTipo = $row['autor_tipo']          ?? 'cliente';
        $dto->autorId   = (int)($row['autor_id']    ?? 0);
        $dto->mensagem  = $row['mensagem']            ?? '';
        $dto->criadoEm  = $row['criado_em']           ?? '';
        return $dto;
    }
}

class SuporteDTO
{
    public int     $id            = 0;
    public string  $tipoUsuario   = 'cliente';
    public int     $usuarioId     = 0;
    public string  $assunto       = '';
    public string  $categoria     = 'Outro';
    public string  $prioridade    = 'Normal';
    public string  $status        = 'Aberto';
    public ?string $resposta      = null;
    public ?int    $respondidoPor = null;
    public string  $criadoEm     = '';
    public string  $atualizadoEm = '';
    /** @var SuporteMensagemDTO[] */
    public array   $mensagens     = [];

    public static function fromArray(array $row): self
    {
        $dto               = new self();
        $dto->id           = (int)($row['id']            ?? 0);
        $dto->tipoUsuario  = $row['tipo_usuario']          ?? 'cliente';
        $dto->usuarioId    = (int)($row['usuario_id']    ?? 0);
        $dto->assunto      = $row['assunto']               ?? '';
        $dto->categoria    = $row['categoria']             ?? 'Outro';
        $dto->prioridade   = $row['prioridade']            ?? 'Normal';
        $dto->status       = $row['status']                ?? 'Aberto';
        $dto->resposta     = $row['resposta']              ?? null;
        $dto->respondidoPor = isset($row['respondido_por']) ? (int)$row['respondido_por'] : null;
        $dto->criadoEm     = $row['criado_em']             ?? '';
        $dto->atualizadoEm = $row['atualizado_em']         ?? '';
        return $dto;
    }
}
