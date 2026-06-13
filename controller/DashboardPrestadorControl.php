<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/PagamentoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ServicoDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class DashboardPrestadorControl
{
    private ChamadoDAO     $chamadoDAO;
    private OrcamentoDAO   $orcamentoDAO;
    private PagamentoDAO   $pagamentoDAO;
    private NotificacaoDAO $notifDAO;
    private TecnicoDAO     $tecnicoDAO;
    private ServicoDAO     $servicoDAO;
    private PDO            $pdo;

    public int    $tecnicoId              = 0;
    public string $genero                 = 'Masculino';
    public bool   $isDestaque             = false;
    public array  $categoriasServico      = [];
    public string $mensagem               = '';
    public string $erro                   = '';
    public array  $stats                  = [];
    public int    $pendentesCount         = 0;
    public array  $chamadosDisponiveis    = [];
    public array  $solicitacoesDiretas    = [];
    public array  $chamadosAguardando     = [];
    public array  $emAndamento            = [];
    public array  $historico              = [];
    public array  $notificacoes           = [];

    public function __construct()
    {
        $this->chamadoDAO   = new ChamadoDAO();
        $this->orcamentoDAO = new OrcamentoDAO();
        $this->pagamentoDAO = new PagamentoDAO();
        $this->notifDAO     = new NotificacaoDAO();
        $this->tecnicoDAO   = new TecnicoDAO();
        $this->servicoDAO   = new ServicoDAO();
        $this->pdo          = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];
        fixnow_checar_ativo_prestador($this->tecnicoId, '../login.php');
    }

    public function processar(): void
    {
        $this->verificarSessao();

        if (isset($_GET['lida'])) {
            $nid = (int)$_GET['lida'];
            if ($nid > 0) $this->notifDAO->marcarLidaTecnico($nid, $this->tecnicoId);
            header('Location: dashboardPrestador.php'); exit;
        }

        $this->carregarGeneroDestaque();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $this->carregarDados();
    }

    private function carregarGeneroDestaque(): void
    {
        $genero = $_SESSION['tecnico_genero'] ?? '';
        if ($genero === '') {
            $dto    = $this->tecnicoDAO->buscarPorId($this->tecnicoId);
            $genero = $dto?->genero ?: 'Masculino';
            $_SESSION['tecnico_genero'] = $genero;
            $this->isDestaque = (bool)($dto?->destaque ?? false);
        } else {
            $dto = $this->tecnicoDAO->buscarPorId($this->tecnicoId);
            $this->isDestaque = (bool)($dto?->destaque ?? false);
        }
        $this->genero = $genero;
    }

    private function processarPost(): void
    {
        // aceitar chamado da fila (pendente sem técnico)
        if (isset($_POST['aceitar_id'])) {
            $cid = (int)$_POST['aceitar_id'];
            if ($cid > 0) {
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
                $up->execute([$this->tecnicoId, $cid, $this->tecnicoId, $this->genero]);
                if ($up->rowCount() > 0) {
                    $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
                    $stmt->execute([$cid]);
                    $row = $stmt->fetch();
                    if ($row) {
                        fixnow_notificar_cliente($this->pdo, (int)$row['cliente_id'],
                            "Seu chamado #{$cid} foi aceito! Aguarde o envio do orçamento.", $cid);
                    }
                    header("Location: orcamento.php?chamado={$cid}&aceito=1");
                    exit;
                } else {
                    $this->erro = 'Não foi possível aceitar este chamado (já atribuído ou indisponível).';
                }
            }

        // negar chamado da fila
        } elseif (isset($_POST['negar_id'])) {
            $cid = (int)$_POST['negar_id'];
            if ($cid > 0) {
                $stmt = $this->pdo->prepare("SELECT cliente_id FROM chamado WHERE id = ? AND status = 'Pendente' AND tecnico_id IS NULL");
                $stmt->execute([$cid]);
                $info = $stmt->fetch();
                if ($info) {
                    $up = $this->pdo->prepare("UPDATE chamado SET status = 'Negado' WHERE id = ? AND status = 'Pendente' AND tecnico_id IS NULL");
                    $up->execute([$cid]);
                    if ($up->rowCount() > 0) {
                        fixnow_notificar_cliente($this->pdo, (int)$info['cliente_id'],
                            "Um prestador negou a execução do chamado #{$cid}. Abra um novo pedido ou contate o suporte.", $cid);
                        $this->mensagem = 'Serviço negado. O cliente foi notificado.';
                    } else {
                        $this->erro = 'Não foi possível negar este chamado.';
                    }
                } else {
                    $this->erro = 'Chamado indisponível para negação.';
                }
            }

        // aceitar solicitação direta
        } elseif (isset($_POST['aceitar_direto_id'])) {
            $cid = (int)$_POST['aceitar_direto_id'];
            if ($cid > 0) {
                $up = $this->pdo->prepare("UPDATE chamado SET status = 'Aguardando Orçamento' WHERE id = ? AND tecnico_id = ? AND status = 'Pendente'");
                $up->execute([$cid, $this->tecnicoId]);
                if ($up->rowCount() > 0) {
                    $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
                    $stmt->execute([$cid]);
                    $row = $stmt->fetch();
                    if ($row) {
                        fixnow_notificar_cliente($this->pdo, (int)$row['cliente_id'],
                            "Sua solicitação direta #{$cid} foi aceita! Aguarde o envio do orçamento.", $cid);
                    }
                    header("Location: orcamento.php?chamado={$cid}&aceito=1");
                    exit;
                } else {
                    $this->erro = 'Não foi possível aceitar esta solicitação.';
                }
            }

        // recusar solicitação direta
        } elseif (isset($_POST['recusar_direto_id'])) {
            $cid = (int)$_POST['recusar_direto_id'];
            if ($cid > 0) {
                $up = $this->pdo->prepare("UPDATE chamado SET tecnico_id = NULL WHERE id = ? AND tecnico_id = ? AND status = 'Pendente'");
                $up->execute([$cid, $this->tecnicoId]);
                if ($up->rowCount() > 0) {
                    $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
                    $stmt->execute([$cid]);
                    $row = $stmt->fetch();
                    if ($row) {
                        fixnow_notificar_cliente($this->pdo, (int)$row['cliente_id'],
                            "O prestador recusou sua solicitação direta #{$cid}. Seu chamado voltou para a fila geral.", $cid);
                    }
                    $this->mensagem = 'Solicitação recusada. O chamado voltou para a fila geral.';
                } else {
                    $this->erro = 'Não foi possível recusar esta solicitação.';
                }
            }

        // aceitar proposta de reagendamento do cliente
        } elseif (isset($_POST['aceitar_reagendamento_id'])) {
            $cid = (int)$_POST['aceitar_reagendamento_id'];
            if ($cid > 0) {
                $up = $this->pdo->prepare("
                    UPDATE chamado
                    SET data_agendamento = data_agendamento_proposta,
                        data_agendamento_proposta = NULL,
                        reagendamento_pendente = 0
                    WHERE id = ? AND tecnico_id = ? AND reagendamento_pendente = 1
                ");
                $up->execute([$cid, $this->tecnicoId]);
                if ($up->rowCount() > 0) {
                    $stmt = $this->pdo->prepare('SELECT cliente_id, data_agendamento FROM chamado WHERE id = ?');
                    $stmt->execute([$cid]);
                    $row = $stmt->fetch();
                    if ($row) {
                        $dataFmt = date('d/m/Y H:i', strtotime($row['data_agendamento']));
                        fixnow_notificar_cliente($this->pdo, (int)$row['cliente_id'],
                            "O prestador confirmou o reagendamento do chamado #{$cid} para {$dataFmt}.", $cid);
                    }
                    $this->mensagem = 'Reagendamento aceito.';
                } else {
                    $this->erro = 'Não foi possível aceitar o reagendamento.';
                }
            }

        // recusar proposta de reagendamento do cliente
        } elseif (isset($_POST['recusar_reagendamento_id'])) {
            $cid = (int)$_POST['recusar_reagendamento_id'];
            if ($cid > 0) {
                $up = $this->pdo->prepare("
                    UPDATE chamado
                    SET data_agendamento_proposta = NULL, reagendamento_pendente = 0
                    WHERE id = ? AND tecnico_id = ? AND reagendamento_pendente = 1
                ");
                $up->execute([$cid, $this->tecnicoId]);
                if ($up->rowCount() > 0) {
                    $stmt = $this->pdo->prepare('SELECT cliente_id FROM chamado WHERE id = ?');
                    $stmt->execute([$cid]);
                    $row = $stmt->fetch();
                    if ($row) {
                        fixnow_notificar_cliente($this->pdo, (int)$row['cliente_id'],
                            "O prestador recusou o reagendamento do chamado #{$cid}. A data anterior foi mantida.", $cid);
                    }
                    $this->mensagem = 'Reagendamento recusado. Data original mantida.';
                } else {
                    $this->erro = 'Não foi possível recusar o reagendamento.';
                }
            }

        // alterar status de chamado em andamento
        } elseif (isset($_POST['chamado_id'], $_POST['novo_status'])) {
            $cid  = (int)$_POST['chamado_id'];
            $novo = $_POST['novo_status'] ?? '';
            $permitidos = ['Pendente', 'Em Andamento', 'Concluído', 'Negado'];
            if ($cid > 0 && in_array($novo, $permitidos, true)) {
                try {
                    $this->pdo->beginTransaction();

                    $stmtOld = $this->pdo->prepare('SELECT cliente_id, status FROM chamado WHERE id = ? AND tecnico_id = ?');
                    $stmtOld->execute([$cid, $this->tecnicoId]);
                    $oldRow = $stmtOld->fetch();

                    $up = $this->pdo->prepare('UPDATE chamado SET status = ? WHERE id = ? AND tecnico_id = ?');
                    $up->execute([$novo, $cid, $this->tecnicoId]);

                    if ($up->rowCount() === 0) {
                        $this->pdo->rollBack();
                        $this->erro = 'Chamado não encontrado ou sem permissão.';
                    } else {
                        if ($oldRow && ($oldRow['status'] ?? '') !== $novo) {
                            $msgStatus = match($novo) {
                                'Concluído'    => "Seu chamado #{$cid} foi marcado como Concluído pelo prestador. Já pode avaliar!",
                                'Em Andamento' => "Seu chamado #{$cid} está Em Andamento.",
                                'Negado'       => "O chamado #{$cid} foi marcado como Negado pelo prestador.",
                                default        => null,
                            };
                            if ($msgStatus) {
                                fixnow_notificar_cliente($this->pdo, (int)$oldRow['cliente_id'], $msgStatus, $cid);
                            }
                        }

                        if ($novo === 'Concluído') {
                            $stmtPag = $this->pdo->prepare("SELECT id FROM pagamento WHERE chamado_id = ? LIMIT 1");
                            $stmtPag->execute([$cid]);
                            if (!$stmtPag->fetch()) {
                                $insPag = $this->pdo->prepare("
                                    INSERT INTO pagamento (chamado_id, metodo, valor, status)
                                    SELECT id, 'PIX', preco_sugerido, 'Pendente'
                                    FROM chamado WHERE id = ?
                                ");
                                $insPag->execute([$cid]);
                            }
                            $this->mensagem = 'Serviço concluído. O cliente já pode avaliar e efetuar o pagamento.';
                        } else {
                            $this->mensagem = 'Status atualizado.';
                        }

                        $this->pdo->commit();
                    }
                } catch (Throwable $e) {
                    if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                    $this->erro = 'Erro ao atualizar status. Tente novamente.';
                }
            }
        }
    }

    private function carregarDados(): void
    {
        $this->stats               = $this->chamadoDAO->estatisticasTecnico($this->tecnicoId);
        $this->chamadosDisponiveis = $this->chamadoDAO->listarDisponiveisPorCategoria($this->tecnicoId, $this->genero);
        $this->pendentesCount      = count($this->chamadosDisponiveis);
        $this->solicitacoesDiretas = $this->chamadoDAO->listarSolicitacoesDiretas($this->tecnicoId);
        $this->emAndamento         = $this->chamadoDAO->listarEmAndamentoPorTecnico($this->tecnicoId);
        $this->historico           = $this->chamadoDAO->listarHistoricoPorTecnico($this->tecnicoId);
        $this->notificacoes        = $this->notifDAO->listarPorTecnico($this->tecnicoId, 10);
        $this->categoriasServico   = $this->servicoDAO->listarNomesCategoriasDoTecnico($this->tecnicoId);

        // Chamados aceitos aguardando envio de orçamento
        $stmtAg = $this->pdo->prepare("
            SELECT c.*, cl.nome AS cliente_nome, cl.telefone AS cliente_telefone,
                   cl.foto_perfil AS cliente_foto, cl.endereco AS cliente_endereco
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            WHERE c.tecnico_id = ? AND c.status = 'Aguardando Orçamento'
              AND NOT EXISTS (SELECT 1 FROM orcamento o WHERE o.chamado_id = c.id AND o.tecnico_id = ? AND o.status = 'Pendente')
            ORDER BY c.criado_em DESC
        ");
        $stmtAg->execute([$this->tecnicoId, $this->tecnicoId]);
        $this->chamadosAguardando = $stmtAg->fetchAll();

        $this->anexarFotos($this->chamadosDisponiveis);
        $this->anexarFotos($this->solicitacoesDiretas);
        $this->anexarFotos($this->chamadosAguardando);
        $this->anexarFotos($this->emAndamento);
    }

    private function anexarFotos(array &$lista): void
    {
        foreach ($lista as &$item) {
            $rows = $this->chamadoDAO->listarFotos((int)$item['id']);
            $item['fotos'] = array_column($rows, 'foto_path');
            if (empty($item['fotos']) && !empty($item['foto_path'])) {
                $item['fotos'] = [$item['foto_path']];
            }
        }
        unset($item);
    }
}
