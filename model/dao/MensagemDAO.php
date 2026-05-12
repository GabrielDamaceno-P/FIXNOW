<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/MensagemDTO.php';

class MensagemDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function tabelaExiste(): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM mensagem_chamado LIMIT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /** @return MensagemDTO[] */
    public function listarPorChamado(int $chamadoId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM mensagem_chamado WHERE chamado_id=? ORDER BY criado_em ASC
        ');
        $stmt->execute([$chamadoId]);
        return array_map([MensagemDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    public function inserir(int $chamadoId, string $tipo, int $remetenteId, string $mensagem, ?string $arquivoPath = null, ?string $arquivoNome = null): int
    {
        $clienteId = $tipo === 'cliente'   ? $remetenteId : null;
        $tecnicoId = $tipo === 'prestador' ? $remetenteId : null;
        $stmt = $this->pdo->prepare('
            INSERT INTO mensagem_chamado (chamado_id, cliente_id, tecnico_id, mensagem, arquivo_path, arquivo_nome)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$chamadoId, $clienteId, $tecnicoId, $mensagem ?: null, $arquivoPath, $arquivoNome]);
        return (int)$this->pdo->lastInsertId();
    }

    public function marcarLidas(int $chamadoId, string $tipoRemetente): void
    {
        $col = $tipoRemetente === 'cliente' ? 'cliente_id' : 'tecnico_id';
        $this->pdo->prepare("
            UPDATE mensagem_chamado SET lida=1
            WHERE chamado_id=? AND $col IS NOT NULL AND lida=0
        ")->execute([$chamadoId]);
    }

    public function contarNaoLidas(int $chamadoId, string $tipoRemetente): int
    {
        $col  = $tipoRemetente === 'cliente' ? 'cliente_id' : 'tecnico_id';
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM mensagem_chamado
            WHERE chamado_id=? AND $col IS NOT NULL AND lida=0
        ");
        $stmt->execute([$chamadoId]);
        return (int)$stmt->fetchColumn();
    }
}
