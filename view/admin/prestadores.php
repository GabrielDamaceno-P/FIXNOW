<?php
session_start();
$paginaAtiva = 'prestadores';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

$mensagem            = '';
$erro                = '';
$abrirModal          = '';
$senhaGerada         = '';
$prestadorCadastrado = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $tid  = (int)($_POST['tecnico_id'] ?? 0);

    if ($acao === 'limpar_senha_temp') {
        unset($_SESSION['admin_temp_senha_prestador']);

    } elseif ($acao === 'aprovar' && $tid > 0) {
        $up = $pdo->prepare("UPDATE tecnico SET ativo=1, status_cadastro='Aprovado' WHERE id=? AND status_cadastro='Pendente'");
        $up->execute([$tid]);
        if ($up->rowCount() > 0) {
            fixnow_notificar_prestador($pdo, $tid, 'Seu cadastro foi aprovado pela Fix Now! Você já pode acessar o painel e aceitar chamados.');
            $mensagem = 'Prestador aprovado.';
        } else { $erro = 'Não foi possível aprovar (cadastro já processado).'; }

    } elseif ($acao === 'recusar' && $tid > 0) {
        $up = $pdo->prepare("UPDATE tecnico SET ativo=0, status_cadastro='Recusado' WHERE id=? AND status_cadastro='Pendente'");
        $up->execute([$tid]);
        if ($up->rowCount() > 0) {
            fixnow_notificar_prestador($pdo, $tid, 'Seu cadastro foi recusado pela Fix Now. Entre em contato com o suporte.');
            $mensagem = 'Cadastro recusado.';
        } else { $erro = 'Não foi possível recusar (cadastro já processado).'; }

    } elseif ($acao === 'excluir' && $isMaster && $tid > 0) {
        $pdo->prepare('DELETE FROM tecnico WHERE id=?')->execute([$tid]);
        $mensagem = 'Prestador excluído.';

    } elseif ($acao === 'cadastrar_prestador') {
        $nome          = trim($_POST['p_nome']          ?? '');
        $email         = strtolower(trim($_POST['p_email'] ?? ''));
        $telefone      = trim($_POST['p_telefone']      ?? '');
        $genero        = $_POST['p_genero']        ?? 'Masculino';
        $especialidade = trim($_POST['p_especialidade'] ?? '');
        $cpf           = preg_replace('/\D/', '', $_POST['p_cpf'] ?? '');
        $abrirModal    = 'modalNovoPrestador';

        if (!$nome || !$email || !$telefone) {
            $erro = 'Preencha nome, e-mail e telefone.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM tecnico WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $erro = 'Este e-mail já está cadastrado.';
            } else {
                $senhaTemp = bin2hex(random_bytes(5));
                $hash = password_hash($senhaTemp, PASSWORD_BCRYPT);
                $foto = 'assets/img/perfil/default-prestador.jpg';
                $pdo->prepare("INSERT INTO tecnico (nome, email, senha, telefone, genero, especialidade, cpf, foto_perfil, ativo, status_cadastro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'Aprovado')")
                    ->execute([$nome, $email, $hash, $telefone, $genero,
                               $especialidade ?: null, $cpf ?: null, $foto]);
                $senhaGerada         = $senhaTemp;
                $prestadorCadastrado = ['nome' => $nome, 'email' => $email];
                $_SESSION['admin_temp_senha_prestador'] = ['nome' => $nome, 'email' => $email, 'senha' => $senhaTemp];
                $abrirModal          = 'modalSenhaTemp';
            }
        }
    }
}

$tempSenhaPrestador = $_SESSION['admin_temp_senha_prestador'] ?? [];

$filtro       = trim($_GET['busca'] ?? '');
$filtroStatus = $_GET['status'] ?? '';
$statusOpcoes = ['Pendente', 'Aprovado', 'Recusado'];

$where  = [];
$params = [];
if ($filtro !== '') {
    $where[] = '(t.nome LIKE ? OR t.email LIKE ?)';
    $like = '%' . $filtro . '%';
    $params[] = $like; $params[] = $like;
}
if (in_array($filtroStatus, $statusOpcoes, true)) {
    $where[] = 't.status_cadastro = ?';
    $params[] = $filtroStatus;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT t.id, t.nome, t.email, t.telefone, t.genero, t.avaliacao_media,
           t.ativo, t.status_cadastro, t.destaque, t.criado_em, t.documento_path,
           (SELECT GROUP_CONCAT(cat.nome ORDER BY cat.nome SEPARATOR ', ')
            FROM servico s INNER JOIN categoria cat ON cat.id=s.categoria_id
            WHERE s.tecnico_id=t.id AND s.ativo=1) AS categorias_servico
    FROM tecnico t $whereSQL
    ORDER BY t.status_cadastro ASC, t.nome ASC
");
$stmt->execute($params);
$tecnicos = $stmt->fetchAll();
$pendentesCount = count(array_filter($tecnicos, fn($t) => $t['status_cadastro'] === 'Pendente'));

$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Prestadores - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .admin-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
    .bg-pink{background-color:#e91e8c!important}
    [data-theme="dark"] .admin-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>🔧 Prestadores</h1>
      <p>Visualize, cadastre, aprove, recuse e remova prestadores da plataforma.</p>
    </div>
  </div>

  <?php if ($mensagem): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= htmlspecialchars($mensagem) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($erro) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($tempSenhaPrestador && !$senhaGerada): ?>
  <div class="d-flex align-items-center gap-3 p-3 mb-3 flex-wrap" style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;">
    <span style="font-size:1.2rem;">🔑</span>
    <div style="flex:1;min-width:0;">
      <strong style="font-size:.9rem;">Senha temporária pendente</strong>
      <div class="text-muted" style="font-size:.82rem;">
        <?= htmlspecialchars($tempSenhaPrestador['nome']) ?> — <?= htmlspecialchars($tempSenhaPrestador['email']) ?>
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

  <?php if ($pendentesCount > 0 && $filtroStatus !== 'Pendente'): ?>
    <div class="d-flex align-items-center gap-3 p-3 mb-4" style="background:rgba(251,191,36,.12);border:1.5px solid #fde68a;border-radius:14px;">
      <span style="font-size:1.4rem;">⚠️</span>
      <div class="flex-grow-1">
        <strong><?= $pendentesCount ?> prestador<?= $pendentesCount > 1 ? 'es' : '' ?> aguardando aprovação.</strong>
      </div>
      <a href="prestadores.php?status=Pendente" class="btn btn-sm btn-warning fw-semibold">Ver pendentes</a>
    </div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="secao-titulo">
      <span>📋</span>
      <span>Prestadores</span>
      <span class="badge" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem;"><?= count($tecnicos) ?></span>
      <div class="ms-auto d-flex gap-2 flex-wrap align-items-center">
        <form method="get" class="d-flex gap-2 flex-wrap">
          <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome ou e-mail…"
            value="<?= htmlspecialchars($filtro) ?>" style="width:170px;">
          <select name="status" class="form-select form-select-sm" style="width:140px;">
            <option value="">Todos os status</option>
            <?php foreach ($statusOpcoes as $st): ?>
              <option value="<?= $st ?>" <?= $filtroStatus===$st?'selected':'' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-secondary">Buscar</button>
          <?php if ($filtro || $filtroStatus): ?>
            <a href="prestadores.php" class="btn btn-sm btn-outline-danger">Limpar</a>
          <?php endif; ?>
        </form>
        <button class="btn btn-sm btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNovoPrestador">
          + Novo Prestador
        </button>
      </div>
    </div>

    <?php if (!$tecnicos): ?>
      <div class="text-center py-5 text-muted">
        <div style="font-size:2.5rem;margin-bottom:.5rem;">🔍</div>
        Nenhum prestador encontrado.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
          <thead class="table-primary">
            <tr>
              <th>#</th><th>Nome</th><th>E-mail</th><th>Gênero</th><th>Categorias</th>
              <th>Telefone</th><th>Avaliação</th><th>Status</th><th>Cadastrado em</th><th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($tecnicos as $t):
            $badgeSt = match($t['status_cadastro']) {
              'Aprovado' => 'background:#dcfce7;color:#16a34a;',
              'Pendente' => 'background:#fef9c3;color:#92400e;',
              default    => 'background:#fee2e2;color:#dc2626;'
            };
            $genColor = match($t['genero'] ?? '') {
              'Feminino'  => 'background:#fce7f3;color:#be185d;',
              'Masculino' => 'background:#eff6ff;color:#1d4ed8;',
              default     => 'background:#f3f4f6;color:#374151;'
            };
          ?>
            <tr>
              <td class="text-muted" style="font-size:.82rem;"><?= (int)$t['id'] ?></td>
              <td>
                <div class="fw-semibold" style="font-size:.88rem;"><?= htmlspecialchars($t['nome']) ?></div>
                <?php if ($t['destaque']): ?>
                  <span class="badge" style="font-size:.66rem;background:#fef9c3;color:#713f12;">★ Destaque</span>
                <?php endif; ?>
              </td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($t['email'] ?? '—') ?></td>
              <td>
                <span class="badge" style="font-size:.72rem;<?= $genColor ?>"><?= htmlspecialchars($t['genero'] ?? '—') ?></span>
              </td>
              <td class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($t['categorias_servico'] ?? '—') ?></td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($t['telefone']) ?></td>
              <td style="font-size:.83rem;">⭐ <?= number_format((float)$t['avaliacao_media'], 1, ',', '.') ?></td>
              <td>
                <span class="badge" style="font-size:.7rem;<?= $badgeSt ?>"><?= htmlspecialchars($t['status_cadastro']) ?></span>
              </td>
              <td class="text-muted" style="font-size:.78rem;"><?= date('d/m/Y', strtotime($t['criado_em'])) ?></td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <?php if ($t['status_cadastro'] === 'Pendente'): ?>
                    <?php if (!empty($t['documento_path'])): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;"
                        data-bs-toggle="modal" data-bs-target="#modalDoc"
                        data-nome="<?= htmlspecialchars($t['nome'], ENT_QUOTES) ?>"
                        data-genero="<?= htmlspecialchars($t['genero'] ?? '', ENT_QUOTES) ?>"
                        data-doc="<?= htmlspecialchars('../../' . $t['documento_path'], ENT_QUOTES) ?>">
                        Ver doc
                      </button>
                    <?php else: ?>
                      <span class="badge" style="font-size:.7rem;background:#fef9c3;color:#92400e;">Sem documento</span>
                    <?php endif; ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="acao" value="aprovar">
                      <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                      <button class="btn btn-sm btn-success" style="font-size:.78rem;">Aprovar</button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Recusar este cadastro?');">
                      <input type="hidden" name="acao" value="recusar">
                      <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" style="font-size:.78rem;">Recusar</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($isMaster): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir este prestador e todos os seus dados?');">
                      <input type="hidden" name="acao" value="excluir">
                      <input type="hidden" name="tecnico_id" value="<?= (int)$t['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" style="font-size:.78rem;">Excluir</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>

<!-- Modal documento -->
<div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header" style="background:linear-gradient(135deg,#0d1b3d,#1a2b63);color:#fff;">
        <div>
          <h5 class="modal-title mb-0" id="modalDocNome"></h5>
          <small style="opacity:.75;">Gênero declarado: <span id="modalDocGenero" class="fw-semibold"></span></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-2">
        <img id="modalDocImg" src="" alt="Documento" class="img-fluid rounded" style="max-height:75vh;">
      </div>
    </div>
  </div>
</div>

<!-- Modal Novo Prestador -->
<div class="modal fade" id="modalNovoPrestador" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header" style="background:linear-gradient(135deg,#0d1b3d,#1a2b63);color:#fff;">
        <h5 class="modal-title">🔧 Cadastrar Novo Prestador</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="acao" value="cadastrar_prestador">
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Nome completo <span class="text-danger">*</span></label>
            <input type="text" name="p_nome" class="form-control" required maxlength="120" placeholder="Nome do prestador">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
            <input type="email" name="p_email" class="form-control" required maxlength="150" placeholder="prestador@email.com">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Telefone <span class="text-danger">*</span></label>
            <input type="text" name="p_telefone" class="form-control" required maxlength="15" inputmode="numeric" placeholder="(11) 99999-9999">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Gênero</label>
            <select name="p_genero" class="form-select">
              <option value="Masculino">Masculino</option>
              <option value="Feminino">Feminino</option>
              <option value="Outro">Outro</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Especialidade <span class="text-muted fw-normal">(opcional)</span></label>
            <input type="text" name="p_especialidade" class="form-control" maxlength="100" placeholder="Ex: Eletricista residencial">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">CPF <span class="text-muted fw-normal">(opcional)</span></label>
            <input type="text" name="p_cpf" class="form-control" maxlength="14" placeholder="000.000.000-00">
          </div>
          <div class="col-12">
            <div class="alert alert-info mb-0 py-2" style="font-size:.85rem;">
              🔑 Uma <strong>senha temporária aleatória</strong> será gerada. O prestador é cadastrado já como <strong>Aprovado</strong> — verifique a documentação offline antes de usar esta opção.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning fw-bold">Cadastrar Prestador</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Senha Temporária -->
<?php
$_mdNome  = $prestadorCadastrado['nome']  ?? $tempSenhaPrestador['nome']  ?? '';
$_mdEmail = $prestadorCadastrado['email'] ?? $tempSenhaPrestador['email'] ?? '';
$_mdSenha = $senhaGerada                  ?: ($tempSenhaPrestador['senha'] ?? '');
?>
<div class="modal fade" id="modalSenhaTemp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg overflow-hidden">
      <div style="background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);padding:1.3rem 1.5rem;">
        <h5 class="text-white fw-bold mb-0">✅ Prestador cadastrado com sucesso</h5>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-3" style="font-size:.9rem;">
          Repasse as credenciais abaixo ao prestador — por WhatsApp, e-mail ou pessoalmente.
        </p>
        <div class="mb-3">
          <div class="form-label fw-semibold" style="font-size:.83rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Prestador</div>
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
          <div class="form-text mt-1">O prestador poderá alterar a senha após o primeiro acesso.</div>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
  var tel = document.querySelector('input[name="p_telefone"]');
  if (tel) {
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
  }

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
});

document.getElementById('modalDoc').addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('modalDocNome').textContent = btn.dataset.nome;
  document.getElementById('modalDocGenero').textContent = btn.dataset.genero;
  document.getElementById('modalDocImg').src = btn.dataset.doc;
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
