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
        $stmt = $this->pdo->prepare('
            INSERT INTO mensagem_chamado (chamado_id, remetente_tipo, remetente_id, mensagem, arquivo_path, arquivo_nome)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$chamadoId, $tipo, $remetenteId, $mensagem ?: null, $arquivoPath, $arquivoNome]);
        return (int)$this->pdo->lastInsertId();
    }

    public function marcarLidas(int $chamadoId, string $tipoRemetente): void
    {
        $this->pdo->prepare("
            UPDATE mensagem_chamado SET lida=1
            WHERE chamado_id=? AND remetente_tipo=? AND lida=0
        ")->execute([$chamadoId, $tipoRemetente]);
    }

    public function contarNaoLidas(int $chamadoId, string $tipoRemetente): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM mensagem_chamado
            WHERE chamado_id=? AND remetente_tipo=? AND lida=0
        ");
        $stmt->execute([$chamadoId, $tipoRemetente]);
        return (int)$stmt->fetchColumn();
    }
}
