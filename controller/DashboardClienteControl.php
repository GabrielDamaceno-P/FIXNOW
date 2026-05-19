<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/AvaliacaoDAO.php';
require_once __DIR__ . '/../model/dto/AvaliacaoDTO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class DashboardClienteControl
{
    private ChamadoDAO    $chamadoDAO;
    private OrcamentoDAO  $orcamentoDAO;
    private NotificacaoDAO $notifDAO;
    private AvaliacaoDAO  $avaliacaoDAO;
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
    public string $filtroCategoria      = '';
    public bool   $filtroPrestadoraMulher = false;
    public string $clienteGenero       = '';
    public int    $naoLidas            = 0;

    public function __construct()
    {
        $this->chamadoDAO   = new ChamadoDAO();
        $this->orcamentoDAO = new OrcamentoDAO();
        $this->notifDAO     = new NotificacaoDAO();
        $this->avaliacaoDAO = new AvaliacaoDAO();
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
                $rowTec = $this->pdo->prepare("SELECT tecnico_id FROM chamado WHERE id=? AND cliente_id=? AND status IN ('Pendente','Aguardando Orçamento','Em Andamento')");
                $rowTec->execute([$cid, $this->clienteId]);
                $tec = $rowTec->fetch();

                if ($tec === false) {
                    $this->erro = 'Chamado não encontrado ou já finalizado.';
                } else {
                    $up = $this->pdo->prepare("UPDATE chamado SET data_agendamento=?, data_agendamento_proposta=NULL, reagendamento_pendente=0 WHERE id=? AND cliente_id=? AND status IN ('Pendente','Aguardando Orçamento','Em Andamento')");
                    $up->execute([$novaData, $cid, $this->clienteId]);
                    if ($up->rowCount() > 0) {
                        if (!empty($tec['tecnico_id'])) {
                            fixnow_notificar_prestador($this->pdo, (int)$tec['tecnico_id'],
                                'O cliente reagendou o chamado #' . $cid . ' para ' . date('d/m/Y H:i', strtotime($novaData)) . '.', $cid);
                        }
                        header('Location: dashboardCliente.php?reagendado=1'); exit;
                    }
                    $this->erro = 'Não foi possível reagendar.';
                }
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
            $cid        = (int)$_POST['avaliar_chamado_id'];
            $nota       = (int)$_POST['nota_avaliacao'];
            $comentario = trim($_POST['comentario_avaliacao'] ?? '');
            if ($nota < 1 || $nota > 5) {
                $this->erro = 'Informe uma nota válida entre 1 e 5.';
            } elseif (mb_strlen($comentario) > 255) {
                $this->erro = 'O comentário deve ter no máximo 255 caracteres.';
            } else {
                $chamado = $this->chamadoDAO->buscarPorId($cid);
                if (!$chamado || $chamado->clienteId !== $this->clienteId) {
                    $this->erro = 'Chamado não encontrado.';
                } elseif ($chamado->status !== 'Concluído') {
                    $this->erro = 'A avaliação só pode ser feita após a conclusão do serviço.';
                } elseif (empty($chamado->tecnicoId)) {
                    $this->erro = 'Não existe prestador associado a este chamado.';
                } elseif ($this->avaliacaoDAO->jaAvaliou($cid)) {
                    $this->erro = 'Este chamado já foi avaliado.';
                } else {
                    $av = new AvaliacaoDTO();
                    $av->chamadoId  = $cid;
                    $av->clienteId  = $this->clienteId;
                    $av->tecnicoId  = $chamado->tecnicoId;
                    $av->nota       = $nota;
                    $av->comentario = $comentario ?: null;
                    $this->avaliacaoDAO->inserir($av);
                    header('Location: dashboardCliente.php?avaliacao_ok=1'); exit;
                }
            }
        }
    }

    private function carregarDados(): void
    {
        $this->chamados            = $this->chamadoDAO->listarPorCliente($this->clienteId);
        $this->stats               = $this->chamadoDAO->estatisticasCliente($this->clienteId);

        // Orçamentos pendentes
        $stmtO = $this->pdo->prepare("
            SELECT o.*, t.nome AS tecnico_nome, c.descricao AS chamado_desc, c.id AS chamado_id
            FROM orcamento o
            INNER JOIN chamado c ON c.id = o.chamado_id AND c.cliente_id = ?
            INNER JOIN tecnico t ON t.id = o.tecnico_id
            WHERE o.status = 'Pendente' AND c.status IN ('Pendente','Aguardando Orçamento')
            ORDER BY o.criado_em DESC
        ");
        $stmtO->execute([$this->clienteId]);
        $this->orcamentosPendentes = $stmtO->fetchAll();

        // Notificações não lidas
        $this->notificacoes = $this->notifDAO->listarPorCliente($this->clienteId, 10);
        $this->naoLidas     = $this->notifDAO->contarNaoLidasCliente($this->clienteId);

        // Gênero do cliente
        $rowCli = $this->pdo->prepare("SELECT genero FROM cliente WHERE id=? LIMIT 1");
        $rowCli->execute([$this->clienteId]);
        $this->clienteGenero = (string)($rowCli->fetchColumn() ?? '');

        // Filtros
        $this->filtroCategoria       = trim($_GET['categoria'] ?? '');
        $this->filtroPrestadoraMulher = isset($_GET['so_mulher']) && $this->clienteGenero === 'Feminino';

        $where  = ['s.ativo = 1'];
        $params = [];

        if ($this->filtroCategoria !== '') {
            $where[]  = 'c.nome = ?';
            $params[] = $this->filtroCategoria;
        }
        if ($this->filtroPrestadoraMulher) {
            $where[]  = "t.genero = 'Feminino'";
        }

        $whereSQL = implode(' AND ', $where);

        // Serviços / prestadores
        $stmtSv = $this->pdo->prepare("
            SELECT s.id AS servico_id, s.nome AS servico_nome, s.descricao, s.preco,
                   c.nome AS categoria_nome,
                   t.id AS tecnico_id, t.nome AS tecnico_nome, t.foto_perfil, t.destaque,
                   COALESCE(t.avaliacao_media, 0) AS media_nota
            FROM servico s
            JOIN tecnico t ON t.id = s.tecnico_id AND t.ativo = 1 AND t.status_cadastro = 'Aprovado'
            LEFT JOIN categoria c ON c.id = s.categoria_id
            WHERE $whereSQL
            ORDER BY t.destaque DESC, t.avaliacao_media DESC, s.nome ASC
        ");
        $stmtSv->execute($params);
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
