<?php

require_once __DIR__ . '/../model/dao/Conexao.php';

class RastreamentoChamadoControl
{
    private PDO $pdo;

    public int    $clienteId   = 0;
    public ?array $chamado     = null;
    public string $tecnicoNome = 'Prestador';
    public string $clienteNome = 'Cliente';

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function processar(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: login.php'); exit;
        }
        $this->clienteId = (int)$_SESSION['cliente_id'];
        $chamadoId = (int)($_GET['chamado'] ?? 0);

        if ($chamadoId > 0) {
            $stmt = $this->pdo->prepare('
                SELECT c.id, c.status, c.categoria, c.descricao, c.foto_path,
                       cl.nome AS cliente_nome, cl.foto_perfil AS cliente_foto,
                       t.nome AS tecnico_nome, t.foto_perfil AS tecnico_foto
                FROM chamado c
                INNER JOIN cliente cl ON cl.id = c.cliente_id
                LEFT  JOIN tecnico  t ON t.id  = c.tecnico_id
                WHERE c.id = ? AND c.cliente_id = ?
            ');
            $stmt->execute([$chamadoId, $this->clienteId]);
        } else {
            $stmt = $this->pdo->prepare('
                SELECT c.id, c.status, c.categoria, c.descricao, c.foto_path,
                       cl.nome AS cliente_nome, cl.foto_perfil AS cliente_foto,
                       t.nome AS tecnico_nome, t.foto_perfil AS tecnico_foto
                FROM chamado c
                INNER JOIN cliente cl ON cl.id = c.cliente_id
                LEFT  JOIN tecnico  t ON t.id  = c.tecnico_id
                WHERE c.cliente_id = ?
                ORDER BY c.criado_em DESC LIMIT 1
            ');
            $stmt->execute([$this->clienteId]);
        }

        $this->chamado     = $stmt->fetch() ?: null;
        $this->tecnicoNome = $this->chamado['tecnico_nome'] ?? 'Prestador';
        $this->clienteNome = $this->chamado['cliente_nome'] ?? 'Cliente';
    }
}
