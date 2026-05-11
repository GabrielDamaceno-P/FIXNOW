<?php

require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class AdminControl
{
    private ClienteDAO   $clienteDAO;
    private TecnicoDAO   $tecnicoDAO;
    private ChamadoDAO   $chamadoDAO;
    private CategoriaDAO $categoriaDAO;
    private PDO          $pdo;

    public string $adminPerfil  = 'Master';
    public int    $adminId      = 0;
    public int    $naoLidas     = 0;
    public string $mensagem     = '';
    public string $erro         = '';
    public array  $statsGerais  = [];
    public float  $lucroEmpresa = 0.0;
    public array  $prestadoresPendentes = [];
    public array  $clientes     = [];
    public array  $prestadores  = [];
    public array  $categorias   = [];
    public array  $tickets      = [];
    public array  $chamados     = [];
    public array  $pagamentos   = [];
    public array  $servicos     = [];

    public function __construct()
    {
        $this->clienteDAO   = new ClienteDAO();
        $this->tecnicoDAO   = new TecnicoDAO();
        $this->chamadoDAO   = new ChamadoDAO();
        $this->categoriaDAO = new CategoriaDAO();
        $this->pdo          = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->adminId     = (int)$_SESSION['admin_id'];
        $this->adminPerfil = $_SESSION['admin_perfil'] ?? 'Master';
    }

    public function processarPainel(): void
    {
        $this->verificarSessao();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao      = $_POST['acao'] ?? '';
            $isMaster  = $this->adminPerfil === 'Master';
            $isOps     = in_array($this->adminPerfil, ['Master', 'Operacoes'], true);
            $isFinanc  = in_array($this->adminPerfil, ['Master', 'Financeiro'], true);

            if ($acao === 'aprovar_prestador' && $isOps) {
                $tid = (int)($_POST['tecnico_id'] ?? 0);
                if ($tid > 0) {
                    $up = $this->pdo->prepare("UPDATE tecnico SET ativo=1, status_cadastro='Aprovado' WHERE id=? AND status_cadastro='Pendente'");
                    $up->execute([$tid]);
                    if ($up->rowCount() > 0) {
                        fixnow_notificar_prestador($this->pdo, $tid,
                            'Seu cadastro de prestador foi aprovado! Você já pode acessar o painel e aceitar chamados.');
                    }
                    $this->mensagem = 'Prestador aprovado.';
                }
            } elseif ($acao === 'recusar_prestador' && $isOps) {
                $tid = (int)($_POST['tecnico_id'] ?? 0);
                if ($tid > 0) {
                    $up = $this->pdo->prepare("UPDATE tecnico SET ativo=0, status_cadastro='Recusado' WHERE id=? AND status_cadastro='Pendente'");
                    $up->execute([$tid]);
                    if ($up->rowCount() > 0) {
                        fixnow_notificar_prestador($this->pdo, $tid,
                            'Seu cadastro de prestador foi recusado. Entre em contato com o suporte para mais informações.');
                    }
                    $this->mensagem = 'Prestador recusado.';
                }
            } elseif ($acao === 'negar_chamado' && $isOps) {
                $cid = (int)($_POST['chamado_id'] ?? 0);
                if ($cid > 0) {
                    $row = $this->pdo->prepare("SELECT cliente_id FROM chamado WHERE id=? LIMIT 1");
                    $row->execute([$cid]);
                    $r = $row->fetch();
                    $up = $this->pdo->prepare("UPDATE chamado SET status='Negado', tecnico_id=NULL WHERE id=? AND status IN ('Pendente','Em Andamento')");
                    $up->execute([$cid]);
                    if ($up->rowCount() > 0 && $r) {
                        fixnow_notificar_cliente($this->pdo, (int)$r['cliente_id'],
                            "Seu chamado #{$cid} foi negado pela equipe Fix Now.", $cid);
                        $this->mensagem = 'Chamado negado e cliente notificado.';
                    } else {
                        $this->erro = 'Não foi possível negar este chamado.';
                    }
                }
            } elseif ($acao === 'alterar_status_chamado' && $isOps) {
                $cid    = (int)($_POST['chamado_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $perm   = ['Pendente','Em Andamento','Concluído','Negado'];
                if ($cid > 0 && in_array($status, $perm, true)) {
                    $prev = $this->pdo->prepare("SELECT cliente_id, status FROM chamado WHERE id=?");
                    $prev->execute([$cid]);
                    $old = $prev->fetch();
                    $this->pdo->prepare("UPDATE chamado SET status=? WHERE id=?")->execute([$status, $cid]);
                    if ($status === 'Negado' && $old && $old['status'] !== 'Negado') {
                        fixnow_notificar_cliente($this->pdo, (int)$old['cliente_id'],
                            "Seu chamado #{$cid} foi atualizado para Negado.", $cid);
                    }
                    $this->mensagem = 'Status do chamado atualizado.';
                }
            } elseif ($acao === 'excluir_servico' && $isMaster) {
                $sid = (int)($_POST['servico_id'] ?? 0);
                if ($sid > 0) {
                    $this->pdo->prepare("DELETE FROM servico WHERE id=?")->execute([$sid]);
                    $this->mensagem = 'Serviço removido.';
                }
            } elseif ($acao === 'excluir_cliente' && $isMaster) {
                $cid = (int)($_POST['cliente_id'] ?? 0);
                if ($cid > 0) {
                    $this->pdo->prepare('DELETE FROM cliente WHERE id=? AND is_admin=0')->execute([$cid]);
                    $this->mensagem = 'Cliente removido.';
                }
            } elseif ($acao === 'excluir_prestador' && $isMaster) {
                $tid = (int)($_POST['tecnico_id'] ?? 0);
                if ($tid > 0) {
                    $this->pdo->prepare('DELETE FROM tecnico WHERE id=?')->execute([$tid]);
                    $this->mensagem = 'Prestador removido.';
                }
            } elseif ($acao === 'salvar_categoria') {
                $nome      = trim($_POST['nome']      ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $ativo     = (int)($_POST['ativo']    ?? 1);
                $cid       = (int)($_POST['cat_id']   ?? 0);
                if (!$nome) { $this->erro = 'Nome da categoria é obrigatório.'; }
                elseif ($this->categoriaDAO->nomeExiste($nome, $cid)) { $this->erro = 'Nome já existe.'; }
                else {
                    if ($cid > 0) { $this->categoriaDAO->atualizar($cid, $nome, $descricao, $ativo); $this->mensagem = 'Categoria atualizada.'; }
                    else { $this->categoriaDAO->inserir($nome, $descricao); $this->mensagem = 'Categoria criada.'; }
                }
            } elseif ($acao === 'excluir_categoria' && $isMaster) {
                $cid = (int)($_POST['cat_id'] ?? 0);
                if ($cid > 0) {
                    $this->categoriaDAO->excluir($cid);
                    $this->mensagem = 'Categoria removida.';
                }
            } elseif ($acao === 'responder_ticket') {
                $tid     = (int)($_POST['ticket_id'] ?? 0);
                $resp    = trim($_POST['resposta']   ?? '');
                $status  = $_POST['ticket_status']   ?? 'Fechado';
                if ($tid > 0 && $resp) {
                    $this->pdo->prepare("
                        UPDATE suporte SET resposta=?, status=?, respondido_por=? WHERE id=?
                    ")->execute([$resp, $status, $this->adminId, $tid]);
                    $this->mensagem = 'Resposta enviada.';
                }
            }
        }

        $this->carregarDados();
    }

    private function carregarDados(): void
    {
        // Comissão: destaque = 15%, demais = 20%
        $lucro = $this->pdo->query("
            SELECT COALESCE(SUM(
                p.valor * CASE WHEN t.destaque = 1 THEN 0.15 ELSE 0.20 END
            ), 0)
            FROM pagamento p
            INNER JOIN chamado c  ON c.id = p.chamado_id
            INNER JOIN tecnico t  ON t.id = c.tecnico_id
            WHERE p.status = 'Pago'
        ")->fetchColumn();
        $totPagos = (float)($this->pdo->query("SELECT COALESCE(SUM(valor),0) FROM pagamento WHERE status='Pago'")->fetchColumn() ?? 0);
        $this->lucroEmpresa = (float)($lucro ?? 0);

        $this->statsGerais = [
            'clientes'          => (int)$this->pdo->query("SELECT COUNT(*) FROM cliente WHERE is_admin=0")->fetchColumn(),
            'prestadores'       => (int)$this->pdo->query("SELECT COUNT(*) FROM tecnico")->fetchColumn(),
            'prestadores_ativos'=> (int)$this->pdo->query("SELECT COUNT(*) FROM tecnico WHERE ativo=1")->fetchColumn(),
            'chamados'          => $this->chamadoDAO->estatisticasGerais(),
            'faturamento'       => $totPagos,
            'lucro'             => $this->lucroEmpresa,
            'avaliacao'         => (float)($this->pdo->query("SELECT COALESCE(ROUND(AVG(nota),1),0) FROM avaliacao")->fetchColumn() ?? 0),
        ];

        $this->prestadoresPendentes = $this->pdo->query("
            SELECT * FROM tecnico WHERE status_cadastro='Pendente' ORDER BY criado_em ASC
        ")->fetchAll();

        $this->clientes = $this->pdo->query("
            SELECT id, nome, email, telefone, genero, endereco, cep, criado_em
            FROM cliente WHERE is_admin=0 ORDER BY criado_em DESC LIMIT 200
        ")->fetchAll();

        $this->prestadores = $this->pdo->query("
            SELECT id, nome, email, especialidade, telefone, genero, avaliacao_media, ativo, status_cadastro, criado_em
            FROM tecnico ORDER BY criado_em DESC LIMIT 200
        ")->fetchAll();

        $this->categorias = $this->categoriaDAO->listarTodas();

        $this->tickets = $this->pdo->query("
            SELECT * FROM suporte ORDER BY criado_em DESC LIMIT 50
        ")->fetchAll();

        $isMaster = $this->adminPerfil === 'Master';
        $isOps    = in_array($this->adminPerfil, ['Master', 'Operacoes'], true);
        $isFinanc = in_array($this->adminPerfil, ['Master', 'Financeiro'], true);

        if ($isOps) {
            $this->chamados = $this->pdo->query("
                SELECT c.id, c.categoria, c.status, c.preco_sugerido, c.criado_em, c.prest_feminino,
                       cl.nome AS cliente_nome, t.nome AS tecnico_nome
                FROM chamado c
                INNER JOIN cliente cl ON cl.id = c.cliente_id
                LEFT JOIN tecnico t ON t.id = c.tecnico_id
                ORDER BY c.criado_em DESC
                LIMIT 200
            ")->fetchAll();

            $this->servicos = $this->pdo->query("
                SELECT s.id, s.nome, s.preco, s.ativo, t.nome AS tecnico_nome, c.nome AS categoria_nome
                FROM servico s
                INNER JOIN tecnico t ON t.id = s.tecnico_id
                LEFT JOIN categoria c ON c.id = s.categoria_id
                ORDER BY t.nome ASC, s.nome ASC
                LIMIT 300
            ")->fetchAll();
        }

        if ($isFinanc) {
            $this->pagamentos = $this->pdo->query("
                SELECT p.id, p.valor, p.status, p.metodo, p.pago_em,
                       c.id AS chamado_id, cl.nome AS cliente_nome
                FROM pagamento p
                INNER JOIN chamado c ON c.id = p.chamado_id
                INNER JOIN cliente cl ON cl.id = c.cliente_id
                ORDER BY p.criado_em DESC
                LIMIT 200
            ")->fetchAll();
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM notificacao WHERE tipo_destinatario = 'admin' AND lida = 0");
        $this->naoLidas = $stmt ? (int)$stmt->fetchColumn() : 0;
    }
}
