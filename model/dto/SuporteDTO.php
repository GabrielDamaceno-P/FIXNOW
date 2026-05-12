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
    public ?int    $clienteId     = null;
    public ?int    $tecnicoId     = null;
    public string  $assunto       = '';
    public string  $categoria     = 'Outro';
    public string  $prioridade    = 'Normal';
    public string  $status        = 'Aberto';
    public ?int    $chamadoId     = null;
    public ?string $resposta      = null;
    public ?int    $respondidoPor = null;
    public string  $criadoEm     = '';
    public string  $atualizadoEm = '';
    /** @var SuporteMensagemDTO[] */
    public array   $mensagens     = [];

    public function tipoUsuario(): string
    {
        if ($this->clienteId)  return 'cliente';
        if ($this->tecnicoId)  return 'prestador';
        return 'desconhecido';
    }

    public static function fromArray(array $row): self
    {
        $dto               = new self();
        $dto->id           = (int)($row['id']            ?? 0);
        $dto->clienteId    = isset($row['cliente_id'])  ? (int)$row['cliente_id']  : null;
        $dto->tecnicoId    = isset($row['tecnico_id'])  ? (int)$row['tecnico_id']  : null;
        $dto->assunto      = $row['assunto']               ?? '';
        $dto->categoria    = $row['categoria']             ?? 'Outro';
        $dto->prioridade   = $row['prioridade']            ?? 'Normal';
        $dto->status       = $row['status']                ?? 'Aberto';
        $dto->chamadoId    = isset($row['chamado_id'])  ? (int)$row['chamado_id']  : null;
        $dto->resposta     = $row['resposta']              ?? null;
        $dto->respondidoPor = isset($row['admin_id']) ? (int)$row['admin_id'] : null;
        $dto->criadoEm     = $row['criado_em']             ?? '';
        $dto->atualizadoEm = $row['atualizado_em']         ?? '';
        return $dto;
    }
}
