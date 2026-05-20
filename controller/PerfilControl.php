<?php

require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ChamadoDAO.php';
require_once __DIR__ . '/../model/dao/AvaliacaoDAO.php';
require_once __DIR__ . '/../includes/helpers.php';

class PerfilControl
{
    private ClienteDAO   $clienteDAO;
    private TecnicoDAO   $tecnicoDAO;
    private ChamadoDAO   $chamadoDAO;
    private AvaliacaoDAO $avaliacaoDAO;

    public string  $usuarioTipo  = '';
    public int     $usuarioId    = 0;
    public ?object $usuario      = null;
    public array   $historico    = [];
    public array   $avaliacoes   = [];
    public string  $cpfMascara   = '';
    public string  $titulo       = '';
    public string  $mensagem     = '';
    public string  $erro         = '';

    public function __construct()
    {
        $this->clienteDAO   = new ClienteDAO();
        $this->tecnicoDAO   = new TecnicoDAO();
        $this->chamadoDAO   = new ChamadoDAO();
        $this->avaliacaoDAO = new AvaliacaoDAO();
    }

    public function verificarSessao(): void
    {
        if (isset($_SESSION['tecnico_id'])) {
            $this->usuarioTipo = 'prestador';
            $this->usuarioId   = (int)$_SESSION['tecnico_id'];
            $this->titulo      = 'Perfil do prestador';
        } elseif (isset($_SESSION['cliente_id'])) {
            $this->usuarioTipo = 'cliente';
            $this->usuarioId   = (int)$_SESSION['cliente_id'];
            $this->titulo      = 'Perfil do cliente';
        } else {
            header('Location: ../view/login.php'); exit;
        }
    }

    public function processar(): void
    {
        $this->verificarSessao();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao = $_POST['acao'] ?? '';
            if ($acao === 'atualizar') $this->atualizarDados();
            elseif ($acao === 'senha') $this->alterarSenha();
        }

        $this->carregarUsuario();
        $this->historico  = $this->chamadoDAO->listarHistoricoResumido($this->usuarioTipo, $this->usuarioId);
        $this->avaliacoes = $this->usuarioTipo === 'prestador'
            ? $this->avaliacaoDAO->listarPorTecnico($this->usuarioId)
            : $this->avaliacaoDAO->listarPorCliente($this->usuarioId);
    }

    private function carregarUsuario(): void
    {
        if ($this->usuarioTipo === 'prestador') {
            $dto = $this->tecnicoDAO->buscarPorId($this->usuarioId);
            if ($dto) {
                $this->usuario = $dto;
                $cpf = $dto->cpf ?? '';
                $this->cpfMascara = $cpf && strlen($cpf) === 11
                    ? substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2)
                    : $cpf;
            }
        } else {
            $dto = $this->clienteDAO->buscarPorId($this->usuarioId);
            if ($dto) {
                $this->usuario = $dto;
                $cpf = $dto->cpf ?? '';
                $this->cpfMascara = $cpf && strlen($cpf) === 11
                    ? substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2)
                    : $cpf;
            }
        }
    }

    private function atualizarDados(): void
    {
        $nome     = trim($_POST['nome']     ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $genero   = $_POST['genero']         ?? '';
        if (!$nome || !$telefone) {
            $this->erro = 'Nome e telefone são obrigatórios.'; return;
        }

        $fotoPath = null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            $fotoPath = fixnow_upload_image($_FILES['foto_perfil'], fixnow_public_perfil_dir(), 'perfil');
            if (!$fotoPath) {
                $this->erro = 'Foto inválida. Use JPG, PNG ou WEBP.'; return;
            }
        }

        if ($this->usuarioTipo === 'prestador') {
            $especialidade = trim($_POST['especialidade'] ?? '');
            $dto = $this->tecnicoDAO->buscarPorId($this->usuarioId);
            if (!$dto) return;
            $dto->nome        = $nome;
            $dto->telefone    = $telefone;
            $dto->genero      = $genero ?: $dto->genero;
            $dto->especialidade = $especialidade;
            if ($fotoPath) $dto->fotoPerfil = $fotoPath;
            $this->tecnicoDAO->atualizar($dto);
            $_SESSION['tecnico_nome'] = $nome;
            if ($fotoPath) $_SESSION['tecnico_foto'] = $fotoPath;
        } else {
            $endereco = trim($_POST['endereco'] ?? '');
            $cep      = trim($_POST['cep']      ?? '');
            $dto = $this->clienteDAO->buscarPorId($this->usuarioId);
            if (!$dto) return;
            $dto->nome     = $nome;
            $dto->telefone = $telefone;
            $dto->genero   = $genero ?: $dto->genero;
            $dto->endereco = $endereco;
            $dto->cep      = $cep;
            if ($fotoPath) $dto->fotoPerfil = $fotoPath;
            $this->clienteDAO->atualizar($dto);
            $_SESSION['cliente_nome']  = $nome;
            $_SESSION['cliente_genero'] = $dto->genero;
            if ($fotoPath) $_SESSION['cliente_foto'] = $fotoPath;
        }
        $this->mensagem = 'Dados atualizados com sucesso.';
    }

    private function alterarSenha(): void
    {
        $atual    = $_POST['senha_atual']          ?? '';
        $nova     = $_POST['nova_senha']           ?? '';
        $confirma = $_POST['confirmar_nova_senha'] ?? '';

        if (!$atual || !$nova || !$confirma) {
            $this->erro = 'Preencha todos os campos de senha.'; return;
        }
        if (strlen($nova) < 6) {
            $this->erro = 'A nova senha deve ter ao menos 6 caracteres.'; return;
        }
        if ($nova !== $confirma) {
            $this->erro = 'As senhas não conferem.'; return;
        }

        if ($this->usuarioTipo === 'prestador') {
            $dto = $this->tecnicoDAO->buscarPorId($this->usuarioId);
            if (!$dto || !password_verify($atual, $dto->senha)) {
                $this->erro = 'Senha atual incorreta.'; return;
            }
            $this->tecnicoDAO->atualizarSenha($this->usuarioId, password_hash($nova, PASSWORD_DEFAULT));
        } else {
            $dto = $this->clienteDAO->buscarPorId($this->usuarioId);
            if (!$dto || !password_verify($atual, $dto->senha)) {
                $this->erro = 'Senha atual incorreta.'; return;
            }
            $this->clienteDAO->atualizarSenha($this->usuarioId, password_hash($nova, PASSWORD_DEFAULT));
        }
        $this->mensagem = 'Senha alterada com sucesso.';
    }
}
