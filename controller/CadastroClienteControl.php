<?php

require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dto/ClienteDTO.php';
require_once __DIR__ . '/../includes/helpers.php';

class CadastroClienteControl
{
    private ClienteDAO $clienteDAO;
    private TecnicoDAO $tecnicoDAO;

    public string $erro     = '';
    public string $mensagem = '';

    public function __construct()
    {
        $this->clienteDAO = new ClienteDAO();
        $this->tecnicoDAO = new TecnicoDAO();
    }

    public function processar(): void
    {
        if (isset($_GET['cadastro_ok'])) {
            $this->mensagem = 'Cadastro realizado com sucesso! Faça login para continuar.';
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $nome     = trim($_POST['nome']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $senha    = $_POST['senha']         ?? '';
        $confirma = $_POST['confirmar_senha'] ?? '';
        $telefone = trim($_POST['telefone'] ?? '');
        $cep      = trim($_POST['cep']      ?? '');
        $logradouro = trim($_POST['logradouro'] ?? '');
        $bairro   = trim($_POST['bairro']   ?? '');
        $cidade   = trim($_POST['cidade']   ?? '');
        $estado   = trim($_POST['estado']   ?? '');
        $cpf      = fixnow_only_digits(trim($_POST['cpf'] ?? ''));
        $genero   = $_POST['genero']        ?? 'Prefiro não informar';
        $generosOk = ['Feminino', 'Masculino', 'Outro', 'Prefiro não informar'];

        if (!$nome || !$email || !$senha || !$telefone || !$cep || !$logradouro || !$bairro || !$cidade || !$estado) {
            $this->erro = 'Preencha todos os campos obrigatórios.'; return;
        }
        if (empty($_FILES['foto_perfil']['name'])) {
            $this->erro = 'A foto de perfil é obrigatória.'; return;
        }
        if (strlen($cpf) !== 11 || !fixnow_validar_cpf($cpf)) {
            $this->erro = 'Informe um CPF válido.'; return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->erro = 'Informe um e-mail válido.'; return;
        }
        if (strlen($senha) < 6) {
            $this->erro = 'A senha deve ter ao menos 6 caracteres.'; return;
        }
        if ($senha !== $confirma) {
            $this->erro = 'As senhas não conferem.'; return;
        }
        if (!in_array($genero, $generosOk, true)) {
            $this->erro = 'Selecione um gênero válido.'; return;
        }
        if ($this->clienteDAO->emailExiste($email) || $this->tecnicoDAO->emailExiste($email)) {
            $this->erro = 'Este e-mail já está em uso.'; return;
        }
        if ($this->clienteDAO->cpfExiste($cpf) || $this->tecnicoDAO->cpfExiste($cpf)) {
            $this->erro = 'Este CPF já está cadastrado.'; return;
        }

        $fotoPath = fixnow_upload_image($_FILES['foto_perfil'], fixnow_public_perfil_dir(), 'perfil_cli');
        if ($fotoPath === null) {
            $this->erro = 'Foto inválida. Envie JPG, PNG ou WEBP.'; return;
        }

        $dto             = new ClienteDTO();
        $dto->nome       = $nome;
        $dto->email      = $email;
        $dto->senha      = password_hash($senha, PASSWORD_DEFAULT);
        $dto->cpf        = $cpf;
        $dto->telefone   = $telefone;
        $dto->endereco   = implode(', ', array_filter([$logradouro, $bairro, $cidade, $estado]));
        $dto->cep        = $cep;
        $dto->fotoPerfil = $fotoPath;
        $dto->genero     = $genero;

        $novoId = $this->clienteDAO->inserir($dto);

        session_regenerate_id(true);
        $_SESSION['cliente_id']     = $novoId;
        $_SESSION['cliente_nome']   = $nome;
        $_SESSION['cliente_genero'] = $genero;
        $_SESSION['cliente_foto']   = $fotoPath;
        header('Location: dashboardCliente.php?cadastro_ok=1');
        exit;
    }
}
