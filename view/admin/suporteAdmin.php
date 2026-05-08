<?php
session_start();
$paginaAtiva = 'suporte';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';

ob_start();
require_once __DIR__ . '/_navbar.php';
$navbarHtml = ob_get_clean();

$mensagem = '';
$erro = '';

$categorias  = ['Pagamento', 'Técnico', 'Conta', 'Outro'];
$prioridades = ['Baixa', 'Normal', 'Alta', 'Urgente'];

function prioridadeBadge(string $p): string {
    return match($p) {
        'Urgente' => 'danger',
        'Alta'    => 'warning text-dark',
        'Baixa'   => 'secondary',
        default   => 'info text-dark',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $sid  = (int)($_POST['suporte_id'] ?? 0);

    if ($acao === 'mensagem' && $sid > 0) {
        $texto      = trim($_POST['mensagem'] ?? '');
        $novoStatus = in_array($_POST['status'] ?? '', ['Aberto','Em Andamento','Fechado'], true)
                    ? $_POST['status'] : null;
        if ($texto === '') {
            $erro = 'A mensagem não pode estar vazia.';
        } else {
            $stk = $pdo->prepare('SELECT tipo_usuario, usuario_id, assunto FROM suporte WHERE id=?');
            $stk->execute([$sid]);
            $tk = $stk->fetch();
            if ($tk) {
                $pdo->prepare("INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)")
                    ->execute([$sid, $adminId, $texto]);
                if ($novoStatus) {
                    $pdo->prepare('UPDATE suporte SET status=?, respondido_por=?, atualizado_em=NOW() WHERE id=?')
                        ->execute([$novoStatus, $adminId, $sid]);
                }
                $msgNotif = 'Sua solicitação de suporte "' . mb_strimwidth($tk['assunto'], 0, 50, '…') . '" recebeu uma nova mensagem.';
                if ($tk['tipo_usuario'] === 'cliente')   fixnow_notificar_cliente($pdo, (int)$tk['usuario_id'], $msgNotif);
                if ($tk['tipo_usuario'] === 'prestador') fixnow_notificar_prestador($pdo, (int)$tk['usuario_id'], $msgNotif);
                $mensagem = 'Mensagem enviada.';
            }
        }
    } elseif ($acao === 'status' && $sid > 0) {
        $novoStatus = in_array($_POST['status'] ?? '', ['Aberto','Em Andamento','Fechado'], true) ? $_POST['status'] : null;
        if ($novoStatus) {
            $pdo->prepare('UPDATE suporte SET status=?, atualizado_em=NOW() WHERE id=?')->execute([$novoStatus, $sid]);
            $mensagem = 'Status atualizado.';
        }
    } elseif ($acao === 'excluir' && $isMaster && $sid > 0) {
        $pdo->prepare('DELETE FROM suporte WHERE id=?')->execute([$sid]);
        $mensagem = 'Ticket excluído.';
    }
}

// Filtros
$filtroStatus    = $_GET['status']     ?? 'todos';
$filtroPrio      = $_GET['prioridade'] ?? '';
$filtroCateg     = $_GET['categoria']  ?? '';

$where  = [];
$params = [];
if (in_array($filtroStatus, ['Aberto','Em Andamento','Fechado'], true)) {
    $where[] = 's.status = ?'; $params[] = $filtroStatus;
}
if (in_array($filtroPrio, $prioridades, true)) {
    $where[] = 's.prioridade = ?'; $params[] = $filtroPrio;
}
if (in_array($filtroCateg, $categorias, true)) {
    $where[] = 's.categoria = ?'; $params[] = $filtroCateg;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT s.*,
           CASE s.tipo_usuario
             WHEN 'cliente'   THEN (SELECT nome FROM cliente WHERE id=s.usuario_id LIMIT 1)
             WHEN 'prestador' THEN (SELECT nome FROM tecnico WHERE id=s.usuario_id LIMIT 1)
             WHEN 'admin'     THEN (SELECT nome FROM cliente WHERE id=s.usuario_id AND is_admin=1 LIMIT 1)
           END AS usuario_nome
    FROM suporte s $whereSQL
    ORDER BY FIELD(s.prioridade,'Urgente','Alta','Normal','Baixa'),
             FIELD(s.status,'Aberto','Em Andamento','Fechado'),
             s.criado_em DESC
");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$totAbertos   = (int)$pdo->query("SELECT COUNT(*) FROM suporte WHERE status='Aberto'")->fetchColumn();
$totAndamento = (int)$pdo->query("SELECT COUNT(*) FROM suporte WHERE status='Em Andamento'")->fetchColumn();
$totFechados  = (int)$pdo->query("SELECT COUNT(*) FROM suporte WHERE status='Fechado'")->fetchColumn();

// Detalhe de ticket
$detalhe  = null;
$mensagens = [];
if (isset($_GET['ver'])) {
    $verSid = (int)$_GET['ver'];
    $stmtD = $pdo->prepare("
        SELECT s.*,
               CASE s.tipo_usuario
                 WHEN 'cliente'   THEN (SELECT nome FROM cliente WHERE id=s.usuario_id LIMIT 1)
                 WHEN 'prestador' THEN (SELECT nome FROM tecnico WHERE id=s.usuario_id LIMIT 1)
                 WHEN 'admin'     THEN (SELECT nome FROM cliente WHERE id=s.usuario_id AND is_admin=1 LIMIT 1)
               END AS usuario_nome
        FROM suporte s WHERE s.id=?
    ");
    $stmtD->execute([$verSid]);
    $detalhe = $stmtD->fetch();
    if ($detalhe) {
        $stmtM = $pdo->prepare("
            SELECT sm.*,
                   CASE sm.autor_tipo
                     WHEN 'admin'     THEN (SELECT nome FROM cliente WHERE id=sm.autor_id AND is_admin=1 LIMIT 1)
                     WHEN 'cliente'   THEN (SELECT nome FROM cliente WHERE id=sm.autor_id LIMIT 1)
                     WHEN 'prestador' THEN (SELECT nome FROM tecnico WHERE id=sm.autor_id LIMIT 1)
                   END AS autor_nome
            FROM suporte_mensagem sm WHERE sm.suporte_id=? ORDER BY sm.criado_em ASC
        ");
        $stmtM->execute([$verSid]);
        $mensagens = $stmtM->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Suporte - Admin Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .thread-box { max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; padding: .85rem; background: #f8f9fa; border-radius: .5rem; border: 1px solid #dee2e6; }
    .bubble { max-width: 75%; padding: .55rem .9rem; border-radius: 1rem; font-size: .875rem; line-height: 1.5; }
    .bubble-user  { align-self: flex-start; background: #fff; border: 1px solid #dee2e6; border-bottom-left-radius: .25rem; }
    .bubble-admin { align-self: flex-end; background: #0d1b3d; color: #fff; border-bottom-right-radius: .25rem; }
    .bubble-meta  { font-size: .72rem; opacity: .6; margin-top: .2rem; }
    .dark-mode .thread-box  { background: #1a1a2e; border-color: #333; }
    .dark-mode .bubble-user { background: #2a2a2a; border-color: #444; color: #eee; }
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Central de Suporte</h2>
  <p class="text-muted mb-4">Gerencie os tickets abertos por clientes, prestadores e administradores.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-4"><div class="card shadow-sm border-0 text-center"><div class="card-body py-2">
      <small class="text-muted">Abertos</small><h4 class="mb-0 text-danger"><?= $totAbertos ?></h4>
    </div></div></div>
    <div class="col-4"><div class="card shadow-sm border-0 text-center"><div class="card-body py-2">
      <small class="text-muted">Em andamento</small><h4 class="mb-0 text-warning"><?= $totAndamento ?></h4>
    </div></div></div>
    <div class="col-4"><div class="card shadow-sm border-0 text-center"><div class="card-body py-2">
      <small class="text-muted">Fechados</small><h4 class="mb-0 text-success"><?= $totFechados ?></h4>
    </div></div></div>
  </div>

  <!-- Detalhe do ticket -->
  <?php if ($detalhe): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
          <h5 class="mb-1">Ticket #<?= (int)$detalhe['id'] ?>: <?= htmlspecialchars($detalhe['assunto']) ?></h5>
          <div class="d-flex gap-2 flex-wrap">
            <?php
              $badgeSt = $detalhe['status'] === 'Aberto' ? 'danger' : ($detalhe['status'] === 'Em Andamento' ? 'warning text-dark' : 'success');
            ?>
            <span class="badge bg-<?= $badgeSt ?>"><?= htmlspecialchars($detalhe['status']) ?></span>
            <span class="badge bg-<?= prioridadeBadge($detalhe['prioridade'] ?? 'Normal') ?>"><?= htmlspecialchars($detalhe['prioridade'] ?? 'Normal') ?></span>
            <span class="badge bg-light text-dark border"><?= htmlspecialchars($detalhe['categoria'] ?? 'Outro') ?></span>
            <span class="small text-muted"><?= ucfirst($detalhe['tipo_usuario']) ?>: <strong><?= htmlspecialchars($detalhe['usuario_nome'] ?? '—') ?></strong>
            — <?= date('d/m/Y H:i', strtotime($detalhe['criado_em'])) ?></span>
          </div>
        </div>
        <div class="d-flex gap-2">
          <!-- Alterar status rápido -->
          <form method="post" class="d-flex gap-1">
            <input type="hidden" name="acao" value="status">
            <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
            <select name="status" class="form-select form-select-sm">
              <?php foreach (['Aberto','Em Andamento','Fechado'] as $s): ?>
                <option value="<?= $s ?>" <?= $detalhe['status']===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary">Salvar</button>
          </form>
          <a href="suporteAdmin.php?status=<?= urlencode($filtroStatus) ?>" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>
      </div>

      <!-- Thread -->
      <div class="thread-box mb-3" id="thread-box">
        <?php if (!$mensagens): ?>
          <p class="text-muted small mb-0">Nenhuma mensagem ainda.</p>
        <?php else: ?>
          <?php foreach ($mensagens as $m):
            $isAdmin = $m['autor_tipo'] === 'admin';
          ?>
            <div>
              <div class="bubble <?= $isAdmin ? 'bubble-admin' : 'bubble-user' ?>">
                <?= nl2br(htmlspecialchars($m['mensagem'])) ?>
              </div>
              <div class="bubble-meta <?= $isAdmin ? 'text-end' : '' ?>">
                <?= htmlspecialchars($m['autor_nome'] ?? ucfirst($m['autor_tipo'])) ?>
                (<?= ucfirst($m['autor_tipo']) ?>)
                · <?= date('d/m H:i', strtotime($m['criado_em'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Enviar mensagem -->
      <?php if ($detalhe['status'] !== 'Fechado'): ?>
      <form method="post">
        <input type="hidden" name="acao" value="mensagem">
        <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
        <div class="row g-2 align-items-end">
          <div class="col">
            <label class="form-label">Resposta</label>
            <textarea name="mensagem" class="form-control" rows="3"
              placeholder="Digite a resposta para o usuário..." required></textarea>
          </div>
          <div class="col-auto">
            <label class="form-label">Mudar status</label>
            <select name="status" class="form-select form-select-sm mb-2">
              <option value="">Manter atual</option>
              <?php foreach (['Aberto','Em Andamento','Fechado'] as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-warning fw-semibold w-100">Enviar</button>
          </div>
        </div>
      </form>
      <?php else: ?>
        <p class="text-muted small mb-0">Este ticket está fechado.</p>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
      <form method="get" class="d-flex gap-2 flex-wrap align-items-end">
        <?php if (isset($_GET['ver'])): ?><input type="hidden" name="ver" value="<?= (int)$_GET['ver'] ?>"><?php endif; ?>
        <div>
          <label class="form-label form-label-sm mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="todos" <?= $filtroStatus==='todos'?'selected':'' ?>>Todos</option>
            <?php foreach (['Aberto','Em Andamento','Fechado'] as $s): ?>
              <option value="<?= $s ?>" <?= $filtroStatus===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label form-label-sm mb-1">Prioridade</label>
          <select name="prioridade" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach ($prioridades as $p): ?>
              <option value="<?= $p ?>" <?= $filtroPrio===$p?'selected':'' ?>><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label form-label-sm mb-1">Categoria</label>
          <select name="categoria" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c ?>" <?= $filtroCateg===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-sm btn-outline-secondary align-self-end">Filtrar</button>
        <?php if ($filtroStatus !== 'todos' || $filtroPrio || $filtroCateg): ?>
          <a href="suporteAdmin.php" class="btn btn-sm btn-outline-danger align-self-end">Limpar</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Tabela de tickets -->
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <?php if (!$tickets): ?>
        <p class="text-muted mb-0">Nenhum ticket encontrado.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle table-sm">
            <thead class="table-primary">
              <tr><th>#</th><th>Usuário</th><th>Tipo</th><th>Categoria</th><th>Prioridade</th><th>Assunto</th><th>Status</th><th>Data</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $tk):
              $badgeSt = $tk['status']==='Aberto'?'danger':($tk['status']==='Em Andamento'?'warning text-dark':'success');
              $badgePr = prioridadeBadge($tk['prioridade'] ?? 'Normal');
            ?>
              <tr>
                <td><?= (int)$tk['id'] ?></td>
                <td><?= htmlspecialchars($tk['usuario_nome'] ?? '—') ?></td>
                <td><span class="badge bg-secondary"><?= ucfirst($tk['tipo_usuario']) ?></span></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tk['categoria'] ?? 'Outro') ?></span></td>
                <td><span class="badge bg-<?= $badgePr ?>"><?= htmlspecialchars($tk['prioridade'] ?? 'Normal') ?></span></td>
                <td><?= htmlspecialchars(mb_strimwidth($tk['assunto'], 0, 45, '...')) ?></td>
                <td><span class="badge bg-<?= $badgeSt ?>"><?= $tk['status'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($tk['criado_em'])) ?></td>
                <td class="d-flex gap-1">
                  <a href="suporteAdmin.php?ver=<?= (int)$tk['id'] ?>&status=<?= urlencode($filtroStatus) ?><?= $filtroPrio ? '&prioridade='.urlencode($filtroPrio) : '' ?><?= $filtroCateg ? '&categoria='.urlencode($filtroCateg) : '' ?>"
                     class="btn btn-sm btn-outline-primary">Abrir</a>
                  <?php if ($isMaster): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir este ticket?');">
                      <input type="hidden" name="acao" value="excluir">
                      <input type="hidden" name="suporte_id" value="<?= (int)$tk['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?= date('Y') ?> Fix Now.</small></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
const box = document.getElementById('thread-box');
if (box) box.scrollTop = box.scrollHeight;
</script>
</body>
</html>
