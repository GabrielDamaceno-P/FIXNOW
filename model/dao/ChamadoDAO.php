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

    /** @return ChamadoDTO[] */
    public function listarPorCliente(int $clienteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, t.nome AS tecnico_nome,
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

    /** @return array — chamados em andamento do técnico */
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

    /** @return array — chamados pendentes na especialidade do técnico (abertos + solicitações diretas) */
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

    /** @return array — solicitações diretas ao técnico */
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

    /** @return array — histórico concluídos/negados do técnico */
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
        $stmt = $this->pdo->prepare('
            INSERT INTO chamado
              (cliente_id, tecnico_id, categoria, descricao, endereco_servico,
               data_agendamento, prest_feminino)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['cliente_id'], $data['tecnico_id'] ?? null, $data['categoria'],
            $data['descricao'], $data['endereco_servico'],
            $data['data_agendamento'] ?? null, $data['prest_feminino'] ?? 0,
        ]);
        $chamadoId = (int)$this->pdo->lastInsertId();

        // Insere fotos múltiplas se fornecidas
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
}
