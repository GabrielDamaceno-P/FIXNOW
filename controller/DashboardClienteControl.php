<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/OrcamentoDAO.php';
require_once __DIR__ . '/../model/dao/PagamentoDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/AvaliacaoDAO.php';
require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/dao/ServicoDAO.php';
require_once __DIR__ . '/../model/dto/AvaliacaoDTO.php';
require_once __DIR__ . '/../includes/helpers.php';

class DashboardClienteControl
{
    private ChamadoDAO    $chamadoDAO;
    private OrcamentoDAO  $orcamentoDAO;
    private PagamentoDAO  $pagamentoDAO;
    private NotificacaoDAO $notifDAO;
    private AvaliacaoDAO  $avaliacaoDAO;
    private ClienteDAO    $clienteDAO;
    private CategoriaDAO  $categoriaDAO;
    private ServicoDAO    $servicoDAO;

    public int    $clienteId            = 0;
    public string $mensagem             = '';
    public string $erro                 = '';
    public array  $chamados             = [];
    public array  $stats                = ['total_chamados' => 0, 'concluidos' => 0];
    public array  $orcamentosPendentes  = [];
    public array  $notificacoes         = [];
    public array  $servicos             = [];
    public array  $categorias           = [];
    public ?array $depoimento           = null;
    public string $filtroCategoria         = '';
    public bool   $filtroPrestadoraMulher  = false;
    public string $clienteGenero           = '';
    public int    $naoLidas                = 0;

    public function __construct()
    {
        $this->chamadoDAO   = new ChamadoDAO();
        $this->orcamentoDAO = new OrcamentoDAO();
        $this->pagamentoDAO = new PagamentoDAO();
        $this->notifDAO     = new NotificacaoDAO();
        $this->avaliacaoDAO = new AvaliacaoDAO();
        $this->clienteDAO   = new ClienteDAO();
        $this->categoriaDAO = new CategoriaDAO();
        $this->servicoDAO   = new ServicoDAO();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: login.php'); exit;
        }
        $this->clienteId = (int)$_SESSION['cliente_id'];
        fixnow_checar_ativo_cliente($this->clienteId, 'login.php');
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
                if ($this->chamadoDAO->cancelar($cid, $this->clienteId)) {
                    header('Location: dashboardCliente.php?cancelar_ok=1'); exit;
                }
                $this->erro = 'Não foi possível cancelar (o chamado pode já ter sido aceito por um técnico).';
            }

        } elseif (isset($_POST['alterar_chamado_id'])) {
            $cid      = (int)$_POST['alterar_chamado_id'];
            $descricao = trim($_POST['nova_descricao'] ?? '');
            $endereco  = trim($_POST['novo_endereco']  ?? '');
            if ($cid > 0 && $descricao !== '' && $endereco !== '') {
                if ($this->chamadoDAO->alterarDescricaoEndereco($this->clienteId, $cid, $descricao, $endereco)) {
                    header('Location: dashboardCliente.php?alterado_ok=1'); exit;
                }
                $this->erro = 'Não foi possível alterar (o chamado pode já ter sido aceito).';
            }

        } elseif (isset($_POST['aceitar_orcamento_id'])) {
            $oid = (int)$_POST['aceitar_orcamento_id'];
            if ($oid > 0) {
                $result = $this->orcamentoDAO->aceitarComTransacao($this->clienteId, $oid);
                if ($result) {
                    fixnow_notificar_prestador((int)$result['tecnico_id'],
                        'Seu orçamento para o chamado #' . $result['chamado_id'] . ' foi aceito pelo cliente! Prepare-se para o atendimento.',
                        (int)$result['chamado_id']);
                    header('Location: dashboardCliente.php?orcamento_aceito=1'); exit;
                }
            }

        } elseif (isset($_POST['recusar_orcamento_id'])) {
            $oid    = (int)$_POST['recusar_orcamento_id'];
            $motivo = trim($_POST['motivo_recusa'] ?? '');
            if ($oid > 0) {
                $result = $this->orcamentoDAO->recusarRetornandoIds($this->clienteId, $oid);
                if ($result) {
                    $msg = 'O cliente recusou seu orçamento para o chamado #' . $result['chamado_id'] . '.';
                    if ($motivo !== '') $msg .= ' Motivo: ' . $motivo;
                    fixnow_notificar_prestador((int)$result['tecnico_id'], $msg, null);
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
                $result = $this->chamadoDAO->reagendarPorCliente($this->clienteId, $cid, $novaData);
                if (!$result['ok']) {
                    $this->erro = 'Chamado não encontrado ou já finalizado.';
                } else {
                    if ($result['tecnico_id'] !== null) {
                        fixnow_notificar_prestador($result['tecnico_id'],
                            'O cliente reagendou o chamado #' . $cid . ' para ' . date('d/m/Y H:i', strtotime($novaData)) . '.', $cid);
                    }
                    header('Location: dashboardCliente.php?reagendado=1'); exit;
                }
            }

        } elseif (isset($_POST['confirmar_pagamento_id'])) {
            $pid    = (int)$_POST['confirmar_pagamento_id'];
            $metodo = $_POST['metodo_pagamento'] ?? 'PIX';
            if (!in_array($metodo, ['PIX', 'Cartão', 'Dinheiro'], true)) $metodo = 'PIX';
            if ($pid > 0) {
                if ($this->pagamentoDAO->confirmarPorCliente($this->clienteId, $pid, $metodo)) {
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
        $this->chamados           = $this->chamadoDAO->listarPorCliente($this->clienteId);
        $this->stats              = $this->chamadoDAO->estatisticasCliente($this->clienteId);
        $this->orcamentosPendentes = $this->orcamentoDAO->listarPendentesParaCliente($this->clienteId);
        $this->notificacoes       = $this->notifDAO->listarPorCliente($this->clienteId, 10);
        $this->naoLidas           = $this->notifDAO->contarNaoLidasCliente($this->clienteId);

        $row = $this->clienteDAO->buscarCamposBasicos($this->clienteId);
        $this->clienteGenero = $row ? (string)($row['genero'] ?? '') : '';

        $this->filtroCategoria       = trim($_GET['categoria'] ?? '');
        $this->filtroPrestadoraMulher = isset($_GET['so_mulher']) && $this->clienteGenero === 'Feminino';

        $this->servicos  = $this->servicoDAO->listarPrestadoresComFiltro(
            $this->filtroCategoria ?: null,
            $this->filtroPrestadoraMulher
        );
        $this->categorias = $this->categoriaDAO->listarAtivas();

        try {
            $this->depoimento = $this->avaliacaoDAO->depoimentoAleatorio();
        } catch (Exception $e) {
            $this->depoimento = null;
        }
    }
}
