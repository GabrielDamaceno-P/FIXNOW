<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/dao/DisponibilidadeDAO.php';
require_once __DIR__ . '/../model/dao/ServicoDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class SolicitarChamadoControl
{
    private ChamadoDAO       $chamadoDAO;
    private CategoriaDAO     $categoriaDAO;
    private TecnicoDAO       $tecnicoDAO;
    private ClienteDAO       $clienteDAO;
    private NotificacaoDAO   $notifDAO;
    private DisponibilidadeDAO $dispoDAO;
    private ServicoDAO       $servicoDAO;

    public int    $clienteId          = 0;
    public string $clienteNome        = '';
    public string $clienteFoto        = '';
    public string $clienteEndereco    = '';
    public string $clienteCep         = '';
    public string $clienteGenero      = '';
    public int    $naoLidas           = 0;
    public string $mensagem           = '';
    public string $erro               = '';
    public array  $categorias         = [];
    public array  $prestadores        = [];
    public int    $prestadorPre       = 0;
    public string $categoriaPre       = '';
    public ?array $tecnicoInfo        = null;
    public array  $slotsDisponiveis   = [];
    public array  $categoriasPrestador = [];

    public function __construct()
    {
        $this->chamadoDAO   = new ChamadoDAO();
        $this->categoriaDAO = new CategoriaDAO();
        $this->tecnicoDAO   = new TecnicoDAO();
        $this->clienteDAO   = new ClienteDAO();
        $this->notifDAO     = new NotificacaoDAO();
        $this->dispoDAO     = new DisponibilidadeDAO();
        $this->servicoDAO   = new ServicoDAO();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->clienteId     = (int)$_SESSION['cliente_id'];
        fixnow_checar_ativo_cliente($this->clienteId, '../login.php');
        $this->clienteNome   = $_SESSION['cliente_nome']   ?? '';
        $this->clienteFoto   = $_SESSION['cliente_foto']   ?? '';
        $this->clienteGenero = $_SESSION['cliente_genero'] ?? '';
    }

    public function processar(): void
    {
        $this->verificarSessao();
        $this->carregarDadosCliente();

        $this->prestadorPre = (int)($_GET['prestador'] ?? 0);
        $this->categoriaPre = trim($_GET['categoria']  ?? '');

        if ($this->prestadorPre > 0) {
            $this->carregarTecnicoESlots($this->prestadorPre);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
            return;
        }

        $this->categorias  = $this->categoriaDAO->listarAtivas();
        $this->prestadores = $this->tecnicoDAO->listarAprovados();
    }

    private function carregarDadosCliente(): void
    {
        $row = $this->clienteDAO->buscarCamposBasicos($this->clienteId);
        if ($row) {
            $this->clienteNome     = $this->clienteNome     ?: ($row['nome']        ?? '');
            $this->clienteFoto     = $this->clienteFoto     ?: ($row['foto_perfil'] ?? '');
            $this->clienteEndereco = $row['endereco'] ?? '';
            $this->clienteCep      = $row['cep']      ?? '';
            if (!$this->clienteGenero) $this->clienteGenero = $row['genero'] ?? '';
        }

        try {
            $this->naoLidas = $this->notifDAO->contarNaoLidasCliente($this->clienteId);
        } catch (Throwable $e) {
            $this->naoLidas = 0;
        }
    }

    private function carregarTecnicoESlots(int $tecnicoId): void
    {
        $info = $this->tecnicoDAO->buscarInfoSimples($tecnicoId);
        if (!$info) return;

        $this->tecnicoInfo = $info;

        $this->categoriasPrestador = $this->tecnicoDAO->buscarCategoriasDoPrestador($tecnicoId);

        if (count($this->categoriasPrestador) === 1 && $this->categoriaPre === '') {
            $this->categoriaPre = $this->categoriasPrestador[0];
        }

        $bloqueados = $this->dispoDAO->buscarBloqueadosFuturos($tecnicoId);
        $ocupados   = $this->chamadoDAO->buscarSlotsOcupadosFuturos($tecnicoId);

        foreach ($ocupados as $data => $horas) {
            foreach ($horas as $hora => $v) {
                $bloqueados[$data][$hora] = true;
            }
        }

        $horasPoss = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];
        $minDt     = new DateTime('+1 hour');
        $curr      = new DateTime('today');
        $limite    = (new DateTime('today'))->modify('+28 days');
        while ($curr <= $limite) {
            $dataStr = $curr->format('Y-m-d');
            foreach ($horasPoss as $hora) {
                $slotDt = new DateTime($dataStr . ' ' . $hora . ':00');
                if ($slotDt < $minDt) continue;
                if (!isset($bloqueados[$dataStr][$hora])) {
                    $this->slotsDisponiveis[$dataStr][] = $hora;
                }
            }
            $curr->modify('+1 day');
        }
    }

    private function processarPost(): void
    {
        $categoria   = trim($_POST['categoria']  ?? '');
        $descricao   = trim($_POST['descricao']  ?? '');
        $endereco    = trim($_POST['endereco']   ?? '');
        $exigeMulher = isset($_POST['prest_feminino']) ? 1 : 0;
        $tecnicoId   = null;
        $dataAgend   = null;

        if (!$categoria || !$descricao || !$endereco) {
            $this->erro = 'Preencha todos os campos obrigatórios.';
            $this->categorias  = $this->categoriaDAO->listarAtivas();
            $this->prestadores = $this->tecnicoDAO->listarAprovados();
            return;
        }

        if ($exigeMulher && $this->clienteGenero !== 'Feminino') {
            $this->erro = 'A opção de prestadora mulher só está disponível para clientes com gênero feminino no cadastro.';
            $this->categorias  = $this->categoriaDAO->listarAtivas();
            $this->prestadores = $this->tecnicoDAO->listarAprovados();
            return;
        }

        $tecnicoIdPost   = (int)($_POST['tecnico_id_selecionado'] ?? 0);
        $slotSelecionado = trim($_POST['slot_selecionado'] ?? '');

        if ($tecnicoIdPost > 0 && $slotSelecionado !== '') {
            [$slotData, $slotHora] = array_pad(explode('|', $slotSelecionado), 2, '');

            if ($this->dispoDAO->slotEstaBloqueado($tecnicoIdPost, $slotData, $slotHora)
                || $this->chamadoDAO->slotEstaReservado($tecnicoIdPost, $slotData, $slotHora)) {
                $this->erro = 'Este horário não está mais disponível. Por favor, escolha outro.';
                $this->carregarTecnicoESlots($tecnicoIdPost);
                $this->categorias  = $this->categoriaDAO->listarAtivas();
                $this->prestadores = $this->tecnicoDAO->listarAprovados();
                return;
            }

            $tecnicoId = $tecnicoIdPost;
            $dataAgend = $slotData . ' ' . $slotHora . ':00';
        } else {
            $tecnicoIdGeral = (int)($_POST['tecnico_id'] ?? 0) ?: null;
            $tecnicoId      = $tecnicoIdGeral;
            if (!empty($_POST['data_agendamento'])) {
                $dataAgend = $_POST['data_agendamento'];
            }
        }

        $fotos = [];
        if (!empty($_FILES['fotos']['name'][0])) {
            $total = count($_FILES['fotos']['name']);
            for ($i = 0; $i < min($total, 6); $i++) {
                $fileItem = [
                    'name'     => $_FILES['fotos']['name'][$i],
                    'type'     => $_FILES['fotos']['type'][$i],
                    'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                    'error'    => $_FILES['fotos']['error'][$i],
                    'size'     => $_FILES['fotos']['size'][$i],
                ];
                if ($fileItem['error'] === 0) {
                    $path = fixnow_upload_image($fileItem, fixnow_public_upload_dir(), 'chamado');
                    if ($path) $fotos[] = $path;
                }
            }
        }

        if ($this->erro) {
            $this->categorias  = $this->categoriaDAO->listarAtivas();
            $this->prestadores = $this->tecnicoDAO->listarAprovados();
            return;
        }

        $latServico = isset($_POST['lat_servico']) && $_POST['lat_servico'] !== ''
            ? (float)$_POST['lat_servico'] : null;
        $lngServico = isset($_POST['lng_servico']) && $_POST['lng_servico'] !== ''
            ? (float)$_POST['lng_servico'] : null;

        $chamadoId = $this->chamadoDAO->inserir([
            'cliente_id'       => $this->clienteId,
            'tecnico_id'       => $tecnicoId,
            'categoria'        => $categoria,
            'descricao'        => $descricao,
            'fotos'            => $fotos,
            'endereco_servico' => $endereco,
            'lat_servico'      => $latServico,
            'lng_servico'      => $lngServico,
            'data_agendamento' => $dataAgend,
            'prest_feminino'   => $exigeMulher,
        ]);

        if ($tecnicoId) {
            fixnow_notificar_prestador($tecnicoId,
                "Você recebeu uma solicitação direta de serviço (#$chamadoId)! Um cliente escolheu você especificamente. Acesse o painel para aceitar ou recusar.",
                $chamadoId);
        } else {
            $destaques = $this->servicoDAO->buscarTecnicosDestaquesPorCategoria($categoria);
            foreach ($destaques as $destTecnicoId) {
                fixnow_notificar_prestador((int)$destTecnicoId,
                    "Novo chamado de {$categoria} disponível! Como prestador em destaque, você tem prioridade.", $chamadoId);
            }
        }

        header('Location: ../dashboardCliente.php?chamado_ok=1'); exit;
    }
}
