<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/AdminDTO.php';

class AdminDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function buscarPorEmail(string $email): ?AdminDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? AdminDTO::fromArray($row) : null;
    }

    public function buscarPorId(int $id): ?AdminDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? AdminDTO::fromArray($row) : null;
    }

    public function listar(): array
    {
        return $this->pdo->query('SELECT * FROM admin ORDER BY nome ASC')->fetchAll();
    }

    public function inserir(string $nome, string $email, string $senhaHash, string $genero): bool
    {
        try {
            $this->pdo->prepare('
                INSERT INTO admin (nome, email, senha, genero)
                VALUES (?, ?, ?, ?)
            ')->execute([$nome, $email, $senhaHash, $genero]);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function atualizar(int $id, string $nome, string $email, string $genero): bool
    {
        try {
            $this->pdo->prepare('
                UPDATE admin SET nome=?, email=?, genero=? WHERE id=?
            ')->execute([$nome, $email, $genero, $id]);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $this->pdo->prepare('UPDATE admin SET senha = ? WHERE id = ?')
            ->execute([$senhaHash, $id]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    

    public function calcularLucroEmpresa(): float
    {
        $val = $this->pdo->query("
            SELECT COALESCE(SUM(p.valor * CASE WHEN t.destaque = 1 THEN 0.15 ELSE 0.20 END), 0)
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            INNER JOIN tecnico t ON t.id = c.tecnico_id
            WHERE p.status = 'Pago'
        ")->fetchColumn();
        return (float)($val ?? 0);
    }

    public function calcularTotalPagos(): float
    {
        $val = $this->pdo->query("SELECT COALESCE(SUM(valor),0) FROM pagamento WHERE status='Pago'")->fetchColumn();
        return (float)($val ?? 0);
    }

    public function calcularTotalEstornado(): float
    {
        $val = $this->pdo->query("SELECT COALESCE(SUM(valor),0) FROM pagamento WHERE status='Estornado'")->fetchColumn();
        return (float)($val ?? 0);
    }

    public function calcularLucroEstornado(): float
    {
        $val = $this->pdo->query("
            SELECT COALESCE(SUM(p.valor * CASE WHEN t.destaque = 1 THEN 0.15 ELSE 0.20 END), 0)
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            INNER JOIN tecnico t ON t.id = c.tecnico_id
            WHERE p.status = 'Estornado'
        ")->fetchColumn();
        return (float)($val ?? 0);
    }

    public function calcularMediaAvaliacoes(): float
    {
        $val = $this->pdo->query("SELECT COALESCE(ROUND(AVG(nota),1),0) FROM avaliacao")->fetchColumn();
        return (float)($val ?? 0);
    }


    public function contarClientes(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM cliente")->fetchColumn();
    }

    public function contarTecnicos(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM tecnico")->fetchColumn();
    }

    public function contarTecnicosAtivos(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM tecnico WHERE ativo=1")->fetchColumn();
    }

    public function contarChamadosPendentes(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM chamado WHERE status='Pendente'")->fetchColumn();
    }

    public function contarChamadosMes(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM chamado WHERE status='Concluído' AND MONTH(criado_em)=MONTH(NOW()) AND YEAR(criado_em)=YEAR(NOW())"
        )->fetchColumn();
    }

    public function contarNaoLidas(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM notificacao WHERE tipo_destinatario = 'admin' AND lida = 0");
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }


    public function listarPrestadoresPendentes(): array
    {
        return $this->pdo->query(
            "SELECT * FROM tecnico WHERE status_cadastro='Pendente' ORDER BY criado_em ASC"
        )->fetchAll();
    }

    public function listarClientes(int $limit = 200): array
    {
        return $this->pdo->query("
            SELECT id, nome, email, telefone, genero, endereco, cep, criado_em
            FROM cliente ORDER BY criado_em DESC LIMIT {$limit}
        ")->fetchAll();
    }

    public function listarPrestadores(int $limit = 200): array
    {
        return $this->pdo->query("
            SELECT id, nome, email, especialidade, telefone, genero, avaliacao_media, ativo, status_cadastro, criado_em
            FROM tecnico ORDER BY criado_em DESC LIMIT {$limit}
        ")->fetchAll();
    }

    public function listarTickets(int $limit = 50): array
    {
        return $this->pdo->query("SELECT * FROM suporte ORDER BY criado_em DESC LIMIT {$limit}")->fetchAll();
    }

    public function listarChamados(int $limit = 200): array
    {
        return $this->pdo->query("
            SELECT c.id, c.categoria, c.status, c.preco_sugerido, c.criado_em, c.prest_feminino,
                   cl.nome AS cliente_nome, t.nome AS tecnico_nome
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN tecnico t ON t.id = c.tecnico_id
            ORDER BY c.criado_em DESC LIMIT {$limit}
        ")->fetchAll();
    }

    public function listarServicos(int $limit = 300): array
    {
        return $this->pdo->query("
            SELECT s.id, s.nome, s.preco, s.ativo, t.nome AS tecnico_nome, c.nome AS categoria_nome
            FROM servico s
            INNER JOIN tecnico t ON t.id = s.tecnico_id
            LEFT JOIN categoria c ON c.id = s.categoria_id
            ORDER BY t.nome ASC, s.nome ASC LIMIT {$limit}
        ")->fetchAll();
    }

    public function listarPagamentos(int $limit = 200): array
    {
        return $this->pdo->query("
            SELECT p.id, p.valor, p.status, p.metodo, p.pago_em,
                   c.id AS chamado_id, cl.nome AS cliente_nome
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            ORDER BY p.criado_em DESC LIMIT {$limit}
        ")->fetchAll();
    }

    public function faturamentoMensal(int $meses = 6): array
    {
        return $this->pdo->query("
            SELECT DATE_FORMAT(p.pago_em,'%Y-%m') AS mes,
                   DATE_FORMAT(p.pago_em,'%m/%Y') AS mes_label,
                   ROUND(SUM(p.valor),2) AS bruto,
                   ROUND(SUM(p.valor * CASE WHEN t.destaque=1 THEN 0.15 ELSE 0.20 END),2) AS lucro
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            INNER JOIN tecnico t ON t.id = c.tecnico_id
            WHERE p.status='Pago' AND p.pago_em >= DATE_SUB(NOW(), INTERVAL {$meses} MONTH)
            GROUP BY mes, mes_label
            ORDER BY mes ASC
        ")->fetchAll();
    }

    public function topPrestadores(int $limit = 5): array
    {
        return $this->pdo->query("
            SELECT t.id, t.nome, t.especialidade, t.avaliacao_media, t.foto_perfil,
                   COUNT(c.id) AS total_concluidos
            FROM tecnico t
            LEFT JOIN chamado c ON c.tecnico_id = t.id AND c.status = 'Concluído'
            WHERE t.status_cadastro = 'Aprovado'
            GROUP BY t.id
            ORDER BY t.avaliacao_media DESC, total_concluidos DESC
            LIMIT {$limit}
        ")->fetchAll();
    }

    public function atividadeRecente(int $limit = 8): array
    {
        return $this->pdo->query("
            SELECT tipo, descricao, criado_em FROM (
                (SELECT 'cliente'   AS tipo, CONCAT('Novo cliente: ', nome) AS descricao, criado_em FROM cliente ORDER BY criado_em DESC LIMIT 3)
                UNION ALL
                (SELECT 'prestador', CONCAT('Prestador aprovado: ', nome), criado_em FROM tecnico WHERE status_cadastro='Aprovado' ORDER BY criado_em DESC LIMIT 3)
                UNION ALL
                (SELECT 'chamado',   CONCAT('Chamado #', id, ' — ', status), criado_em FROM chamado ORDER BY criado_em DESC LIMIT 3)
                UNION ALL
                (SELECT 'pagamento', CONCAT('Pagamento recebido: R$ ', FORMAT(valor,2)), criado_em FROM pagamento WHERE status='Pago' ORDER BY criado_em DESC LIMIT 3)
            ) ev
            ORDER BY criado_em DESC
            LIMIT {$limit}
        ")->fetchAll();
    }


    public function aprovarPrestador(int $tecnicoId, int $adminId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tecnico SET ativo=1, status_cadastro='Aprovado', admin_id=? WHERE id=? AND status_cadastro='Pendente'"
        );
        $stmt->execute([$adminId, $tecnicoId]);
        return $stmt->rowCount() > 0;
    }

    public function recusarPrestador(int $tecnicoId, int $adminId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tecnico SET ativo=0, status_cadastro='Recusado', admin_id=? WHERE id=? AND status_cadastro='Pendente'"
        );
        $stmt->execute([$adminId, $tecnicoId]);
        return $stmt->rowCount() > 0;
    }

    public function negarChamado(int $chamadoId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT cliente_id FROM chamado WHERE id=? LIMIT 1");
        $stmt->execute([$chamadoId]);
        $row = $stmt->fetch();

        $up = $this->pdo->prepare(
            "UPDATE chamado SET status='Negado', tecnico_id=NULL WHERE id=? AND status IN ('Pendente','Em Andamento')"
        );
        $up->execute([$chamadoId]);
        return ($up->rowCount() > 0 && $row) ? (int)$row['cliente_id'] : null;
    }

    public function alterarStatusChamado(int $chamadoId, string $status): ?array
    {
        $stmt = $this->pdo->prepare("SELECT cliente_id, status FROM chamado WHERE id=?");
        $stmt->execute([$chamadoId]);
        $old = $stmt->fetch();

        $this->pdo->prepare("UPDATE chamado SET status=? WHERE id=?")->execute([$status, $chamadoId]);

        return $old ? ['cliente_id' => (int)$old['cliente_id'], 'old_status' => (string)$old['status']] : null;
    }

    public function excluirServico(int $id): void
    {
        $this->pdo->prepare("DELETE FROM servico WHERE id=?")->execute([$id]);
    }

    public function excluirCliente(int $id): void
    {
        $this->pdo->prepare('DELETE FROM cliente WHERE id=?')->execute([$id]);
    }

    public function excluirPrestador(int $id): void
    {
        $this->pdo->prepare('DELETE FROM tecnico WHERE id=?')->execute([$id]);
    }

    public function responderTicket(int $ticketId, string $resposta, string $status, int $adminId): void
    {
        $this->pdo->prepare("UPDATE suporte SET resposta=?, status=?, admin_id=? WHERE id=?")
            ->execute([$resposta, $status, $adminId, $ticketId]);
    }
}
