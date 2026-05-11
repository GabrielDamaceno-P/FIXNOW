<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/SuporteDTO.php';
require_once __DIR__ . '/../../includes/helpers.php';

class SuporteDAO
{
    private PDO $pdo;

    const CATEGORIAS  = ['Pagamento', 'Técnico', 'Conta', 'Outro'];
    const PRIORIDADES = ['Baixa', 'Normal', 'Alta', 'Urgente'];

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    /**
     * Abre um novo ticket com a primeira mensagem.
     * @return int ID do ticket criado, ou 0 em caso de erro
     */
    public function abrir(string $tipoUsuario, int $usuarioId, string $assunto, string $categoria, string $prioridade, string $texto): int
    {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("
                INSERT INTO suporte (tipo_usuario, usuario_id, assunto, categoria, prioridade, status)
                VALUES (?,?,?,?,?,'Aberto')
            ")->execute([$tipoUsuario, $usuarioId, $assunto, $categoria, $prioridade]);
            $sid = (int)$this->pdo->lastInsertId();
            $this->pdo->prepare("
                INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,?,?,?)
            ")->execute([$sid, $tipoUsuario, $usuarioId, $texto]);
            $this->pdo->commit();
            fixnow_notificar_admin($this->pdo, "Novo ticket #{$sid} [{$categoria} / {$prioridade}] de {$tipoUsuario}: {$assunto}");
            return $sid;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return 0;
        }
    }

    /**
     * Adiciona mensagem a um ticket existente do usuário.
     */
    public function adicionarMensagem(int $suporteId, string $tipoUsuario, int $usuarioId, string $texto): bool
    {
        $stk = $this->pdo->prepare('SELECT id, status FROM suporte WHERE id=? AND tipo_usuario=? AND usuario_id=?');
        $stk->execute([$suporteId, $tipoUsuario, $usuarioId]);
        $tk = $stk->fetch();
        if (!$tk || $tk['status'] === 'Fechado') return false;

        $this->pdo->prepare("
            INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,?,?,?)
        ")->execute([$suporteId, $tipoUsuario, $usuarioId, $texto]);
        $this->pdo->prepare("UPDATE suporte SET status='Em Andamento', atualizado_em=NOW() WHERE id=?")
            ->execute([$suporteId]);
        fixnow_notificar_admin($this->pdo, "Nova mensagem no ticket #{$suporteId} de {$tipoUsuario}.");
        return true;
    }

    /**
     * Lista tickets do usuário com mensagens embutidas.
     * @return SuporteDTO[]
     */
    public function listarPorUsuario(string $tipoUsuario, int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM suporte WHERE tipo_usuario=? AND usuario_id=? ORDER BY criado_em DESC
        ');
        $stmt->execute([$tipoUsuario, $usuarioId]);
        $rows = $stmt->fetchAll();
        if (!$rows) return [];

        $ids = array_column($rows, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $msgs = $this->pdo->prepare("
            SELECT * FROM suporte_mensagem WHERE suporte_id IN ($ph) ORDER BY criado_em ASC
        ");
        $msgs->execute($ids);

        $byTicket = [];
        foreach ($msgs->fetchAll() as $m) {
            $byTicket[$m['suporte_id']][] = SuporteMensagemDTO::fromArray($m);
        }

        return array_map(function (array $row) use ($byTicket): SuporteDTO {
            $dto = SuporteDTO::fromArray($row);
            $dto->mensagens = $byTicket[$dto->id] ?? [];
            return $dto;
        }, $rows);
    }

    /**
     * Lista todos os tickets (uso admin).
     * @return SuporteDTO[]
     */
    public function listarTodos(int $limit = 50): array
    {
        $rows = $this->pdo->query("SELECT * FROM suporte ORDER BY criado_em DESC LIMIT {$limit}")->fetchAll();
        return array_map([SuporteDTO::class, 'fromArray'], $rows);
    }

    public function buscarPorId(int $id): ?SuporteDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM suporte WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $dto = SuporteDTO::fromArray($row);

        $msgs = $this->pdo->prepare('SELECT * FROM suporte_mensagem WHERE suporte_id=? ORDER BY criado_em ASC');
        $msgs->execute([$id]);
        $dto->mensagens = array_map([SuporteMensagemDTO::class, 'fromArray'], $msgs->fetchAll());
        return $dto;
    }

    public function responder(int $id, string $resposta, string $status, int $adminId): void
    {
        $this->pdo->prepare("
            UPDATE suporte SET resposta=?, status=?, respondido_por=? WHERE id=?
        ")->execute([$resposta, $status, $adminId, $id]);
    }
}
