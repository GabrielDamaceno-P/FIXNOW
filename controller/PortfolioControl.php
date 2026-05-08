<?php

require_once __DIR__ . '/../model/dao/PortfolioDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class PortfolioControl
{
    private PortfolioDAO $portfolioDAO;
    private PDO          $pdo;

    public int    $tecnicoId  = 0;
    public int    $naoLidas   = 0;
    public string $mensagem   = '';
    public string $erro       = '';
    public array  $fotos      = [];
    public array  $categorias = [];

    public function __construct()
    {
        $this->portfolioDAO = new PortfolioDAO();
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

        $this->fotos = $this->portfolioDAO->listarPorTecnico($this->tecnicoId);

        // Categorias dos serviços ativos do prestador
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT cat.id, COALESCE(cat.nome, s.nome) AS nome
            FROM servico s
            LEFT JOIN categoria cat ON cat.id = s.categoria_id
            WHERE s.tecnico_id = ? AND s.ativo = 1
            ORDER BY nome
        ");
        $stmt->execute([$this->tecnicoId]);
        $this->categorias = $stmt->fetchAll();

        $this->carregarNaoLidas();
    }

    private function processarPost(): void
    {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'adicionar') {
            if (empty($_FILES['fotos']['name'][0])) {
                $this->erro = 'Selecione pelo menos uma foto.'; return;
            }
            $titulo      = trim($_POST['titulo']       ?? '');
            $descricao   = trim($_POST['descricao']    ?? '');
            $categoriaId = (int)($_POST['categoria_id'] ?? 0) ?: null;
            $total       = count($_FILES['fotos']['name']);
            $adicionadas = 0;
            for ($i = 0; $i < min($total, 10); $i++) {
                if ($_FILES['fotos']['error'][$i] !== 0) continue;
                $fileItem = [
                    'name'     => $_FILES['fotos']['name'][$i],
                    'type'     => $_FILES['fotos']['type'][$i],
                    'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                    'error'    => $_FILES['fotos']['error'][$i],
                    'size'     => $_FILES['fotos']['size'][$i],
                ];
                $path = fixnow_upload_image($fileItem, fixnow_public_upload_dir(), 'portfolio');
                if ($path) {
                    $this->portfolioDAO->inserir($this->tecnicoId, $path, $titulo ?: null, $descricao ?: null, $categoriaId);
                    $adicionadas++;
                }
            }
            if ($adicionadas === 0) { $this->erro = 'Nenhuma foto válida. Use JPG, PNG ou WEBP.'; return; }
            $this->mensagem = $adicionadas === 1 ? 'Foto adicionada ao portfólio.' : "$adicionadas fotos adicionadas ao portfólio.";

        } elseif ($acao === 'editar') {
            $fid         = (int)($_POST['foto_id']      ?? 0);
            $titulo      = trim($_POST['titulo']         ?? '');
            $descricao   = trim($_POST['descricao']      ?? '');
            $categoriaId = (int)($_POST['categoria_id']  ?? 0) ?: null;
            if ($fid > 0) {
                $this->portfolioDAO->atualizar($fid, $this->tecnicoId, $titulo, $descricao, $categoriaId);
                $this->mensagem = 'Foto atualizada.';
            }

        } elseif ($acao === 'excluir') {
            $fid = (int)($_POST['foto_id'] ?? 0);
            if ($fid > 0) {
                $fotoPath = $this->portfolioDAO->excluir($fid, $this->tecnicoId);
                if ($fotoPath) {
                    $fsPath = dirname(__DIR__) . '/' . $fotoPath;
                    if (is_file($fsPath)) @unlink($fsPath);
                    $this->mensagem = 'Foto removida.';
                } else {
                    $this->erro = 'Foto não encontrada.';
                }
            }
        }
    }
}
