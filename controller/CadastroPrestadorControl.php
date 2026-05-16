<?php

require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ClienteDAO.php';
require_once __DIR__ . '/../model/dto/TecnicoDTO.php';
require_once __DIR__ . '/../includes/helpers.php';

class CadastroPrestadorControl
{
    private TecnicoDAO $tecnicoDAO;
    private ClienteDAO $clienteDAO;

    public string $erro     = '';
    public string $mensagem = '';

    public function __construct()
    {
        $this->tecnicoDAO = new TecnicoDAO();
        $this->clienteDAO = new ClienteDAO();
    }

    public function processar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (empty($_POST['aceitar_termos'])) {
            $this->erro = 'Você precisa aceitar os Termos de Uso e a Política de Privacidade para criar uma conta.'; return;
        }

        $nome         = trim($_POST['nome']      ?? '');
        $email        = trim($_POST['email']     ?? '');
        $senha        = $_POST['senha']          ?? '';
        $confirma     = $_POST['confirmar_senha'] ?? '';
        $telefone     = trim($_POST['telefone']  ?? '');
        $especialidade = trim($_POST['especialidade'] ?? '');
        $genero       = $_POST['genero']         ?? 'Masculino';
        $cpf          = fixnow_only_digits(trim($_POST['cpf'] ?? ''));
        $generosOk    = ['Feminino', 'Masculino', 'Outro'];

        if (!$nome || !$email || !$senha || !$telefone) {
            $this->erro = 'Preencha todos os campos obrigatórios.'; return;
        }
        if (empty($_FILES['foto_perfil']['name'])) {
            $this->erro = 'A foto de perfil é obrigatória.'; return;
        }
        if (empty($_FILES['documento']['name'])) {
            $this->erro = 'O documento de identidade (RG ou CNH) é obrigatório.'; return;
        }
        if ($cpf && (strlen($cpf) !== 11 || !fixnow_validar_cpf($cpf))) {
            $this->erro = 'Informe um CPF válido.'; return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->erro = 'Informe um e-mail válido.'; return;
        }
        if (strlen($senha) < 6) {
            $this->erro = 'A senha deve ter ao menos 6 caracteres.'; return;
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            $this->erro = 'A senha deve conter ao menos uma letra maiúscula.'; return;
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
            $this->erro = 'A senha deve conter ao menos um caractere especial (!@#$%...).'; return;
        }
        if ($senha !== $confirma) {
            $this->erro = 'As senhas não conferem.'; return;
        }
        if (!in_array($genero, $generosOk, true)) {
            $this->erro = 'Selecione um gênero válido.'; return;
        }
        if ($this->tecnicoDAO->emailExiste($email) || $this->clienteDAO->emailExiste($email)) {
            $this->erro = 'Este e-mail já está em uso.'; return;
        }
        if ($cpf && ($this->tecnicoDAO->cpfExiste($cpf) || $this->clienteDAO->cpfExiste($cpf))) {
            $this->erro = 'Este CPF já está cadastrado.'; return;
        }

        $fotoPath = fixnow_upload_image($_FILES['foto_perfil'], fixnow_public_perfil_dir(), 'perfil_tec');
        if ($fotoPath === null) {
            $this->erro = 'Foto inválida. Envie JPG, PNG ou WEBP.'; return;
        }

        $docDir  = __DIR__ . '/../assets/img/documentos/';
        if (!is_dir($docDir)) mkdir($docDir, 0755, true);
        $docPath = fixnow_upload_image($_FILES['documento'], $docDir, 'doc_tec');
        if ($docPath === null) {
            $this->erro = 'Documento inválido. Envie JPG, PNG ou WEBP.'; return;
        }

        $dto               = new TecnicoDTO();
        $dto->nome         = $nome;
        $dto->email        = $email;
        $dto->senha        = password_hash($senha, PASSWORD_DEFAULT);
        $dto->cpf          = $cpf ?: '';
        $dto->telefone     = $telefone;
        $dto->especialidade = $especialidade;
        $dto->genero       = $genero;
        $dto->fotoPerfil   = $fotoPath;
        $dto->documentoPath = $docPath;
        $dto->statusCadastro = 'Pendente';

        $this->tecnicoDAO->inserir($dto);

        $pdo = \Conexao::getConexao();
        fixnow_notificar_admin($pdo, "Novo prestador aguardando aprovação: {$nome}");

        $this->mensagem = 'Cadastro realizado! Aguarde a aprovação do administrador.';
    }
}
