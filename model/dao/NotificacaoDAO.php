<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/NotificacaoDTO.php';

class NotificacaoDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    /** @return NotificacaoDTO[] */
    public function listarPorCliente(int $clienteId, int $limit = 60): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notificacao
            WHERE cliente_id=? AND tipo_destinatario='cliente'
            ORDER BY criado_em DESC LIMIT {$limit}
        ");
        $stmt->execute([$clienteId]);
        return array_map([NotificacaoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    /** @return NotificacaoDTO[] */
    public function listarPorTecnico(int $tecnicoId, int $limit = 60): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notificacao
            WHERE tecnico_id=? AND tipo_destinatario='prestador'
            ORDER BY criado_em DESC LIMIT {$limit}
        ");
        $stmt->execute([$tecnicoId]);
        return array_map([NotificacaoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    /** @return NotificacaoDTO[] */
    public function listarAdmin(int $limit = 60): array
    {
        return array_map(
            [NotificacaoDTO::class, 'fromArray'],
            $this->pdo->query("
                SELECT * FROM notificacao WHERE tipo_destinatario='admin'
                ORDER BY criado_em DESC LIMIT {$limit}
            ")->fetchAll()
        );
    }

    public function marcarLidaCliente(int $id, int $clienteId): void
    {
        $this->pdo->prepare("UPDATE notificacao SET lida=1 WHERE id=? AND cliente_id=?")
            ->execute([$id, $clienteId]);
    }

    public function marcarLidaTecnico(int $id, int $tecnicoId): void
    {
        $this->pdo->prepare("UPDATE notificacao SET lida=1 WHERE id=? AND tecnico_id=?")
            ->execute([$id, $tecnicoId]);
    }

    public function marcarTodasLidasCliente(int $clienteId): void
    {
        $this->pdo->prepare("UPDATE notificacao SET lida=1 WHERE cliente_id=? AND tipo_destinatario='cliente'")
            ->execute([$clienteId]);
    }

    public function marcarTodasLidasTecnico(int $tecnicoId): void
    {
        $this->pdo->prepare("UPDATE notificacao SET lida=1 WHERE tecnico_id=? AND tipo_destinatario='prestador'")
            ->execute([$tecnicoId]);
    }

    public function contarNaoLidasCliente(int $clienteId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notificacao WHERE cliente_id=? AND tipo_destinatario='cliente' AND lida=0
        ");
        $stmt->execute([$clienteId]);
        return (int)$stmt->fetchColumn();
    }

    public function contarNaoLidasTecnico(int $tecnicoId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notificacao WHERE tecnico_id=? AND tipo_destinatario='prestador' AND lida=0
        ");
        $stmt->execute([$tecnicoId]);
        return (int)$stmt->fetchColumn();
    }
}
