<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/SuporteDTO.php';
require_once __DIR__ . '/../../includes/helpers.php';

class SuporteDAO
{
    private PDO $pdo;

    public const CATEGORIAS  = ['Pagamento', 'Técnico', 'Conta', 'Outro'];
    public const PRIORIDADES = ['Baixa', 'Normal', 'Alta', 'Urgente'];

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    /** @return int ID do ticket criado, ou 0 em caso de erro */
    public function abrir(string $tipoUsuario, int $usuarioId, string $assunto, string $categoria, string $prioridade, string $texto, ?int $chamadoId = null): int
    {
        $clienteId = $tipoUsuario === 'cliente'   ? $usuarioId : null;
        $tecnicoId = $tipoUsuario === 'prestador' ? $usuarioId : null;

        try {
            $this->pdo->beginTransaction();
            if ($chamadoId !== null) {
                $this->pdo->prepare("
                    INSERT INTO suporte (cliente_id, tecnico_id, assunto, categoria, prioridade, status, chamado_id)
                    VALUES (?,?,?,?,?,'Aberto',?)
                ")->execute([$clienteId, $tecnicoId, $assunto, $categoria, $prioridade, $chamadoId]);
            } else {
                $this->pdo->prepare("
                    INSERT INTO suporte (cliente_id, tecnico_id, assunto, categoria, prioridade, status)
                    VALUES (?,?,?,?,?,'Aberto')
                ")->execute([$clienteId, $tecnicoId, $assunto, $categoria, $prioridade]);
            }
            $sid = (int)$this->pdo->lastInsertId();
            $this->pdo->prepare("
                INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,?,?,?)
            ")->execute([$sid, $tipoUsuario, $usuarioId, $texto]);
            $this->pdo->commit();
            $chamadoInfo = $chamadoId ? " | Chamado #{$chamadoId}" : '';
            fixnow_notificar_admin($this->pdo, "Novo ticket #{$sid} [{$categoria} / {$prioridade}]{$chamadoInfo} de {$tipoUsuario}: {$assunto}");
            return $sid;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return 0;
        }
    }

    public function registrarAcao(int $suporteId, int $adminId, string $descricao): void
    {
        $this->pdo->prepare(
            "INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)"
        )->execute([$suporteId, $adminId, '[AÇÃO] ' . $descricao]);
        $this->pdo->prepare("UPDATE suporte SET atualizado_em=NOW() WHERE id=?")->execute([$suporteId]);
    }

    public function adicionarMensagem(int $suporteId, string $tipoUsuario, int $usuarioId, string $texto): bool
    {
        $col = $tipoUsuario === 'cliente' ? 'cliente_id' : 'tecnico_id';
        $stk = $this->pdo->prepare("SELECT id, status FROM suporte WHERE id=? AND {$col}=?");
        $stk->execute([$suporteId, $usuarioId]);
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

    /** @return SuporteDTO[] */
    public function listarPorUsuario(string $tipoUsuario, int $usuarioId): array
    {
        $col  = $tipoUsuario === 'cliente' ? 'cliente_id' : 'tecnico_id';
        $stmt = $this->pdo->prepare("SELECT * FROM suporte WHERE {$col}=? ORDER BY criado_em DESC");
        $stmt->execute([$usuarioId]);
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

    /** @return SuporteDTO[] */
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
            UPDATE suporte SET resposta=?, status=?, admin_id=? WHERE id=?
        ")->execute([$resposta, $status, $adminId, $id]);
    }

    public function reabrir(int $suporteId, string $tipoUsuario, int $usuarioId): bool
    {
        $col = $tipoUsuario === 'cliente' ? 'cliente_id' : 'tecnico_id';
        $stk = $this->pdo->prepare("SELECT id, status FROM suporte WHERE id=? AND {$col}=?");
        $stk->execute([$suporteId, $usuarioId]);
        $tk = $stk->fetch();
        if (!$tk || $tk['status'] !== 'Fechado') return false;

        $this->pdo->prepare(
            "UPDATE suporte SET status='Aberto', resposta=NULL, admin_id=NULL, atualizado_em=NOW() WHERE id=?"
        )->execute([$suporteId]);
        $this->pdo->prepare(
            "INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,?,?,'Ticket reaberto pelo usuário.')"
        )->execute([$suporteId, $tipoUsuario, $usuarioId]);
        fixnow_notificar_admin($this->pdo, "Ticket #{$suporteId} foi reaberto pelo usuário.");
        return true;
    }
}
