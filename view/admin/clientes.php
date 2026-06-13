<?php
session_start();
$paginaAtiva = 'clientes';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

$mensagem          = '';
$erro              = '';
$abrirModal        = '';
$senhaGerada       = '';
$clienteCadastrado = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'excluir';

    if ($acao === 'limpar_senha_temp') {
        unset($_SESSION['admin_temp_senha_cliente']);

    } elseif ($acao === 'toggle_ativo' && $isMaster) {
        $cid = (int)($_POST['cliente_id'] ?? 0);
        if ($cid > 0) {
            $pdo->prepare('UPDATE cliente SET ativo = 1 - ativo WHERE id = ?')->execute([$cid]);
            $mensagem = 'Status do cliente atualizado.';
        }

    } elseif ($acao === 'cadastrar_cliente') {
        $nome     = trim($_POST['c_nome']     ?? '');
        $email    = strtolower(trim($_POST['c_email'] ?? ''));
        $telefone = trim($_POST['c_telefone'] ?? '');
        $genero   = $_POST['c_genero']  ?? 'Prefiro não informar';
        $cpf      = preg_replace('/\D/', '', $_POST['c_cpf']      ?? '');
        $cep      = preg_replace('/\D/', '', $_POST['c_cep']      ?? '');
        $endereco = trim($_POST['c_endereco'] ?? '');
        $abrirModal = 'modalNovoCliente';

        if (!$nome || !$email || !$telefone) {
            $erro = 'Preencha nome, e-mail e telefone.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM cliente WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $erro = 'Este e-mail já está cadastrado.';
            } else {
                $senhaTemp = bin2hex(random_bytes(5));
                $hash = password_hash($senhaTemp, PASSWORD_BCRYPT);
                $foto = 'assets/img/perfil/default-cliente.jpg';
                $pdo->prepare("INSERT INTO cliente (nome, email, senha, telefone, genero, cpf, cep, endereco, foto_perfil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$nome, $email, $hash, $telefone, $genero,
                               $cpf ?: null, $cep ?: '', $endereco ?: '', $foto]);
                $senhaGerada       = $senhaTemp;
                $clienteCadastrado = ['nome' => $nome, 'email' => $email];
                $_SESSION['admin_temp_senha_cliente'] = ['nome' => $nome, 'email' => $email, 'senha' => $senhaTemp];
                $abrirModal        = 'modalSenhaTemp';
            }
        }
    }
}

$tempSenhaCliente = $_SESSION['admin_temp_senha_cliente'] ?? [];

$filtro = trim($_GET['busca'] ?? '');
if ($filtro !== '') {
    $like = '%' . $filtro . '%';
    $stmt = $pdo->prepare("SELECT id, nome, email, telefone, genero, endereco, cep, ativo, criado_em FROM cliente WHERE nome LIKE ? OR email LIKE ? ORDER BY nome ASC");
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT id, nome, email, telefone, genero, endereco, cep, ativo, criado_em FROM cliente ORDER BY nome ASC");
}
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clientes - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .admin-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
    [data-theme="dark"] .admin-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>👥 Clientes</h1>
      <p>Visualize, cadastre e remova contas de clientes da plataforma.</p>
    </div>
  </div>

  <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($erro) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($tempSenhaCliente && !$senhaGerada): ?>
  <div class="d-flex align-items-center gap-3 p-3 mb-3 flex-wrap" style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;">
    <span style="font-size:1.2rem;">🔑</span>
    <div style="flex:1;min-width:0;">
      <strong style="font-size:.9rem;">Senha temporária pendente</strong>
      <div class="text-muted" style="font-size:.82rem;">
        <?= htmlspecialchars($tempSenhaCliente['nome']) ?> — <?= htmlspecialchars($tempSenhaCliente['email']) ?>
      </div>
    </div>
    <button class="btn btn-sm btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSenhaTemp">
      👁 Ver senha
    </button>
    <form method="post" class="d-inline">
      <input type="hidden" name="acao" value="limpar_senha_temp">
      <button type="submit" class="btn btn-sm btn-outline-secondary">✓ Já anotei</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="secao-titulo">
      <span>📋</span>
      <span>Clientes cadastrados</span>
      <span class="badge" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem;"><?= count($clientes) ?></span>
      <div class="ms-auto d-flex gap-2 flex-wrap align-items-center">
        <form method="get" class="d-flex gap-2">
          <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome ou e-mail…"
            value="<?= htmlspecialchars($filtro) ?>" style="width:200px;">
          <button class="btn btn-sm btn-outline-secondary">Buscar</button>
          <?php if ($filtro): ?>
            <a href="clientes.php" class="btn btn-sm btn-outline-danger">Limpar</a>
          <?php endif; ?>
        </form>
        <button class="btn btn-sm btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
          + Novo Cliente
        </button>
      </div>
    </div>

    <?php if (!$clientes): ?>
      <div class="text-center py-5 text-muted">
        <div style="font-size:2.5rem;margin-bottom:.5rem;">🔍</div>
        Nenhum cliente encontrado.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
          <thead class="table-primary">
            <tr>
              <th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Gênero</th>
              <th>Endereço</th><th>CEP</th><th>Situação</th><th>Cadastrado em</th>
              <?php if ($isMaster): ?><th></th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($clientes as $cl): ?>
            <tr>
              <td class="text-muted" style="font-size:.82rem;"><?= (int)$cl['id'] ?></td>
              <td class="fw-semibold" style="font-size:.88rem;"><?= htmlspecialchars($cl['nome']) ?></td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($cl['email']) ?></td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($cl['telefone']) ?></td>
              <td>
                <?php
                  $genColor = match($cl['genero'] ?? '') {
                    'Feminino'  => 'background:#fce7f3;color:#be185d;',
                    'Masculino' => 'background:#eff6ff;color:#1d4ed8;',
                    default     => 'background:#f3f4f6;color:#374151;'
                  };
                ?>
                <span class="badge" style="font-size:.72rem;<?= $genColor ?>"><?= htmlspecialchars($cl['genero']) ?></span>
              </td>
              <td class="text-muted" style="font-size:.82rem;"><?= htmlspecialchars($cl['endereco'] ?: '—') ?></td>
              <td class="text-muted" style="font-size:.82rem;"><?= htmlspecialchars($cl['cep'] ?: '—') ?></td>
              <td>
                <?php if ($cl['ativo']): ?>
                  <span class="badge" style="font-size:.72rem;background:#dcfce7;color:#16a34a;">Ativo</span>
                <?php else: ?>
                  <span class="badge" style="font-size:.72rem;background:#fee2e2;color:#dc2626;">Inativo</span>
                <?php endif; ?>
              </td>
              <td class="text-muted" style="font-size:.78rem;"><?= date('d/m/Y', strtotime($cl['criado_em'])) ?></td>
              <?php if ($isMaster): ?>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="acao" value="toggle_ativo">
                  <input type="hidden" name="cliente_id" value="<?= (int)$cl['id'] ?>">
                  <?php if ($cl['ativo']): ?>
                    <button class="btn btn-sm btn-outline-danger" style="font-size:.78rem;">Desativar</button>
                  <?php else: ?>
                    <button class="btn btn-sm btn-outline-success" style="font-size:.78rem;">Ativar</button>
                  <?php endif; ?>
                </form>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>

<!-- Modal Novo Cliente -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header" style="background:linear-gradient(135deg,#0d1b3d,#1a2b63);color:#fff;">
        <h5 class="modal-title">👤 Cadastrar Novo Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="acao" value="cadastrar_cliente">
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Nome completo <span class="text-danger">*</span></label>
            <input type="text" name="c_nome" class="form-control" required maxlength="120" placeholder="Nome do cliente">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
            <input type="email" name="c_email" class="form-control" required maxlength="150" placeholder="cliente@email.com">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Telefone <span class="text-danger">*</span></label>
            <input type="text" name="c_telefone" class="form-control" required maxlength="15" inputmode="numeric" placeholder="(11) 99999-9999">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Gênero</label>
            <select name="c_genero" class="form-select">
              <option value="Prefiro não informar">Prefiro não informar</option>
              <option value="Feminino">Feminino</option>
              <option value="Masculino">Masculino</option>
              <option value="Outro">Outro</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">CPF <span class="text-muted fw-normal">(opcional)</span></label>
            <input type="text" name="c_cpf" class="form-control" maxlength="14" placeholder="000.000.000-00">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">CEP <span class="text-muted fw-normal">(opcional)</span></label>
            <input type="text" name="c_cep" class="form-control" maxlength="9" placeholder="00000-000">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Endereço <span class="text-muted fw-normal">(opcional)</span></label>
            <input type="text" name="c_endereco" class="form-control" maxlength="200" placeholder="Rua, nº, bairro…">
          </div>
          <div class="col-12">
            <div class="alert alert-info mb-0 py-2" style="font-size:.85rem;">
              🔑 Uma <strong>senha temporária aleatória</strong> será gerada. Anote e repasse ao cliente — ele poderá alterá-la no perfil.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning fw-bold">Cadastrar Cliente</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Senha Temporária -->
<?php
$_mdNome  = $clienteCadastrado['nome']  ?? $tempSenhaCliente['nome']  ?? '';
$_mdEmail = $clienteCadastrado['email'] ?? $tempSenhaCliente['email'] ?? '';
$_mdSenha = $senhaGerada                ?: ($tempSenhaCliente['senha'] ?? '');
?>
<div class="modal fade" id="modalSenhaTemp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content overflow-hidden">
      <div style="background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);padding:1.3rem 1.5rem;">
        <h5 class="text-white fw-bold mb-0">✅ Cliente cadastrado com sucesso</h5>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-3" style="font-size:.9rem;">
          Repasse as credenciais abaixo ao cliente — por WhatsApp, e-mail ou pessoalmente.
        </p>

        <div class="mb-3">
          <div class="form-label fw-semibold" style="font-size:.83rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Cliente</div>
          <div class="fw-bold"><?= htmlspecialchars($_mdNome) ?></div>
          <div class="text-muted" style="font-size:.9rem;"><?= htmlspecialchars($_mdEmail) ?></div>
        </div>

        <div class="mb-1">
          <div class="form-label fw-semibold" style="font-size:.83rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Senha temporária</div>
          <div class="d-flex align-items-center gap-2">
            <code id="senha-temp-val"
                  style="font-size:1.4rem;font-weight:900;letter-spacing:.15em;color:#0d1b3d;background:#f0f3fa;border-radius:8px;padding:.45rem 1rem;flex-grow:1;display:block;text-align:center;">
              <?= htmlspecialchars($_mdSenha) ?>
            </code>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-copiar-senha" title="Copiar senha" style="white-space:nowrap;">
              📋 Copiar
            </button>
          </div>
          <div class="form-text mt-1">O cliente poderá alterar a senha após o primeiro acesso.</div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 gap-2">
        <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Fechar</button>
        <form method="post" class="flex-fill">
          <input type="hidden" name="acao" value="limpar_senha_temp">
          <button type="submit" class="btn btn-warning fw-bold w-100">✓ Já anotei</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/forms-helpers.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var copiarBtn = document.getElementById('btn-copiar-senha');
  if (copiarBtn) {
    copiarBtn.addEventListener('click', function () {
      var texto = document.getElementById('senha-temp-val').textContent.trim();
      navigator.clipboard.writeText(texto).then(function () {
        copiarBtn.textContent = '✅ Copiado!';
        setTimeout(function () { copiarBtn.innerHTML = '📋 Copiar'; }, 2000);
      });
    });
  }

  var tel = document.querySelector('input[name="c_telefone"]');
  if (!tel) return;
  tel.addEventListener('input', function () {
    var d = this.value.replace(/\D/g, '').slice(0, 11);
    var out = '';
    if (d.length > 0)  out = '(' + d.slice(0, 2);
    if (d.length >= 2) out += ') ';
    if (d.length > 2)  out += d.slice(2, d.length > 10 ? 7 : 6);
    if (d.length > 10) out += '-' + d.slice(7, 11);
    else if (d.length > 6) out += '-' + d.slice(6, 10);
    this.value = out;
  });
});
</script>
<?php if ($abrirModal): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('<?= $abrirModal ?>');
  if (el) bootstrap.Modal.getOrCreateInstance(el).show();
});
</script>
<?php endif; ?>
</body>
</html>
