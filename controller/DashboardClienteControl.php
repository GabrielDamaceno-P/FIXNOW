<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class DashboardClienteControl
{
    private ChamadoDAO    $chamadoDAO;
    private OrcamentoDAO  $orcamentoDAO;
    private NotificacaoDAO $notifDAO;
    private PDO           $pdo;

    public int    $clienteId         = 0;
    public string $mensagem          = '';
    public string $erro              = '';
    public array  $chamados          = [];
    public array  $stats             = ['total_chamados' => 0, 'concluidos' => 0];
    public array  $orcamentosPendentes = [];
    public array  $notificacoes      = [];
    public array  $servicos          = [];
    public array  $categorias        = [];
    public ?array $depoimento        = null;
    public string $filtroCategoria   = '';
    public int    $naoLidas          = 0;

    public function __construct()
    {
        $this->chamadoDAO   = new ChamadoDAO();
        $this->orcamentoDAO = new OrcamentoDAO();
        $this->notifDAO     = new NotificacaoDAO();
        $this->pdo          = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: login.php'); exit;
        }
        $this->clienteId = (int)$_SESSION['cliente_id'];
    }

    public function processar(): void
    {
        $this->verificarSessao();

        if (isset($_GET['lida'])) {
            $nid = (int)$_GET['lida'];
            if ($nid > 0) $this->notifDAO->marcarLidaCliente($nid, $this->clienteId);
            header('Location: dashboardCliente.php'); exit;
        }

        $this->resolverMensagemGet();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $this->carregarDados();
    }

    private function resolverMensagemGet(): void
    {
        $msgs = [
            'cancelar_ok'        => 'Agendamento cancelado com sucesso.',
            'alterado_ok'        => 'Agendamento atualizado com sucesso.',
            'orcamento_aceito'   => 'Orçamento aceito! O prestador será notificado.',
            'orcamento_recusado' => 'Orçamento recusado.',
            'reagendado'         => 'Proposta de reagendamento enviada. Aguardando confirmação do prestador.',
            'avaliacao_ok'       => 'Avaliação enviada com sucesso. Obrigado pelo feedback!',
            'cadastro_ok'        => 'Cadastro concluído com sucesso. Bem-vindo(a) ao Fix Now!',
            'chamado_ok'         => 'Novo chamado enviado com sucesso. Em breve um técnico vai aceitar.',
        ];
        foreach ($msgs as $key => $msg) {
            if (isset($_GET[$key])) { $this->mensagem = $msg; break; }
        }
    }

    private function processarPost(): void
    {
        if (isset($_POST['cancelar_chamado_id'])) {
            $cid = (int)$_POST['cancelar_chamado_id'];
            if ($cid > 0) {
                $up = $this->pdo->prepare("UPDATE chamado SET status='Negado' WHERE id=? AND cliente_id=? AND status='Pendente'");
                $up->execute([$cid, $this->clienteId]);
                if ($up->rowCount() > 0) {
                    header('Location: dashboardCliente.php?cancelar_ok=1'); exit;
                }
                $this->erro = 'Não foi possível cancelar (o chamado pode já ter sido aceito por um técnico).';
            }

        } elseif (isset($_POST['alterar_chamado_id'])) {
            $cid      = (int)$_POST['alterar_chamado_id'];
            $descricao = trim($_POST['nova_descricao'] ?? '');
            $endereco  = trim($_POST['novo_endereco']  ?? '');
            if ($cid > 0 && $descricao !== '' && $endereco !== '') {
                $up = $this->pdo->prepare("UPDATE chamado SET descricao=?, endereco_servico=? WHERE id=? AND cliente_id=? AND status='Pendente'");
                $up->execute([$descricao, $endereco, $cid, $this->clienteId]);
                if ($up->rowCount() > 0) {
                    header('Location: dashboardCliente.php?alterado_ok=1'); exit;
                }
                $this->erro = 'Não foi possível alterar (o chamado pode já ter sido aceito).';
            }

        } elseif (isset($_POST['aceitar_orcamento_id'])) {
            $oid = (int)$_POST['aceitar_orcamento_id'];
            if ($oid > 0) {
                $stmtO = $this->pdo->prepare("
                    SELECT o.id, o.valor, o.tecnico_id, o.chamado_id
                    FROM orcamento o
                    INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
                    WHERE o.id = ? AND o.status = 'Pendente' LIMIT 1
                ");
                $stmtO->execute([$this->clienteId, $oid]);
                $orc = $stmtO->fetch();
                if ($orc) {
                    $this->pdo->beginTransaction();
                    $this->pdo->prepare("UPDATE orcamento SET status='Aceito' WHERE id=?")->execute([$orc['id']]);
                    $this->pdo->prepare("UPDATE orcamento SET status='Recusado' WHERE chamado_id=? AND id!=?")->execute([$orc['chamado_id'], $orc['id']]);
                    $this->pdo->prepare("UPDATE chamado SET tecnico_id=?, status='Em Andamento', preco_sugerido=? WHERE id=?")->execute([$orc['tecnico_id'], $orc['valor'], $orc['chamado_id']]);
                    $stmtPagExiste = $this->pdo->prepare("SELECT id FROM pagamento WHERE chamado_id=?");
                    $stmtPagExiste->execute([$orc['chamado_id']]);
                    if ($stmtPagExiste->fetch()) {
                        $this->pdo->prepare("UPDATE pagamento SET valor=? WHERE chamado_id=?")->execute([$orc['valor'], $orc['chamado_id']]);
                    } else {
                        $this->pdo->prepare("INSERT INTO pagamento (chamado_id, metodo, valor, status) VALUES (?, 'PIX', ?, 'Pendente')")->execute([$orc['chamado_id'], $orc['valor']]);
                    }
                    $this->pdo->commit();
                    fixnow_notificar_prestador($this->pdo, (int)$orc['tecnico_id'],
                        'Seu orçamento para o chamado #' . $orc['chamado_id'] . ' foi aceito pelo cliente! Prepare-se para o atendimento.', (int)$orc['chamado_id']);
                    header('Location: dashboardCliente.php?orcamento_aceito=1'); exit;
                }
            }

        } elseif (isset($_POST['recusar_orcamento_id'])) {
            $oid    = (int)$_POST['recusar_orcamento_id'];
            $motivo = trim($_POST['motivo_recusa'] ?? '');
            if ($oid > 0) {
                $stmtO = $this->pdo->prepare("
                    SELECT o.id, o.tecnico_id, o.chamado_id FROM orcamento o
                    INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
                    WHERE o.id = ? AND o.status = 'Pendente' LIMIT 1
                ");
                $stmtO->execute([$this->clienteId, $oid]);
                $orcRec = $stmtO->fetch();
                if ($orcRec) {
                    $this->pdo->prepare("UPDATE orcamento SET status='Recusado', motivo_recusa=? WHERE id=?")
                        ->execute([$motivo ?: null, $oid]);
                    $msg = 'Seu orçamento para o chamado #' . $orcRec['chamado_id'] . ' foi recusado pelo cliente.';
                    if ($motivo !== '') $msg .= ' Motivo: ' . $motivo;
                    fixnow_notificar_prestador($this->pdo, (int)$orcRec['tecnico_id'], $msg, (int)$orcRec['chamado_id']);
                    header('Location: dashboardCliente.php?orcamento_recusado=1'); exit;
                }
            }

        } elseif (isset($_POST['reagendar_chamado_id'])) {
            $cid      = (int)$_POST['reagendar_chamado_id'];
            $novaData = trim($_POST['nova_data_agendamento'] ?? '');
            if ($cid <= 0 || $novaData === '') {
                $this->erro = 'Selecione uma data e hora válidas.';
            } elseif (strtotime($novaData) <= time()) {
                $this->erro = 'A data deve ser futura.';
            } else {
                $up = $this->pdo->prepare("UPDATE chamado SET data_agendamento_proposta=?, reagendamento_pendente=1 WHERE id=? AND cliente_id=? AND status IN ('Pendente','Em Andamento') AND reagendamento_pendente=0");
                $up->execute([$novaData, $cid, $this->clienteId]);
                if ($up->rowCount() > 0) {
                    $rowTec = $this->pdo->prepare('SELECT tecnico_id FROM chamado WHERE id=?');
                    $rowTec->execute([$cid]);
                    $tec = $rowTec->fetch();
                    if ($tec && $tec['tecnico_id']) {
                        fixnow_notificar_prestador($this->pdo, (int)$tec['tecnico_id'],
                            'O cliente propôs um novo horário para o chamado #' . $cid . '. Acesse o painel para aceitar ou recusar.', $cid);
                    }
                    header('Location: dashboardCliente.php?reagendado=1'); exit;
                }
                $this->erro = 'Não foi possível reagendar. Pode já haver uma proposta pendente ou o chamado estar finalizado.';
            }

        } elseif (isset($_POST['confirmar_pagamento_id'])) {
            $pid    = (int)$_POST['confirmar_pagamento_id'];
            $metodo = $_POST['metodo_pagamento'] ?? 'PIX';
            if (!in_array($metodo, ['PIX', 'Cartão', 'Dinheiro'], true)) $metodo = 'PIX';
            if ($pid > 0) {
                $up = $this->pdo->prepare("
                    UPDATE pagamento p
                    INNER JOIN chamado c ON c.id = p.chamado_id AND c.cliente_id = ?
                    SET p.status = 'Pago', p.pago_em = NOW(), p.metodo = ?
                    WHERE p.id = ? AND p.status = 'Pendente'
                ");
                $up->execute([$this->clienteId, $metodo, $pid]);
                if ($up->rowCount() > 0) {
                    $this->mensagem = 'Pagamento registrado com sucesso (simulação).';
                } else {
                    $this->erro = 'Não foi possível confirmar este pagamento.';
                }
            }

        } elseif (isset($_POST['avaliar_chamado_id'], $_POST['nota_avaliacao'])) {
            $cid       = (int)$_POST['avaliar_chamado_id'];
            $nota      = (int)$_POST['nota_avaliacao'];
            $comentario = trim($_POST['comentario_avaliacao'] ?? '');
            if ($nota < 1 || $nota > 5) {
                $this->erro = 'Informe uma nota válida entre 1 e 5.';
            } elseif (mb_strlen($comentario) > 255) {
                $this->erro = 'O comentário deve ter no máximo 255 caracteres.';
            } else {
                $stmtC = $this->pdo->prepare("SELECT c.id, c.status, c.tecnico_id, t.nome AS tecnico_nome FROM chamado c LEFT JOIN tecnico t ON t.id=c.tecnico_id WHERE c.id=? AND c.cliente_id=? LIMIT 1");
                $stmtC->execute([$cid, $this->clienteId]);
                $ch = $stmtC->fetch();
                if (!$ch) {
                    $this->erro = 'Chamado não encontrado.';
                } elseif ($ch['status'] !== 'Concluído') {
                    $this->erro = 'A avaliação só pode ser feita após a conclusão do serviço.';
                } elseif (empty($ch['tecnico_id'])) {
                    $this->erro = 'Não existe prestador associado a este chamado.';
                } else {
                    $stmtE = $this->pdo->prepare("SELECT id FROM avaliacao WHERE chamado_id=? LIMIT 1");
                    $stmtE->execute([$cid]);
                    if ($stmtE->fetch()) {
                        $this->erro = 'Este chamado já foi avaliado.';
                    } else {
                        $this->pdo->prepare("INSERT INTO avaliacao (chamado_id, cliente_id, tecnico_id, nota, comentario) VALUES (?,?,?,?,?)")
                            ->execute([$cid, $this->clienteId, (int)$ch['tecnico_id'], $nota, $comentario ?: null]);
                        $this->pdo->prepare("UPDATE tecnico t SET t.avaliacao_media = (SELECT COALESCE(AVG(a.nota),0) FROM avaliacao a WHERE a.tecnico_id = t.id) WHERE t.id=?")
                            ->execute([(int)$ch['tecnico_id']]);
                        header('Location: dashboardCliente.php?avaliacao_ok=1'); exit;
                    }
                }
            }
        }
    }

    private function carregarDados(): void
    {
        // Chamados com pagamento e avaliação
        $stmt = $this->pdo->prepare("
            SELECT c.*, t.nome AS tecnico_nome,
                   p.id AS pagamento_id, p.status AS pag_status, p.valor AS pag_valor, p.metodo AS pag_metodo,
                   a.nota AS avaliacao_nota
            FROM chamado c
            LEFT JOIN tecnico t  ON t.id = c.tecnico_id
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            LEFT JOIN avaliacao a ON a.chamado_id = c.id AND a.cliente_id = ?
            WHERE c.cliente_id = ?
            ORDER BY c.criado_em DESC
        ");
        $stmt->execute([$this->clienteId, $this->clienteId]);
        $this->chamados = array_map([ChamadoDTO::class, 'fromArray'], $stmt->fetchAll());

        // Stats
        $stmtS = $this->pdo->prepare("SELECT COUNT(*) AS total_chamados, SUM(CASE WHEN status='Concluído' THEN 1 ELSE 0 END) AS concluidos FROM chamado WHERE cliente_id=?");
        $stmtS->execute([$this->clienteId]);
        $this->stats = $stmtS->fetch() ?: ['total_chamados' => 0, 'concluidos' => 0];

        // Orçamentos pendentes
        $stmtO = $this->pdo->prepare("
            SELECT o.*, t.nome AS tecnico_nome, c.descricao AS chamado_desc, c.id AS chamado_id
            FROM orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            INNER JOIN tecnico t ON t.id = o.tecnico_id
            WHERE o.status = 'Pendente' AND c.status = 'Pendente'
            ORDER BY o.criado_em DESC
        ");
        $stmtO->execute([$this->clienteId]);
        $this->orcamentosPendentes = $stmtO->fetchAll();

        // Notificações não lidas
        $stmtN = $this->pdo->prepare("SELECT * FROM notificacao WHERE cliente_id=? AND tipo_destinatario='cliente' AND lida=0 ORDER BY criado_em DESC LIMIT 10");
        $stmtN->execute([$this->clienteId]);
        $this->notificacoes = $stmtN->fetchAll();
        $this->naoLidas = count($this->notificacoes);

        // Filtro categoria
        $this->filtroCategoria = trim($_GET['categoria'] ?? '');
        $whereCateg = $this->filtroCategoria !== '' ? 'AND c.nome = ?' : '';
        $paramsCat  = $this->filtroCategoria !== '' ? [$this->filtroCategoria] : [];

        // Serviços / prestadores
        $stmtSv = $this->pdo->prepare("
            SELECT s.id AS servico_id, s.nome AS servico_nome, s.descricao, s.preco,
                   c.nome AS categoria_nome,
                   t.id AS tecnico_id, t.nome AS tecnico_nome, t.foto_perfil, t.destaque,
                   COALESCE(t.avaliacao_media, 0) AS media_nota
            FROM servico s
            JOIN tecnico t ON t.id = s.tecnico_id AND t.ativo = 1 AND t.status_cadastro = 'Aprovado'
            LEFT JOIN categoria c ON c.id = s.categoria_id
            WHERE s.ativo = 1 $whereCateg
            ORDER BY t.destaque DESC, t.avaliacao_media DESC, s.nome ASC
        ");
        $stmtSv->execute($paramsCat);
        $rows = $stmtSv->fetchAll();

        // Agrupa por prestador: um card por prestador com lista de serviços
        $byProvider = [];
        foreach ($rows as $row) {
            $tid = $row['tecnico_id'];
            if (!isset($byProvider[$tid])) {
                $byProvider[$tid] = [
                    'tecnico_id'   => $tid,
                    'tecnico_nome' => $row['tecnico_nome'],
                    'foto_perfil'  => $row['foto_perfil'],
                    'destaque'     => $row['destaque'],
                    'media_nota'   => $row['media_nota'],
                    'servicos'     => [],
                    'preco_min'    => $row['preco'],
                    'preco_max'    => $row['preco'],
                ];
            }
            $byProvider[$tid]['servicos'][] = [
                'nome'           => $row['servico_nome'],
                'descricao'      => $row['descricao'],
                'preco'          => $row['preco'],
                'categoria_nome' => $row['categoria_nome'],
            ];
            $byProvider[$tid]['preco_min'] = min($byProvider[$tid]['preco_min'], (float)$row['preco']);
            $byProvider[$tid]['preco_max'] = max($byProvider[$tid]['preco_max'], (float)$row['preco']);
        }
        $this->servicos = array_values($byProvider);

        // Categorias
        $this->categorias = $this->pdo->query("SELECT id, nome FROM categoria WHERE ativo=1 ORDER BY nome")->fetchAll();

        // Depoimento aleatório
        try {
            $stmtD = $this->pdo->query("
                SELECT a.comentario, a.nota, cl.nome AS cliente_nome
                FROM avaliacao a INNER JOIN cliente cl ON cl.id = a.cliente_id
                WHERE a.comentario IS NOT NULL AND TRIM(a.comentario) != '' AND a.nota >= 4
                ORDER BY RAND() LIMIT 1
            ");
            $this->depoimento = $stmtD->fetch() ?: null;
        } catch (Exception $e) {
            $this->depoimento = null;
        }
    }
}
