<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/ChamadoDTO.php';

class ChamadoDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function listarPorCliente(int $clienteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, t.nome AS tecnico_nome, t.foto_perfil AS tecnico_foto,
                   p.id AS pagamento_id, p.status AS pag_status,
                   p.valor AS pag_valor, p.metodo AS pag_metodo,
                   a.nota AS avaliacao_nota
            FROM chamado c
            LEFT JOIN tecnico t  ON t.id = c.tecnico_id
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            LEFT JOIN avaliacao a ON a.chamado_id = c.id AND a.cliente_id = ?
            WHERE c.cliente_id = ?
            ORDER BY c.criado_em DESC
        ");
        $stmt->execute([$clienteId, $clienteId]);
        return array_map([ChamadoDTO::class, 'fromArray'], $stmt->fetchAll());
    }

    public function listarEmAndamentoPorTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome, cl.telefone AS cliente_telefone,
                   cl.endereco AS cliente_endereco, cl.genero AS cliente_genero,
                   cl.foto_perfil AS cliente_foto,
                   p.id AS pagamento_id, p.status AS pag_status, p.valor AS pag_valor
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            WHERE c.tecnico_id = ? AND c.status = 'Em Andamento'
            ORDER BY c.criado_em DESC
        ");
        $stmt->execute([$tecnicoId]);
        return $stmt->fetchAll();
    }

    public function listarDisponiveisPorCategoria(int $tecnicoId, string $genero = 'Masculino'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome, cl.endereco AS cliente_endereco,
                   cl.telefone AS cliente_telefone, cl.foto_perfil AS cliente_foto,
                   (c.tecnico_id = ?) AS solicitacao_direta
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.status = 'Pendente'
              AND (
                    (c.tecnico_id IS NULL
                     AND c.categoria IN (
                       SELECT cat.nome FROM servico s
                       INNER JOIN categoria cat ON cat.id = s.categoria_id
                       WHERE s.tecnico_id = ? AND s.ativo = 1
                     )
                    )
                    OR c.tecnico_id = ?
              )
              AND (COALESCE(c.prest_feminino, 0) = 0 OR (c.prest_feminino = 1 AND ? = 'Feminino'))
              AND NOT EXISTS (SELECT 1 FROM orcamento o WHERE o.chamado_id = c.id AND o.tecnico_id = ?)
            ORDER BY solicitacao_direta DESC, c.criado_em ASC
        ");
        $stmt->execute([$tecnicoId, $tecnicoId, $tecnicoId, $genero, $tecnicoId]);
        return $stmt->fetchAll();
    }

    public function listarSolicitacoesDiretas(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome, cl.telefone AS cliente_telefone,
                   cl.endereco AS cliente_endereco, cl.genero AS cliente_genero,
                   cl.foto_perfil AS cliente_foto
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.tecnico_id = ? AND c.status = 'Pendente'
            ORDER BY c.criado_em DESC
        ");
        $stmt->execute([$tecnicoId]);
        return $stmt->fetchAll();
    }

    public function listarHistoricoPorTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome, cl.foto_perfil AS cliente_foto,
                   cl.telefone AS cliente_telefone,
                   p.status AS pag_status, p.valor AS pag_valor, p.metodo AS pag_metodo,
                   p.pago_em,
                   a.nota AS avaliacao_nota
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            LEFT JOIN avaliacao a ON a.chamado_id = c.id
            WHERE c.tecnico_id = ? AND c.status IN ('Concluído','Negado')
            ORDER BY c.atualizado_em DESC
            LIMIT 50
        ");
        $stmt->execute([$tecnicoId]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?ChamadoDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, t.nome AS tecnico_nome, COALESCE(t.foto_perfil,'') AS tecnico_foto,
                   cl.nome AS cliente_nome, cl.telefone AS cliente_telefone,
                   cl.genero AS cliente_genero, COALESCE(cl.foto_perfil,'') AS cliente_foto
            FROM chamado c
            LEFT JOIN tecnico t ON t.id = c.tecnico_id
            LEFT JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ChamadoDTO::fromArray($row) : null;
    }

    public function inserir(array $data): int
    {
        $primeiraFoto = !empty($data['fotos'][0]) ? $data['fotos'][0] : null;
        $stmt = $this->pdo->prepare('
            INSERT INTO chamado
              (cliente_id, tecnico_id, categoria, descricao, endereco_servico,
               lat_servico, lng_servico, data_agendamento, prest_feminino, foto_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['cliente_id'], $data['tecnico_id'] ?? null, $data['categoria'],
            $data['descricao'], $data['endereco_servico'],
            $data['lat_servico'] ?? null, $data['lng_servico'] ?? null,
            $data['data_agendamento'] ?? null, $data['prest_feminino'] ?? 0,
            $primeiraFoto,
        ]);
        $chamadoId = (int)$this->pdo->lastInsertId();

        if (!empty($data['fotos']) && is_array($data['fotos'])) {
            $ins = $this->pdo->prepare('INSERT INTO chamado_foto (chamado_id, foto_path) VALUES (?, ?)');
            foreach ($data['fotos'] as $path) {
                if ($path) $ins->execute([$chamadoId, $path]);
            }
        }
        return $chamadoId;
    }

    public function listarFotos(int $chamadoId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM chamado_foto WHERE chamado_id=? ORDER BY criado_em ASC');
        $stmt->execute([$chamadoId]);
        return $stmt->fetchAll();
    }

    public function aceitarPelaTecnico(int $id, int $tecnicoId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE chamado SET status='Em Andamento', tecnico_id=?
            WHERE id=? AND status='Pendente'
        ");
        $stmt->execute([$tecnicoId, $id]);
        return $stmt->rowCount() > 0;
    }

    public function recusarPelaTecnico(int $id, int $tecnicoId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE chamado SET status='Negado'
            WHERE id=? AND tecnico_id=? AND status='Pendente'
        ");
        $stmt->execute([$id, $tecnicoId]);
        return $stmt->rowCount() > 0;
    }

    public function concluir(int $id, int $tecnicoId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE chamado SET status='Concluído'
            WHERE id=? AND tecnico_id=? AND status='Em Andamento'
        ");
        $stmt->execute([$id, $tecnicoId]);
        return $stmt->rowCount() > 0;
    }

    public function cancelar(int $id, int $clienteId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE chamado SET status='Negado'
            WHERE id=? AND cliente_id=? AND status='Pendente'
        ");
        $stmt->execute([$id, $clienteId]);
        return $stmt->rowCount() > 0;
    }

    public function podeAcessarChat(int $chamadoId, int $usuarioId, bool $isPrestador): bool
    {
        if ($isPrestador) {
            $stmt = $this->pdo->prepare("
                SELECT id FROM chamado c
                WHERE c.id = ?
                  AND (
                    c.tecnico_id = ?
                    OR EXISTS (SELECT 1 FROM orcamento o WHERE o.chamado_id = c.id AND o.tecnico_id = ?)
                    OR (
                      c.status = 'Pendente' AND c.tecnico_id IS NULL
                      AND c.categoria IN (
                        SELECT cat.nome FROM servico s
                        INNER JOIN categoria cat ON cat.id = s.categoria_id
                        WHERE s.tecnico_id = ? AND s.ativo = 1
                      )
                    )
                  )
            ");
            $stmt->execute([$chamadoId, $usuarioId, $usuarioId, $usuarioId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id FROM chamado WHERE id=? AND cliente_id=?');
            $stmt->execute([$chamadoId, $usuarioId]);
        }
        return (bool)$stmt->fetch();
    }

    public function estatisticasCliente(int $clienteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total_chamados,
                   SUM(CASE WHEN status='Concluído'    THEN 1 ELSE 0 END) AS concluidos,
                   SUM(CASE WHEN status='Em Andamento' THEN 1 ELSE 0 END) AS em_andamento
            FROM chamado WHERE cliente_id=?
        ");
        $stmt->execute([$clienteId]);
        return $stmt->fetch() ?: ['total_chamados' => 0, 'concluidos' => 0, 'em_andamento' => 0];
    }

    public function estatisticasTecnico(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
              SUM(CASE WHEN status='Em Andamento' THEN 1 ELSE 0 END) AS em_andamento,
              SUM(CASE WHEN status='Concluído'    THEN 1 ELSE 0 END) AS concluidos,
              SUM(CASE WHEN status='Pendente' AND tecnico_id IS NULL THEN 1 ELSE 0 END) AS disponiveis
            FROM chamado
            WHERE tecnico_id=? OR (
              status='Pendente' AND tecnico_id IS NULL AND categoria IN (
                SELECT cat.nome FROM servico s
                INNER JOIN categoria cat ON cat.id = s.categoria_id
                WHERE s.tecnico_id=? AND s.ativo=1
              )
            )
        ");
        $stmt->execute([$tecnicoId, $tecnicoId]);
        return $stmt->fetch() ?: ['em_andamento' => 0, 'concluidos' => 0, 'disponiveis' => 0];
    }

    public function listarHistoricoResumido(string $tipo, int $id, int $limit = 30): array
    {
        if ($tipo === 'prestador') {
            $stmt = $this->pdo->prepare('
                SELECT c.id, c.status, c.categoria, c.criado_em,
                       cl.nome AS outra_parte
                FROM chamado c
                INNER JOIN cliente cl ON cl.id = c.cliente_id
                WHERE c.tecnico_id = ?
                ORDER BY c.criado_em DESC LIMIT ' . $limit
            );
        } else {
            $stmt = $this->pdo->prepare('
                SELECT c.id, c.status, c.categoria, c.criado_em,
                       COALESCE(t.nome, \'A definir\') AS outra_parte
                FROM chamado c
                LEFT JOIN tecnico t ON t.id = c.tecnico_id
                WHERE c.cliente_id = ?
                ORDER BY c.criado_em DESC LIMIT ' . $limit
            );
        }
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function estatisticasGerais(): array
    {
        return $this->pdo->query("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN status='Pendente'     THEN 1 ELSE 0 END) AS pendentes,
                   SUM(CASE WHEN status='Em Andamento' THEN 1 ELSE 0 END) AS em_andamento,
                   SUM(CASE WHEN status='Concluído'    THEN 1 ELSE 0 END) AS concluidos
            FROM chamado
        ")->fetch() ?: ['total' => 0, 'pendentes' => 0, 'em_andamento' => 0, 'concluidos' => 0];
    }

    public function alterarDescricaoEndereco(int $clienteId, int $id, string $descricao, string $endereco): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE chamado SET descricao=?, endereco_servico=?
            WHERE id=? AND cliente_id=? AND status='Pendente'
        ");
        $stmt->execute([$descricao, $endereco, $id, $clienteId]);
        return $stmt->rowCount() > 0;
    }

    public function reagendarPorCliente(int $clienteId, int $id, string $novaData): array
    {
        $stmt = $this->pdo->prepare("
            SELECT tecnico_id FROM chamado
            WHERE id=? AND cliente_id=? AND status IN ('Pendente','Aguardando Orçamento','Em Andamento')
        ");
        $stmt->execute([$id, $clienteId]);
        $row = $stmt->fetch();
        if ($row === false) return ['ok' => false, 'tecnico_id' => null];

        $up = $this->pdo->prepare("
            UPDATE chamado SET data_agendamento=?, data_agendamento_proposta=NULL, reagendamento_pendente=0
            WHERE id=? AND cliente_id=? AND status IN ('Pendente','Aguardando Orçamento','Em Andamento')
        ");
        $up->execute([$novaData, $id, $clienteId]);
        if ($up->rowCount() === 0) return ['ok' => false, 'tecnico_id' => null];

        return ['ok' => true, 'tecnico_id' => $row['tecnico_id'] !== null ? (int)$row['tecnico_id'] : null];
    }

    public function reagendarDireto(int $clienteId, int $id, string $novaData): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE chamado SET data_agendamento=?
            WHERE id=? AND cliente_id=? AND status IN ('Pendente','Em Andamento')
        ");
        $stmt->execute([$novaData, $id, $clienteId]);
        return $stmt->rowCount() > 0;
    }

    public function aceitarDaFila(int $tecnicoId, int $id, string $genero): ?int
    {
        $up = $this->pdo->prepare("
            UPDATE chamado
            SET tecnico_id = ?, status = 'Aguardando Orçamento'
            WHERE id = ?
              AND status = 'Pendente'
              AND tecnico_id IS NULL
              AND categoria IN (
                SELECT cat.nome FROM servico s
                INNER JOIN categoria cat ON cat.id = s.categoria_id
                WHERE s.tecnico_id = ? AND s.ativo = 1
              )
              AND (COALESCE(prest_feminino, 0) = 0
                   OR (prest_feminino = 1 AND ? = 'Feminino'))
        ");
        $up->execute([$tecnicoId, $id, $tecnicoId, $genero]);
        if ($up->rowCount() === 0) return null;

        $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['cliente_id'] : null;
    }

    public function negarDaFila(int $id): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT cliente_id FROM chamado WHERE id = ? AND status = 'Pendente' AND tecnico_id IS NULL"
        );
        $stmt->execute([$id]);
        $info = $stmt->fetch();
        if (!$info) return null;

        $up = $this->pdo->prepare(
            "UPDATE chamado SET status = 'Negado' WHERE id = ? AND status = 'Pendente' AND tecnico_id IS NULL"
        );
        $up->execute([$id]);
        return $up->rowCount() > 0 ? (int)$info['cliente_id'] : null;
    }

    public function aceitarDireto(int $tecnicoId, int $id): ?int
    {
        $up = $this->pdo->prepare(
            "UPDATE chamado SET status = 'Aguardando Orçamento' WHERE id = ? AND tecnico_id = ? AND status = 'Pendente'"
        );
        $up->execute([$id, $tecnicoId]);
        if ($up->rowCount() === 0) return null;

        $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['cliente_id'] : null;
    }

    public function recusarDireto(int $tecnicoId, int $id): ?int
    {
        $up = $this->pdo->prepare(
            "UPDATE chamado SET tecnico_id = NULL WHERE id = ? AND tecnico_id = ? AND status = 'Pendente'"
        );
        $up->execute([$id, $tecnicoId]);
        if ($up->rowCount() === 0) return null;

        $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['cliente_id'] : null;
    }

    public function aceitarReagendamentoProposta(int $tecnicoId, int $id): ?array
    {
        $up = $this->pdo->prepare("
            UPDATE chamado
            SET data_agendamento = data_agendamento_proposta,
                data_agendamento_proposta = NULL,
                reagendamento_pendente = 0
            WHERE id = ? AND tecnico_id = ? AND reagendamento_pendente = 1
        ");
        $up->execute([$id, $tecnicoId]);
        if ($up->rowCount() === 0) return null;

        $stmt = $this->pdo->prepare('SELECT cliente_id, data_agendamento FROM chamado WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function recusarReagendamentoProposta(int $tecnicoId, int $id): ?int
    {
        $up = $this->pdo->prepare("
            UPDATE chamado
            SET data_agendamento_proposta = NULL, reagendamento_pendente = 0
            WHERE id = ? AND tecnico_id = ? AND reagendamento_pendente = 1
        ");
        $up->execute([$id, $tecnicoId]);
        if ($up->rowCount() === 0) return null;

        $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['cliente_id'] : null;
    }

    public function alterarStatusComPagamento(int $tecnicoId, int $id, string $novoStatus): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmtOld = $this->pdo->prepare('SELECT cliente_id, status FROM chamado WHERE id = ? AND tecnico_id = ?');
            $stmtOld->execute([$id, $tecnicoId]);
            $oldRow = $stmtOld->fetch();

            $up = $this->pdo->prepare('UPDATE chamado SET status = ? WHERE id = ? AND tecnico_id = ?');
            $up->execute([$novoStatus, $id, $tecnicoId]);

            if ($up->rowCount() === 0) {
                $this->pdo->rollBack();
                return ['ok' => false, 'cliente_id' => null, 'old_status' => ''];
            }

            if ($novoStatus === 'Concluído') {
                $stmtPag = $this->pdo->prepare("SELECT id FROM pagamento WHERE chamado_id = ? LIMIT 1");
                $stmtPag->execute([$id]);
                if (!$stmtPag->fetch()) {
                    $this->pdo->prepare("
                        INSERT INTO pagamento (chamado_id, metodo, valor, status)
                        SELECT id, 'PIX', preco_sugerido, 'Pendente' FROM chamado WHERE id = ?
                    ")->execute([$id]);
                }
            }

            $this->pdo->commit();
            return [
                'ok'         => true,
                'cliente_id' => $oldRow ? (int)$oldRow['cliente_id'] : null,
                'old_status' => $oldRow ? (string)$oldRow['status'] : '',
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['ok' => false, 'cliente_id' => null, 'old_status' => ''];
        }
    }

    public function listarAguardandoOrcamento(int $tecnicoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome, cl.telefone AS cliente_telefone,
                   cl.foto_perfil AS cliente_foto, cl.endereco AS cliente_endereco
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.tecnico_id = ? AND c.status = 'Aguardando Orçamento'
              AND NOT EXISTS (
                SELECT 1 FROM orcamento o WHERE o.chamado_id = c.id AND o.tecnico_id = ? AND o.status = 'Pendente'
              )
            ORDER BY c.criado_em DESC
        ");
        $stmt->execute([$tecnicoId, $tecnicoId]);
        return $stmt->fetchAll();
    }

    public function listarParaCalendario(int $clienteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.descricao, c.categoria, c.status,
                   c.data_agendamento, c.criado_em,
                   t.nome AS tecnico_nome
            FROM chamado c
            LEFT JOIN tecnico t ON t.id = c.tecnico_id
            WHERE c.cliente_id = ?
            ORDER BY c.data_agendamento ASC, c.criado_em ASC
        ");
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll();
    }

    public function buscarSlotsOcupadosFuturos(int $tecnicoId, int $dias = 28): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE(data_agendamento) AS data,
                   TIME_FORMAT(data_agendamento,'%H:%i') AS hora
            FROM chamado
            WHERE tecnico_id = ?
              AND status IN ('Pendente','Em Andamento')
              AND data_agendamento >= NOW()
              AND data_agendamento <= DATE_ADD(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$tecnicoId, $dias]);

        $result = [];
        foreach ($stmt->fetchAll() as $c) {
            $result[$c['data']][$c['hora']] = true;
        }
        return $result;
    }

    public function buscarParaRastreamento(int $chamadoId, int $clienteId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.status, c.categoria, c.descricao, c.foto_path,
                   c.em_deslocamento, c.tecnico_lat, c.tecnico_lng,
                   c.endereco_servico, c.lat_servico, c.lng_servico,
                   cl.nome AS cliente_nome, cl.foto_perfil AS cliente_foto,
                   t.nome AS tecnico_nome, t.foto_perfil AS tecnico_foto
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN tecnico t ON t.id = c.tecnico_id
            WHERE c.id = ? AND c.cliente_id = ?
        ");
        $stmt->execute([$chamadoId, $clienteId]);
        return $stmt->fetch() ?: null;
    }

    public function buscarUltimoPorClienteParaRastreamento(int $clienteId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.status, c.categoria, c.descricao, c.foto_path,
                   c.em_deslocamento, c.tecnico_lat, c.tecnico_lng,
                   c.endereco_servico, c.lat_servico, c.lng_servico,
                   cl.nome AS cliente_nome, cl.foto_perfil AS cliente_foto,
                   t.nome AS tecnico_nome, t.foto_perfil AS tecnico_foto
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN tecnico t ON t.id = c.tecnico_id
            WHERE c.cliente_id = ?
            ORDER BY c.criado_em DESC LIMIT 1
        ");
        $stmt->execute([$clienteId]);
        return $stmt->fetch() ?: null;
    }

    public function slotEstaReservado(int $tecnicoId, string $data, string $hora): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM chamado
            WHERE tecnico_id=? AND data_agendamento=? AND status IN ('Pendente','Em Andamento')
        ");
        $stmt->execute([$tecnicoId, $data . ' ' . $hora . ':00']);
        return (bool)$stmt->fetch();
    }

    public function listarAgendadosPorDatas(int $tecnicoId, array $datas): array
    {
        $ph   = implode(',', array_fill(0, count($datas), '?'));
        $stmt = $this->pdo->prepare("
            SELECT DATE(c.data_agendamento) AS data,
                   TIME_FORMAT(c.data_agendamento,'%H:%i') AS hora,
                   c.id, c.categoria,
                   cl.nome AS cliente_nome
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.tecnico_id = ?
              AND c.status IN ('Pendente','Em Andamento')
              AND DATE(c.data_agendamento) IN ($ph)
        ");
        $stmt->execute(array_merge([$tecnicoId], $datas));
        return $stmt->fetchAll();
    }
}
