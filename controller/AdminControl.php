<?php

require_once __DIR__ . '/../model/dao/AdminDAO.php';
require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class AdminControl
{
    private AdminDAO    $adminDAO;
    private ClienteDAO  $clienteDAO;
    private TecnicoDAO  $tecnicoDAO;
    private ChamadoDAO  $chamadoDAO;
    private CategoriaDAO $categoriaDAO;

    public string $adminPerfil  = 'Master';
    public int    $adminId      = 0;
    public int    $naoLidas     = 0;
    public string $mensagem     = '';
    public string $erro         = '';
    public array  $statsGerais  = [];
    public float  $lucroEmpresa    = 0.0;
    public float  $totalEstornado  = 0.0;
    public float  $lucroEstornado  = 0.0;
    public array  $prestadoresPendentes = [];
    public array  $clientes     = [];
    public array  $prestadores  = [];
    public array  $categorias   = [];
    public array  $tickets      = [];
    public array  $chamados     = [];
    public array  $pagamentos   = [];
    public array  $servicos     = [];
    public array  $faturamentoMensal = [];
    public array  $topPrestadores   = [];
    public array  $atividadeRecente = [];

    public function __construct()
    {
        $this->adminDAO    = new AdminDAO();
        $this->clienteDAO  = new ClienteDAO();
        $this->tecnicoDAO  = new TecnicoDAO();
        $this->chamadoDAO  = new ChamadoDAO();
        $this->categoriaDAO = new CategoriaDAO();
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
            $acao     = $_POST['acao'] ?? '';
            $isMaster = $this->adminPerfil === 'Master';
            $isOps    = in_array($this->adminPerfil, ['Master', 'Operacoes'], true);
            $isFinanc = in_array($this->adminPerfil, ['Master', 'Financeiro'], true);

            if ($acao === 'aprovar_prestador' && $isOps) {
                $tid = (int)($_POST['tecnico_id'] ?? 0);
                if ($tid > 0 && $this->adminDAO->aprovarPrestador($tid, $this->adminId)) {
                    fixnow_notificar_prestador($tid,
                        'Seu cadastro de prestador foi aprovado! Você já pode acessar o painel e aceitar chamados.');
                    $this->mensagem = 'Prestador aprovado.';
                }

            } elseif ($acao === 'recusar_prestador' && $isOps) {
                $tid = (int)($_POST['tecnico_id'] ?? 0);
                if ($tid > 0 && $this->adminDAO->recusarPrestador($tid, $this->adminId)) {
                    fixnow_notificar_prestador($tid,
                        'Seu cadastro de prestador foi recusado. Entre em contato com o suporte para mais informações.');
                    $this->mensagem = 'Prestador recusado.';
                }

            } elseif ($acao === 'negar_chamado' && $isOps) {
                $cid = (int)($_POST['chamado_id'] ?? 0);
                if ($cid > 0) {
                    $clienteId = $this->adminDAO->negarChamado($cid);
                    if ($clienteId !== null) {
                        fixnow_notificar_cliente($clienteId,
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
                    $info = $this->adminDAO->alterarStatusChamado($cid, $status);
                    if ($info && $status === 'Negado' && $info['old_status'] !== 'Negado') {
                        fixnow_notificar_cliente((int)$info['cliente_id'],
                            "Seu chamado #{$cid} foi atualizado para Negado.", $cid);
                    }
                    $this->mensagem = 'Status do chamado atualizado.';
                }

            } elseif ($acao === 'excluir_servico' && $isMaster) {
                $sid = (int)($_POST['servico_id'] ?? 0);
                if ($sid > 0) {
                    $this->adminDAO->excluirServico($sid);
                    $this->mensagem = 'Serviço removido.';
                }

            } elseif ($acao === 'excluir_cliente' && $isMaster) {
                $cid = (int)($_POST['cliente_id'] ?? 0);
                if ($cid > 0) {
                    $this->adminDAO->excluirCliente($cid);
                    $this->mensagem = 'Cliente removido.';
                }

            } elseif ($acao === 'excluir_prestador' && $isMaster) {
                $tid = (int)($_POST['tecnico_id'] ?? 0);
                if ($tid > 0) {
                    $this->adminDAO->excluirPrestador($tid);
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
                    else { $this->categoriaDAO->inserir($nome, $descricao, $this->adminId); $this->mensagem = 'Categoria criada.'; }
                }

            } elseif ($acao === 'excluir_categoria' && $isMaster) {
                $cid = (int)($_POST['cat_id'] ?? 0);
                if ($cid > 0) {
                    $this->categoriaDAO->excluir($cid);
                    $this->mensagem = 'Categoria removida.';
                }

            } elseif ($acao === 'responder_ticket') {
                $tid    = (int)($_POST['ticket_id'] ?? 0);
                $resp   = trim($_POST['resposta']   ?? '');
                $status = $_POST['ticket_status']   ?? 'Fechado';
                if ($tid > 0 && $resp) {
                    $this->adminDAO->responderTicket($tid, $resp, $status, $this->adminId);
                    $this->mensagem = 'Resposta enviada.';
                }
            }
        }

        $this->carregarDados();
    }

    private function carregarDados(): void
    {
        $totPagos             = $this->adminDAO->calcularTotalPagos();
        $this->lucroEmpresa   = $this->adminDAO->calcularLucroEmpresa();
        $this->totalEstornado = $this->adminDAO->calcularTotalEstornado();
        $this->lucroEstornado = $this->adminDAO->calcularLucroEstornado();

        $this->statsGerais = [
            'clientes'           => $this->adminDAO->contarClientes(),
            'prestadores'        => $this->adminDAO->contarTecnicos(),
            'prestadores_ativos' => $this->adminDAO->contarTecnicosAtivos(),
            'chamados'           => $this->chamadoDAO->estatisticasGerais(),
            'faturamento'        => $totPagos,
            'lucro'              => $this->lucroEmpresa,
            'avaliacao'          => $this->adminDAO->calcularMediaAvaliacoes(),
            'chamados_pendentes' => $this->adminDAO->contarChamadosPendentes(),
            'chamados_mes'       => $this->adminDAO->contarChamadosMes(),
        ];

        $this->naoLidas             = $this->adminDAO->contarNaoLidas();
        $this->prestadoresPendentes = $this->adminDAO->listarPrestadoresPendentes();
        $this->clientes             = $this->adminDAO->listarClientes();
        $this->prestadores          = $this->adminDAO->listarPrestadores();
        $this->categorias           = $this->categoriaDAO->listarTodas();
        $this->tickets              = $this->adminDAO->listarTickets();
        $this->faturamentoMensal    = $this->adminDAO->faturamentoMensal();
        $this->topPrestadores       = $this->adminDAO->topPrestadores();
        $this->atividadeRecente     = $this->adminDAO->atividadeRecente();

        $isOps   = in_array($this->adminPerfil, ['Master', 'Operacoes'], true);
        $isFinanc = in_array($this->adminPerfil, ['Master', 'Financeiro'], true);

        if ($isOps) {
            $this->chamados = $this->adminDAO->listarChamados();
            $this->servicos = $this->adminDAO->listarServicos();
        }

        if ($isFinanc) {
            $this->pagamentos = $this->adminDAO->listarPagamentos();
        }
    }
}
