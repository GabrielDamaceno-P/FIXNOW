<?php

require_once __DIR__ . '/../model/dao/ServicoDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dto/ServicoDTO.php';

class ServicosControl
{
    private ServicoDAO   $servicoDAO;
    private CategoriaDAO $categoriaDAO;
    private PDO          $pdo;

    public int    $tecnicoId  = 0;
    public int    $naoLidas   = 0;
    public string $mensagem   = '';
    public string $erro       = '';
    public array  $servicos   = [];
    public array  $categorias = [];

    public function __construct()
    {
        $this->servicoDAO   = new ServicoDAO();
        $this->categoriaDAO = new CategoriaDAO();
        $this->pdo          = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];
    }

    private function carregarNaoLidas(): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notificacao WHERE tecnico_id = ? AND tipo_destinatario = 'prestador' AND lida = 0"
        );
        $stmt->execute([$this->tecnicoId]);
        $this->naoLidas = (int)$stmt->fetchColumn();
    }

    public function processar(): void
    {
        $this->verificarSessao();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPost();
        }

        $this->servicos   = $this->servicoDAO->listarPorTecnico($this->tecnicoId);
        $this->categorias = $this->categoriaDAO->listarAtivas();
        $this->carregarNaoLidas();
    }

    private function processarPost(): void
    {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'salvar') {
            $nome       = trim($_POST['nome']       ?? '');
            $catId      = (int)($_POST['categoria_id'] ?? 0) ?: null;
            $descricao  = trim($_POST['descricao']   ?? '');
            $ativo      = (int)($_POST['ativo']      ?? 1);
            $sid        = (int)($_POST['servico_id'] ?? 0);

            if (!$nome) { $this->erro = 'O nome do serviço é obrigatório.'; return; }

            $dto              = new ServicoDTO();
            $dto->tecnicoId   = $this->tecnicoId;
            $dto->categoriaId = $catId;
            $dto->nome        = $nome;
            $dto->descricao   = $descricao;
            $dto->ativo       = $ativo;

            if ($sid > 0) {
                $dto->id = $sid;
                $this->servicoDAO->atualizar($dto);
                $this->mensagem = 'Serviço atualizado com sucesso.';
            } else {
                $this->servicoDAO->inserir($dto);
                $this->mensagem = 'Serviço cadastrado com sucesso.';
            }

        } elseif ($acao === 'excluir') {
            $sid = (int)($_POST['servico_id'] ?? 0);
            if ($sid > 0 && $this->servicoDAO->excluir($sid, $this->tecnicoId)) {
                $this->mensagem = 'Serviço removido.';
            } else {
                $this->erro = 'Não foi possível remover o serviço.';
            }
        }
    }
}
