<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class SolicitarChamadoControl
{
    private ChamadoDAO   $chamadoDAO;
    private CategoriaDAO $categoriaDAO;
    private TecnicoDAO   $tecnicoDAO;
    private PDO          $pdo;

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
        $this->pdo          = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->clienteId     = (int)$_SESSION['cliente_id'];
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
        $stmt = $this->pdo->prepare("SELECT nome, foto_perfil, endereco, cep, genero FROM cliente WHERE id = ?");
        $stmt->execute([$this->clienteId]);
        $row = $stmt->fetch();
        if ($row) {
            $this->clienteNome     = $this->clienteNome     ?: ($row['nome']        ?? '');
            $this->clienteFoto     = $this->clienteFoto     ?: ($row['foto_perfil'] ?? '');
            $this->clienteEndereco = $row['endereco'] ?? '';
            $this->clienteCep      = $row['cep']      ?? '';
            if (!$this->clienteGenero) $this->clienteGenero = $row['genero'] ?? '';
        }

        try {
            $stmt2 = $this->pdo->prepare("SELECT COUNT(*) FROM notificacao WHERE cliente_id = ? AND tipo_destinatario = 'cliente' AND lida = 0");
            $stmt2->execute([$this->clienteId]);
            $this->naoLidas = (int)$stmt2->fetchColumn();
        } catch (Throwable $e) {
            $this->naoLidas = 0;
        }
    }

    private function carregarTecnicoESlots(int $tecnicoId): void
    {
        $stmt = $this->pdo->prepare("SELECT id, nome, especialidade, foto_perfil FROM tecnico WHERE id = ? AND ativo = 1 AND status_cadastro = 'Aprovado'");
        $stmt->execute([$tecnicoId]);
        $info = $stmt->fetch() ?: null;
        if (!$info) return;

        $this->tecnicoInfo = $info;

        // Categorias dos serviços ativos do prestador
        $stmtCat = $this->pdo->prepare("
            SELECT DISTINCT COALESCE(cat.nome, s.nome) AS categoria_nome
            FROM servico s
            LEFT JOIN categoria cat ON cat.id = s.categoria_id
            WHERE s.tecnico_id = ? AND s.ativo = 1
            ORDER BY categoria_nome
        ");
        $stmtCat->execute([$tecnicoId]);
        $this->categoriasPrestador = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

        // Auto-preenche se o prestador tem só uma categoria
        if (count($this->categoriasPrestador) === 1 && $this->categoriaPre === '') {
            $this->categoriaPre = $this->categoriasPrestador[0];
        }

        // Horários manualmente bloqueados pelo prestador
        $stmtBloq = $this->pdo->prepare("
            SELECT data, TIME_FORMAT(hora,'%H:%i') AS hora
            FROM disponibilidade
            WHERE tecnico_id = ? AND data >= CURDATE() AND data <= DATE_ADD(CURDATE(), INTERVAL 28 DAY)
        ");
        $stmtBloq->execute([$tecnicoId]);
        $ocupados = [];
        foreach ($stmtBloq->fetchAll() as $b) {
            $ocupados[$b['data']][$b['hora']] = true;
        }

        // Horários já reservados por chamados ativos
        $stmtChamados = $this->pdo->prepare("
            SELECT DATE(data_agendamento) AS data,
                   TIME_FORMAT(data_agendamento,'%H:%i') AS hora
            FROM chamado
            WHERE tecnico_id = ?
              AND status IN ('Pendente','Em Andamento')
              AND data_agendamento >= NOW()
              AND data_agendamento <= DATE_ADD(NOW(), INTERVAL 28 DAY)
        ");
        $stmtChamados->execute([$tecnicoId]);
        foreach ($stmtChamados->fetchAll() as $c) {
            $ocupados[$c['data']][$c['hora']] = true;
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
                if (!isset($ocupados[$dataStr][$hora])) {
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

        // slot picker (solicitação direta com horário do prestador)
        $tecnicoIdPost   = (int)($_POST['tecnico_id_selecionado'] ?? 0);
        $slotSelecionado = trim($_POST['slot_selecionado'] ?? '');

        if ($tecnicoIdPost > 0 && $slotSelecionado !== '') {
            [$slotData, $slotHora] = array_pad(explode('|', $slotSelecionado), 2, '');

            // Verifica se o slot está bloqueado manualmente ou já reservado por outro chamado
            $bloqueado = $this->pdo->prepare("SELECT id FROM disponibilidade WHERE tecnico_id=? AND data=? AND hora=?");
            $bloqueado->execute([$tecnicoIdPost, $slotData, $slotHora . ':00']);

            $jaReservado = $this->pdo->prepare("
                SELECT id FROM chamado
                WHERE tecnico_id=? AND data_agendamento=? AND status IN ('Pendente','Em Andamento')
            ");
            $jaReservado->execute([$tecnicoIdPost, $slotData . ' ' . $slotHora . ':00']);

            if ($bloqueado->fetch() || $jaReservado->fetch()) {
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

        $chamadoId = $this->chamadoDAO->inserir([
            'cliente_id'              => $this->clienteId,
            'tecnico_id'              => $tecnicoId,
            'categoria'               => $categoria,
            'descricao'               => $descricao,
            'fotos'                   => $fotos,
            'endereco_servico'        => $endereco,
            'data_agendamento'        => $dataAgend,
            'prest_feminino' => $exigeMulher,
        ]);

        if ($tecnicoId) {
            fixnow_notificar_prestador($this->pdo, $tecnicoId,
                "Você recebeu uma solicitação direta de serviço (#$chamadoId)! Um cliente escolheu você especificamente. Acesse o painel para aceitar ou recusar.",
                $chamadoId);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT s.tecnico_id FROM servico s
                INNER JOIN categoria cat ON cat.id = s.categoria_id
                INNER JOIN tecnico t ON t.id = s.tecnico_id
                WHERE cat.nome = ? AND s.ativo = 1 AND t.ativo = 1
                  AND t.status_cadastro = 'Aprovado' AND t.destaque = 1
            ");
            $stmt->execute([$categoria]);
            foreach ($stmt->fetchAll() as $row) {
                fixnow_notificar_prestador($this->pdo, (int)$row['tecnico_id'],
                    "Novo chamado de {$categoria} disponível! Como prestador em destaque, você tem prioridade.", $chamadoId);
            }
        }

        header('Location: ../dashboardCliente.php?chamado_ok=1'); exit;
    }
}
