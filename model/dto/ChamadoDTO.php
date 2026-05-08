<?php

class ChamadoDTO
{
    public int     $id               = 0;
    public int     $clienteId        = 0;
    public ?int    $tecnicoId        = null;
    public string  $categoria        = '';
    public string  $descricao        = '';
    public string  $status           = 'Pendente';
    public float   $precoSugerido    = 0.0;
    public string  $enderecoServico  = '';
    public ?string $dataAgendamento  = null;
    public string  $criadoEm         = '';
    public ?string $tecnicoNome      = null;
    public ?string $clienteNome      = null;
    public ?string $tecnicoFoto      = null;
    public ?string $clienteFoto      = null;
    public ?int    $pagamentoId      = null;
    public ?string $pagStatus        = null;
    public ?float  $pagValor         = null;
    public ?string $pagMetodo        = null;
    public ?int    $avaliacaoNota            = null;
    public ?string $fotoPath                 = null;
    public bool    $reagendamentoPendente    = false;
    public ?string $dataAgendamentoProposta  = null;

    public static function fromArray(array $row): self
    {
        $dto                          = new self();
        $dto->id                      = (int)($row['id']               ?? 0);
        $dto->clienteId               = (int)($row['cliente_id']       ?? 0);
        $dto->tecnicoId               = isset($row['tecnico_id'])  ? (int)$row['tecnico_id']  : null;
        $dto->categoria               = $row['categoria']              ?? '';
        $dto->descricao               = $row['descricao']              ?? '';
        $dto->status                  = $row['status']                 ?? 'Pendente';
        $dto->precoSugerido           = (float)($row['preco_sugerido'] ?? 0);
        $dto->enderecoServico         = $row['endereco_servico']       ?? '';
        $dto->dataAgendamento         = $row['data_agendamento']       ?? null;
        $dto->criadoEm                = $row['criado_em']              ?? '';
        $dto->tecnicoNome             = $row['tecnico_nome']           ?? null;
        $dto->clienteNome             = $row['cliente_nome']           ?? null;
        $dto->tecnicoFoto             = $row['tecnico_foto']           ?? null;
        $dto->clienteFoto             = $row['cliente_foto']           ?? null;
        $dto->pagamentoId             = isset($row['pagamento_id'])    ? (int)$row['pagamento_id'] : null;
        $dto->pagStatus               = $row['pag_status']             ?? null;
        $dto->pagValor                = isset($row['pag_valor'])       ? (float)$row['pag_valor']  : null;
        $dto->pagMetodo               = $row['pag_metodo']             ?? null;
        $dto->avaliacaoNota           = isset($row['avaliacao_nota'])  ? (int)$row['avaliacao_nota'] : null;
        $dto->fotoPath                = $row['foto_path']              ?? null;
        $dto->reagendamentoPendente   = !empty($row['reagendamento_pendente']);
        $dto->dataAgendamentoProposta = $row['data_agendamento_proposta'] ?? null;
        return $dto;
    }
}
