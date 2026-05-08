<?php

require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/helpers.php';

class SuporteControl
{
    private PDO $pdo;

    public string $usuarioTipo  = '';
    public int    $usuarioId    = 0;
    public string $usuarioNome  = '';
    public string $usuarioFoto  = '';
    public string $mensagem     = '';
    public string $erro         = '';
    public array  $tickets      = [];

    const CATEGORIAS  = ['Pagamento', 'Técnico', 'Conta', 'Outro'];
    const PRIORIDADES = ['Baixa', 'Normal', 'Alta', 'Urgente'];

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (isset($_SESSION['admin_id'])) {
            $this->usuarioTipo = 'admin';
            $this->usuarioId   = (int)$_SESSION['admin_id'];
            $this->usuarioNome = $_SESSION['admin_nome'] ?? 'Admin';
        } elseif (isset($_SESSION['tecnico_id'])) {
            $this->usuarioTipo = 'prestador';
            $this->usuarioId   = (int)$_SESSION['tecnico_id'];
            $this->usuarioNome = $_SESSION['tecnico_nome'] ?? 'Prestador';
            $this->usuarioFoto = $_SESSION['tecnico_foto'] ?? '';
        } elseif (isset($_SESSION['cliente_id'])) {
            $this->usuarioTipo = 'cliente';
            $this->usuarioId   = (int)$_SESSION['cliente_id'];
            $this->usuarioNome = $_SESSION['cliente_nome'] ?? 'Cliente';
            $this->usuarioFoto = $_SESSION['cliente_foto'] ?? '';
        } else {
            header('Location: ../view/login.php'); exit;
        }
    }

    public function processar(): void
    {
        $this->verificarSessao();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao = $_POST['acao'] ?? '';

            if ($acao === 'abrir') {
                $assunto    = trim($_POST['assunto']    ?? '');
                $texto      = trim($_POST['mensagem']   ?? '');
                $categoria  = in_array($_POST['categoria']  ?? '', self::CATEGORIAS,  true) ? $_POST['categoria']  : 'Outro';
                $prioridade = in_array($_POST['prioridade'] ?? '', self::PRIORIDADES, true) ? $_POST['prioridade'] : 'Normal';

                if (!$assunto || !$texto) {
                    $this->erro = 'Preencha assunto e mensagem.';
                } else {
                    try {
                        $this->pdo->beginTransaction();
                        $this->pdo->prepare("INSERT INTO suporte (tipo_usuario, usuario_id, assunto, categoria, prioridade, status) VALUES (?,?,?,?,?,'Aberto')")
                            ->execute([$this->usuarioTipo, $this->usuarioId, $assunto, $categoria, $prioridade]);
                        $sid = (int)$this->pdo->lastInsertId();
                        $this->pdo->prepare('INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,?,?,?)')
                            ->execute([$sid, $this->usuarioTipo, $this->usuarioId, $texto]);
                        $this->pdo->commit();
                        fixnow_notificar_admin($this->pdo, "Novo ticket #{$sid} [{$categoria} / {$prioridade}] de {$this->usuarioTipo}: {$assunto}");
                        $this->mensagem = 'Ticket aberto com sucesso. Responderemos em breve.';
                    } catch (Exception $e) {
                        $this->pdo->rollBack();
                        $this->erro = 'Erro ao criar ticket.';
                    }
                }

            } elseif ($acao === 'mensagem') {
                $sid   = (int)($_POST['suporte_id'] ?? 0);
                $texto = trim($_POST['mensagem'] ?? '');

                if ($sid > 0 && $texto !== '') {
                    $stk = $this->pdo->prepare('SELECT * FROM suporte WHERE id=? AND tipo_usuario=? AND usuario_id=?');
                    $stk->execute([$sid, $this->usuarioTipo, $this->usuarioId]);
                    $tk = $stk->fetch();
                    if ($tk && $tk['status'] !== 'Fechado') {
                        $this->pdo->prepare('INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,?,?,?)')
                            ->execute([$sid, $this->usuarioTipo, $this->usuarioId, $texto]);
                        $this->pdo->prepare("UPDATE suporte SET status='Em Andamento', atualizado_em=NOW() WHERE id=?")
                            ->execute([$sid]);
                        fixnow_notificar_admin($this->pdo, "Nova mensagem no ticket #{$sid} de {$this->usuarioTipo}.");
                        $this->mensagem = 'Mensagem enviada.';
                    }
                } else {
                    $this->erro = 'A mensagem não pode estar vazia.';
                }
            }
        }

        $stmt = $this->pdo->prepare('SELECT * FROM suporte WHERE tipo_usuario=? AND usuario_id=? ORDER BY criado_em DESC');
        $stmt->execute([$this->usuarioTipo, $this->usuarioId]);
        $tickets = $stmt->fetchAll();

        if ($tickets) {
            $ids = array_column($tickets, 'id');
            $ph  = implode(',', array_fill(0, count($ids), '?'));
            $msgs = $this->pdo->prepare("SELECT * FROM suporte_mensagem WHERE suporte_id IN ($ph) ORDER BY criado_em ASC");
            $msgs->execute($ids);
            $byTicket = [];
            foreach ($msgs->fetchAll() as $m) { $byTicket[$m['suporte_id']][] = $m; }
            foreach ($tickets as &$tk) { $tk['mensagens'] = $byTicket[$tk['id']] ?? []; }
            unset($tk);
        }

        $this->tickets = $tickets;
    }
}
