<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/PagamentoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ServicoDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class DashboardPrestadorControl
{
    private ChamadoDAO     $chamadoDAO;
    private OrcamentoDAO   $orcamentoDAO;
    private PagamentoDAO   $pagamentoDAO;
    private NotificacaoDAO $notifDAO;
    private TecnicoDAO     $tecnicoDAO;
    private ServicoDAO     $servicoDAO;

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
        $dto    = $this->tecnicoDAO->buscarPorId($this->tecnicoId);
        if ($genero === '') {
            $genero = $dto?->genero ?: 'Masculino';
            $_SESSION['tecnico_genero'] = $genero;
        }
        $this->genero     = $genero;
        $this->isDestaque = (bool)($dto?->destaque ?? false);
    }

    private function processarPost(): void
    {
        if (isset($_POST['aceitar_id'])) {
            $cid = (int)$_POST['aceitar_id'];
            if ($cid > 0) {
                $clienteId = $this->chamadoDAO->aceitarDaFila($this->tecnicoId, $cid, $this->genero);
                if ($clienteId !== null) {
                    fixnow_notificar_cliente($clienteId,
                        "Seu chamado #{$cid} foi aceito! Aguarde o envio do orçamento.", $cid);
                    header("Location: orcamento.php?chamado={$cid}&aceito=1"); exit;
                }
                $this->erro = 'Não foi possível aceitar este chamado (já atribuído ou indisponível).';
            }

        } elseif (isset($_POST['negar_id'])) {
            $cid = (int)$_POST['negar_id'];
            if ($cid > 0) {
                $clienteId = $this->chamadoDAO->negarDaFila($cid);
                if ($clienteId !== null) {
                    fixnow_notificar_cliente($clienteId,
                        "Um prestador negou a execução do chamado #{$cid}. Abra um novo pedido ou contate o suporte.", $cid);
                    $this->mensagem = 'Serviço negado. O cliente foi notificado.';
                } else {
                    $this->erro = 'Não foi possível negar este chamado.';
                }
            }

        } elseif (isset($_POST['aceitar_direto_id'])) {
            $cid = (int)$_POST['aceitar_direto_id'];
            if ($cid > 0) {
                $clienteId = $this->chamadoDAO->aceitarDireto($this->tecnicoId, $cid);
                if ($clienteId !== null) {
                    fixnow_notificar_cliente($clienteId,
                        "Sua solicitação direta #{$cid} foi aceita! Aguarde o envio do orçamento.", $cid);
                    header("Location: orcamento.php?chamado={$cid}&aceito=1"); exit;
                }
                $this->erro = 'Não foi possível aceitar esta solicitação.';
            }

        } elseif (isset($_POST['recusar_direto_id'])) {
            $cid = (int)$_POST['recusar_direto_id'];
            if ($cid > 0) {
                $clienteId = $this->chamadoDAO->recusarDireto($this->tecnicoId, $cid);
                if ($clienteId !== null) {
                    fixnow_notificar_cliente($clienteId,
                        "O prestador recusou sua solicitação direta #{$cid}. Seu chamado voltou para a fila geral.", $cid);
                    $this->mensagem = 'Solicitação recusada. O chamado voltou para a fila geral.';
                } else {
                    $this->erro = 'Não foi possível recusar esta solicitação.';
                }
            }

        } elseif (isset($_POST['aceitar_reagendamento_id'])) {
            $cid = (int)$_POST['aceitar_reagendamento_id'];
            if ($cid > 0) {
                $info = $this->chamadoDAO->aceitarReagendamentoProposta($this->tecnicoId, $cid);
                if ($info) {
                    $dataFmt = date('d/m/Y H:i', strtotime($info['data_agendamento']));
                    fixnow_notificar_cliente((int)$info['cliente_id'],
                        "O prestador confirmou o reagendamento do chamado #{$cid} para {$dataFmt}.", $cid);
                    $this->mensagem = 'Reagendamento aceito.';
                } else {
                    $this->erro = 'Não foi possível aceitar o reagendamento.';
                }
            }

        } elseif (isset($_POST['recusar_reagendamento_id'])) {
            $cid = (int)$_POST['recusar_reagendamento_id'];
            if ($cid > 0) {
                $clienteId = $this->chamadoDAO->recusarReagendamentoProposta($this->tecnicoId, $cid);
                if ($clienteId !== null) {
                    fixnow_notificar_cliente($clienteId,
                        "O prestador recusou o reagendamento do chamado #{$cid}. A data anterior foi mantida.", $cid);
                    $this->mensagem = 'Reagendamento recusado. Data original mantida.';
                } else {
                    $this->erro = 'Não foi possível recusar o reagendamento.';
                }
            }

        } elseif (isset($_POST['chamado_id'], $_POST['novo_status'])) {
            $cid    = (int)$_POST['chamado_id'];
            $novo   = $_POST['novo_status'] ?? '';
            $perm   = ['Pendente', 'Em Andamento', 'Concluído', 'Negado'];
            if ($cid > 0 && in_array($novo, $perm, true)) {
                $result = $this->chamadoDAO->alterarStatusComPagamento($this->tecnicoId, $cid, $novo);
                if (!$result['ok']) {
                    $this->erro = 'Chamado não encontrado ou sem permissão.';
                } else {
                    if ($result['cliente_id'] && $result['old_status'] !== $novo) {
                        $msgStatus = match($novo) {
                            'Concluído'    => "Seu chamado #{$cid} foi marcado como Concluído pelo prestador. Já pode avaliar!",
                            'Em Andamento' => "Seu chamado #{$cid} está Em Andamento.",
                            'Negado'       => "O chamado #{$cid} foi marcado como Negado pelo prestador.",
                            default        => null,
                        };
                        if ($msgStatus) {
                            fixnow_notificar_cliente((int)$result['cliente_id'], $msgStatus, $cid);
                        }
                    }
                    $this->mensagem = $novo === 'Concluído'
                        ? 'Serviço concluído. O cliente já pode avaliar e efetuar o pagamento.'
                        : 'Status atualizado.';
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
        $this->chamadosAguardando  = $this->chamadoDAO->listarAguardandoOrcamento($this->tecnicoId);
        $this->emAndamento         = $this->chamadoDAO->listarEmAndamentoPorTecnico($this->tecnicoId);
        $this->historico           = $this->chamadoDAO->listarHistoricoPorTecnico($this->tecnicoId);
        $this->notificacoes        = $this->notifDAO->listarPorTecnico($this->tecnicoId, 10);
        $this->categoriasServico   = $this->servicoDAO->listarNomesCategoriasDoTecnico($this->tecnicoId);

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
