<?php

class PagamentoDTO
{
    public int     $id        = 0;
    public int     $chamadoId = 0;
    public string  $metodo    = 'PIX';
    public float   $valor     = 0.0;
    public string  $status    = 'Pendente';
    public ?string $pagoEm   = null;
    public string  $criadoEm  = '';

    public static function fromArray(array $row): self
    {
        $dto           = new self();
        $dto->id       = (int)($row['id']         ?? 0);
        $dto->chamadoId = (int)($row['chamado_id'] ?? 0);
        $dto->metodo   = $row['metodo']             ?? 'PIX';
        $dto->valor    = (float)($row['valor']      ?? 0);
        $dto->status   = $row['status']             ?? 'Pendente';
        $dto->pagoEm   = $row['pago_em']            ?? null;
        $dto->criadoEm = $row['criado_em']          ?? '';
        return $dto;
    }
}
