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
        } elseif ($novoStatus === 'Fechado' && mb_strlen($texto) < 10) {
            $erro = 'Ao fechar o ticket, descreva a resolução (mínimo 10 caracteres).';
        } else {
            $stk = $pdo->prepare('SELECT cliente_id, tecnico_id, assunto FROM suporte WHERE id=?');
            $stk->execute([$sid]);
            $tk = $stk->fetch();
            if ($tk) {
                $pdo->prepare("INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)")
                    ->execute([$sid, $adminId, $texto]);
                if ($novoStatus) {
                    $resposta = $novoStatus === 'Fechado' ? $texto : null;
                    $pdo->prepare('UPDATE suporte SET status=?, admin_id=?, resposta=?, atualizado_em=NOW() WHERE id=?')
                        ->execute([$novoStatus, $adminId, $resposta, $sid]);
                }
                $msgNotif = 'Sua solicitação de suporte "' . mb_strimwidth($tk['assunto'], 0, 50, '…') . '" recebeu uma nova mensagem.';
                if ($tk['cliente_id']) fixnow_notificar_cliente($pdo, (int)$tk['cliente_id'], $msgNotif);
                if ($tk['tecnico_id']) fixnow_notificar_prestador($pdo, (int)$tk['tecnico_id'], $msgNotif);
                $mensagem = $novoStatus === 'Fechado' ? 'Ticket fechado com resolução registrada.' : 'Mensagem enviada.';
            }
        }
    } elseif ($acao === 'status' && $sid > 0) {
        $novoStatus = in_array($_POST['status'] ?? '', ['Aberto','Em Andamento'], true) ? $_POST['status'] : null;
        if ($novoStatus) {
            $pdo->prepare('UPDATE suporte SET status=?, atualizado_em=NOW() WHERE id=?')->execute([$novoStatus, $sid]);
            $mensagem = 'Status atualizado.';
        } elseif (($_POST['status'] ?? '') === 'Fechado') {
            $erro = 'Para fechar o ticket, use o formulário de resposta e descreva a resolução.';
        }
    } elseif ($acao === 'excluir' && $isMaster && $sid > 0) {
        $pdo->prepare('DELETE FROM suporte WHERE id=?')->execute([$sid]);
        $mensagem = 'Ticket excluído.';

    } elseif ($acao === 'cancelar_chamado' && $sid > 0) {
        $cid = (int)($_POST['chamado_id'] ?? 0);
        if ($cid > 0) {
            $pdo->prepare("UPDATE chamado SET status='Negado', atualizado_em=NOW() WHERE id=? AND status IN ('Pendente','Em Andamento')")
                ->execute([$cid]);
            $pdo->prepare("INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)")
                ->execute([$sid, $adminId, '[AÇÃO] Chamado #' . $cid . ' cancelado pelo suporte.']);
            $stk = $pdo->prepare('SELECT cliente_id, tecnico_id FROM suporte WHERE id=?');
            $stk->execute([$sid]);
            $tk = $stk->fetch();
            if ($tk) {
                $notif = "O chamado #{$cid} foi cancelado pela equipe de suporte.";
                if ($tk['cliente_id']) fixnow_notificar_cliente($pdo, (int)$tk['cliente_id'], $notif);
                if ($tk['tecnico_id']) fixnow_notificar_prestador($pdo, (int)$tk['tecnico_id'], $notif);
            }
            $mensagem = "Chamado #{$cid} cancelado.";
        }

    } elseif ($acao === 'devolver_chamado' && $sid > 0) {
        $cid = (int)($_POST['chamado_id'] ?? 0);
        if ($cid > 0) {
            $pdo->prepare("UPDATE chamado SET status='Pendente', tecnico_id=NULL, atualizado_em=NOW() WHERE id=? AND status='Em Andamento'")
                ->execute([$cid]);
            $pdo->prepare("INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)")
                ->execute([$sid, $adminId, '[AÇÃO] Chamado #' . $cid . ' devolvido ao pool — um novo prestador será atribuído.']);
            $stk = $pdo->prepare('SELECT cliente_id FROM suporte WHERE id=?');
            $stk->execute([$sid]);
            $tk = $stk->fetch();
            if ($tk && $tk['cliente_id']) {
                fixnow_notificar_cliente($pdo, (int)$tk['cliente_id'], "O chamado #{$cid} será reatribuído a um novo prestador em breve.");
            }
            $mensagem = "Chamado #{$cid} devolvido ao pool de prestadores.";
        }

    } elseif ($acao === 'estorno' && $sid > 0) {
        $nota = trim($_POST['nota_estorno'] ?? '');
        $stk  = $pdo->prepare('SELECT cliente_id, tecnico_id, chamado_id FROM suporte WHERE id=?');
        $stk->execute([$sid]);
        $tk = $stk->fetch();

        if (!$tk) {
            $erro = 'Ticket não encontrado.';
        } elseif (!$tk['chamado_id']) {
            $erro = 'Este ticket não tem um chamado vinculado. Vincule um chamado para processar o estorno.';
        } else {
            $pag = $pdo->prepare('SELECT id, valor, status FROM pagamento WHERE chamado_id=? LIMIT 1');
            $pag->execute([$tk['chamado_id']]);
            $pagamento = $pag->fetch();

            if (!$pagamento) {
                $erro = 'Nenhum pagamento encontrado para o chamado #' . $tk['chamado_id'] . '.';
            } elseif ($pagamento['status'] === 'Estornado') {
                $erro = 'Este pagamento já foi estornado anteriormente.';
            } elseif ($pagamento['status'] !== 'Pago') {
                $erro = 'Só é possível estornar pagamentos com status "Pago". Status atual: ' . $pagamento['status'] . '.';
            } else {
                $pdo->prepare("UPDATE pagamento SET status='Estornado' WHERE id=?")
                    ->execute([$pagamento['id']]);

                $valorFmt = 'R$ ' . number_format((float)$pagamento['valor'], 2, ',', '.');
                $msg = '[AÇÃO] Estorno de ' . $valorFmt . ' processado para o chamado #' . $tk['chamado_id'];
                if ($nota !== '') $msg .= ' — ' . $nota;
                $pdo->prepare("INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)")
                    ->execute([$sid, $adminId, $msg]);

                $notif = 'Seu estorno de ' . $valorFmt . ' foi processado. O valor será devolvido conforme o método de pagamento original.';
                if ($tk['cliente_id']) fixnow_notificar_cliente($pdo, (int)$tk['cliente_id'], $notif);
                if ($tk['tecnico_id']) fixnow_notificar_prestador($pdo, (int)$tk['tecnico_id'], $notif);

                $mensagem = 'Estorno de ' . $valorFmt . ' processado com sucesso.';
            }
        }

    } elseif ($acao === 'bloquear_usuario' && $sid > 0) {
        $tuid = (int)($_POST['tecnico_id'] ?? 0);
        if ($tuid > 0) {
            $pdo->prepare("UPDATE tecnico SET ativo=0 WHERE id=?")->execute([$tuid]);
            $pdo->prepare("INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)")
                ->execute([$sid, $adminId, '[AÇÃO] Conta do prestador bloqueada temporariamente.']);
            fixnow_notificar_prestador($pdo, $tuid, 'Sua conta foi bloqueada temporariamente pelo suporte. Entre em contato para mais informações.');
            $mensagem = 'Conta do prestador bloqueada.';
        }

    } elseif ($acao === 'desbloquear_usuario' && $sid > 0) {
        $tuid = (int)($_POST['tecnico_id'] ?? 0);
        if ($tuid > 0) {
            $pdo->prepare("UPDATE tecnico SET ativo=1 WHERE id=?")->execute([$tuid]);
            $pdo->prepare("INSERT INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem) VALUES (?,'admin',?,?)")
                ->execute([$sid, $adminId, '[AÇÃO] Bloqueio da conta removido.']);
            fixnow_notificar_prestador($pdo, $tuid, 'Seu acesso foi restaurado pelo suporte. Bem-vindo de volta!');
            $mensagem = 'Bloqueio removido.';
        }
    }
}

// Filtros
$filtroStatus = $_GET['status']     ?? 'todos';
$filtroPrio   = $_GET['prioridade'] ?? '';
$filtroCateg  = $_GET['categoria']  ?? '';

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
           CASE
             WHEN s.cliente_id IS NOT NULL THEN (SELECT nome FROM cliente WHERE id=s.cliente_id LIMIT 1)
             WHEN s.tecnico_id IS NOT NULL THEN (SELECT nome FROM tecnico WHERE id=s.tecnico_id LIMIT 1)
           END AS usuario_nome,
           CASE
             WHEN s.cliente_id IS NOT NULL THEN 'cliente'
             WHEN s.tecnico_id IS NOT NULL THEN 'prestador'
           END AS tipo_usuario
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
$detalhe        = null;
$mensagens      = [];
$chamadoDetalhe = null;
$usuarioAtivo   = null;
if (isset($_GET['ver'])) {
    $verSid = (int)$_GET['ver'];
    $stmtD = $pdo->prepare("
        SELECT s.*,
               CASE
                 WHEN s.cliente_id IS NOT NULL THEN (SELECT nome FROM cliente WHERE id=s.cliente_id LIMIT 1)
                 WHEN s.tecnico_id IS NOT NULL THEN (SELECT nome FROM tecnico WHERE id=s.tecnico_id LIMIT 1)
               END AS usuario_nome,
               CASE
                 WHEN s.cliente_id IS NOT NULL THEN 'cliente'
                 WHEN s.tecnico_id IS NOT NULL THEN 'prestador'
               END AS tipo_usuario
        FROM suporte s WHERE s.id=?
    ");
    $stmtD->execute([$verSid]);
    $detalhe = $stmtD->fetch();
    if ($detalhe) {
        $stmtM = $pdo->prepare("
            SELECT sm.*,
                   CASE sm.autor_tipo
                     WHEN 'admin'     THEN (SELECT nome FROM admin WHERE id=sm.autor_id LIMIT 1)
                     WHEN 'cliente'   THEN (SELECT nome FROM cliente WHERE id=sm.autor_id LIMIT 1)
                     WHEN 'prestador' THEN (SELECT nome FROM tecnico WHERE id=sm.autor_id LIMIT 1)
                   END AS autor_nome
            FROM suporte_mensagem sm WHERE sm.suporte_id=? ORDER BY sm.criado_em ASC
        ");
        $stmtM->execute([$verSid]);
        $mensagens = $stmtM->fetchAll();

        if (!empty($detalhe['chamado_id'])) {
            $stmtC = $pdo->prepare("
                SELECT c.id, c.status, c.categoria, c.descricao, c.endereco_servico,
                       c.cliente_id, c.tecnico_id, c.preco_sugerido,
                       cl.nome AS cliente_nome,
                       t.nome  AS tecnico_nome, t.ativo AS tecnico_ativo
                FROM chamado c
                LEFT JOIN cliente cl ON cl.id = c.cliente_id
                LEFT JOIN tecnico t  ON t.id  = c.tecnico_id
                WHERE c.id = ?
            ");
            $stmtC->execute([$detalhe['chamado_id']]);
            $chamadoDetalhe = $stmtC->fetch() ?: null;
        }

        if ($detalhe['tecnico_id']) {
            $r = $pdo->prepare("SELECT ativo FROM tecnico WHERE id=?");
            $r->execute([$detalhe['tecnico_id']]);
            $usuarioAtivo = (int)($r->fetchColumn() ?? 1);
        }
    }
}

function chamadoStatusBadge(string $s): string {
    return match($s) {
        'Em Andamento' => 'primary',
        'Concluído'    => 'success',
        'Negado'       => 'danger',
        default        => 'warning text-dark',
    };
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
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .admin-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.3rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .secao-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:.9rem;padding-bottom:.6rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}
    .kpi-sup{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1rem 1.3rem;display:flex;align-items:center;gap:.8rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .kpi-sup-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
    .kpi-sup-val{font-size:1.4rem;font-weight:800;line-height:1.1}
    .kpi-sup-lbl{font-size:.76rem;color:#667085}
    .thread-box{max-height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:.5rem;padding:.85rem;background:#f8fafc;border-radius:10px;border:1.5px solid #e8ecf3}
    .bubble{max-width:75%;padding:.55rem .9rem;border-radius:1rem;font-size:.875rem;line-height:1.5}
    .bubble-user{align-self:flex-start;background:#fff;color:#1f2937;border:1px solid #dee2e6;border-bottom-left-radius:.25rem}
    .bubble-admin{align-self:flex-end;background:#0d1b3d;color:#fff;border-bottom-right-radius:.25rem}
    .bubble-acao{align-self:center;background:#fef9c3;color:#713f12;border:1px solid #fde047;border-radius:1rem;font-size:.78rem;padding:.35rem .9rem;font-style:italic;max-width:90%}
    .bubble-meta{font-size:.72rem;opacity:.6;margin-top:.2rem}
    .chamado-card{background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:.85rem 1rem}
    .action-panel{background:#f8fafc;border:1.5px solid #e8ecf3;border-radius:10px;padding:.85rem 1rem}
    [data-theme="dark"] .admin-card,.kpi-sup{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .admin-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .kpi-sup{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .kpi-sup-lbl{color:#8090b0}
    [data-theme="dark"] .secao-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .thread-box{background:#1a1a2e;border-color:#333}
    [data-theme="dark"] .bubble-user{background:#2a2a2a;border-color:#444;color:#eee}
    [data-theme="dark"] .bubble-acao{background:#3a3010;color:#fde68a;border-color:#78630a}
    [data-theme="dark"] .chamado-card{background:#1e2a4a;border-color:#3b4f8a}
    [data-theme="dark"] .action-panel{background:#1a1a2e;border-color:#333}
  </style>
</head>
<body>
<?= $navbarHtml ?>

<main class="container py-4 mt-5">

  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>🎧 Central de Suporte</h1>
      <p>Gerencie os tickets abertos por clientes e prestadores da plataforma.</p>
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

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-4">
      <div class="kpi-sup">
        <div class="kpi-sup-icon" style="background:#fef2f2;">🔴</div>
        <div>
          <div class="kpi-sup-val" style="color:#dc2626;"><?= $totAbertos ?></div>
          <div class="kpi-sup-lbl">Abertos</div>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="kpi-sup">
        <div class="kpi-sup-icon" style="background:#fffbeb;">🟡</div>
        <div>
          <div class="kpi-sup-val" style="color:#d97706;"><?= $totAndamento ?></div>
          <div class="kpi-sup-lbl">Em andamento</div>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="kpi-sup">
        <div class="kpi-sup-icon" style="background:#f0fdf4;">🟢</div>
        <div>
          <div class="kpi-sup-val" style="color:#16a34a;"><?= $totFechados ?></div>
          <div class="kpi-sup-lbl">Fechados</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detalhe do ticket -->
  <?php if ($detalhe): ?>
  <div class="admin-card mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
      <div>
        <h5 class="mb-1" style="font-size:1rem;">Ticket #<?= (int)$detalhe['id'] ?>: <?= htmlspecialchars($detalhe['assunto']) ?></h5>
        <div class="d-flex gap-2 flex-wrap">
          <?php
            $badgeSt = $detalhe['status'] === 'Aberto' ? 'danger' : ($detalhe['status'] === 'Em Andamento' ? 'warning text-dark' : 'success');
          ?>
          <span class="badge bg-<?= $badgeSt ?>" style="font-size:.72rem;"><?= htmlspecialchars($detalhe['status']) ?></span>
          <span class="badge bg-<?= prioridadeBadge($detalhe['prioridade'] ?? 'Normal') ?>" style="font-size:.72rem;"><?= htmlspecialchars($detalhe['prioridade'] ?? 'Normal') ?></span>
          <span class="badge" style="background:#f3f4f6;color:#374151;font-size:.72rem;"><?= htmlspecialchars($detalhe['categoria'] ?? 'Outro') ?></span>
          <span class="text-muted" style="font-size:.78rem;"><?= ucfirst($detalhe['tipo_usuario'] ?? '—') ?>: <strong><?= htmlspecialchars($detalhe['usuario_nome'] ?? '—') ?></strong> — <?= date('d/m/Y H:i', strtotime($detalhe['criado_em'])) ?></span>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <form method="post" class="d-flex gap-1">
          <input type="hidden" name="acao" value="status">
          <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
          <select name="status" class="form-select form-select-sm">
            <?php foreach (['Aberto','Em Andamento'] as $s): ?>
              <option value="<?= $s ?>" <?= $detalhe['status']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-secondary">Salvar</button>
        </form>
        <a href="suporteAdmin.php?status=<?= urlencode($filtroStatus) ?>" class="btn btn-sm btn-outline-secondary">← Voltar</a>
      </div>
    </div>

    <!-- Chamado vinculado -->
    <?php if ($chamadoDetalhe): ?>
    <div class="chamado-card mb-3">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <div style="font-size:.75rem;color:#6b7280;margin-bottom:.25rem;">🔧 Chamado vinculado</div>
          <div class="fw-semibold" style="font-size:.9rem;">
            #<?= (int)$chamadoDetalhe['id'] ?> — <?= htmlspecialchars($chamadoDetalhe['categoria']) ?>
            <span class="badge bg-<?= chamadoStatusBadge($chamadoDetalhe['status']) ?> ms-1" style="font-size:.7rem;"><?= $chamadoDetalhe['status'] ?></span>
          </div>
          <div style="font-size:.8rem;margin-top:.2rem;color:#374151;">
            👤 <?= htmlspecialchars($chamadoDetalhe['cliente_nome'] ?? '—') ?>
            <?php if ($chamadoDetalhe['tecnico_nome']): ?>
              &nbsp;·&nbsp;🔨 <?= htmlspecialchars($chamadoDetalhe['tecnico_nome']) ?>
            <?php else: ?>
              &nbsp;·&nbsp;<em class="text-muted">Sem prestador</em>
            <?php endif; ?>
            <?php if ($chamadoDetalhe['preco_sugerido'] > 0): ?>
              &nbsp;·&nbsp;💰 R$ <?= number_format((float)$chamadoDetalhe['preco_sugerido'], 2, ',', '.') ?>
            <?php endif; ?>
          </div>
          <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem;">
            📍 <?= htmlspecialchars(mb_strimwidth($chamadoDetalhe['endereco_servico'], 0, 70, '…')) ?>
          </div>
        </div>
        <div class="d-flex flex-column gap-1" style="min-width:180px">
          <?php if (in_array($chamadoDetalhe['status'], ['Pendente','Em Andamento'])): ?>
            <form method="post" onsubmit="return confirm('Cancelar este chamado? Essa ação não pode ser desfeita.');">
              <input type="hidden" name="acao" value="cancelar_chamado">
              <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
              <input type="hidden" name="chamado_id" value="<?= (int)$chamadoDetalhe['id'] ?>">
              <button class="btn btn-sm btn-outline-danger w-100">✕ Cancelar chamado</button>
            </form>
          <?php endif; ?>
          <?php if ($chamadoDetalhe['status'] === 'Em Andamento' && $chamadoDetalhe['tecnico_id']): ?>
            <form method="post" onsubmit="return confirm('Devolver ao pool? O prestador atual será removido.');">
              <input type="hidden" name="acao" value="devolver_chamado">
              <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
              <input type="hidden" name="chamado_id" value="<?= (int)$chamadoDetalhe['id'] ?>">
              <button class="btn btn-sm btn-outline-warning w-100">↩ Reatribuir prestador</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php elseif (!empty($detalhe['chamado_id'])): ?>
      <div class="alert alert-warning small py-2 mb-3">Chamado #<?= (int)$detalhe['chamado_id'] ?> não encontrado.</div>
    <?php endif; ?>

    <!-- Thread -->
    <div class="thread-box mb-3" id="thread-box">
      <?php if (!$mensagens): ?>
        <p class="text-muted small mb-0">Nenhuma mensagem ainda.</p>
      <?php else: ?>
        <?php foreach ($mensagens as $m):
          $isAcao  = str_starts_with($m['mensagem'], '[AÇÃO]');
          $isAdmin = $m['autor_tipo'] === 'admin';
          $textoExibido = $isAcao ? ltrim(substr($m['mensagem'], 6)) : $m['mensagem'];
        ?>
          <div>
            <?php if ($isAcao): ?>
              <div class="bubble bubble-acao">⚙️ <?= nl2br(htmlspecialchars($textoExibido)) ?></div>
              <div class="bubble-meta text-center"><?= date('d/m H:i', strtotime($m['criado_em'])) ?></div>
            <?php else: ?>
              <div class="bubble <?= $isAdmin ? 'bubble-admin' : 'bubble-user' ?>">
                <?= nl2br(htmlspecialchars($textoExibido)) ?>
              </div>
              <div class="bubble-meta <?= $isAdmin ? 'text-end' : '' ?>">
                <?= htmlspecialchars($m['autor_nome'] ?? ucfirst($m['autor_tipo'])) ?>
                (<?= ucfirst($m['autor_tipo']) ?>) · <?= date('d/m H:i', strtotime($m['criado_em'])) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Painel: Pagamento -->
    <?php if (($detalhe['categoria'] ?? '') === 'Pagamento'): ?>
    <div class="action-panel mb-3">
      <div class="fw-semibold mb-2" style="font-size:.85rem;color:#16a34a;">💰 Registrar estorno / ajuste de pagamento</div>
      <form method="post" class="row g-2 align-items-end">
        <input type="hidden" name="acao" value="estorno">
        <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1" style="font-size:.8rem;">Valor (R$)</label>
          <input type="number" name="valor_estorno" class="form-control form-control-sm"
                 step="0.01" min="0" placeholder="0,00" style="width:120px">
        </div>
        <div class="col">
          <label class="form-label form-label-sm mb-1" style="font-size:.8rem;">Observação</label>
          <input type="text" name="nota_estorno" class="form-control form-control-sm"
                 placeholder="Ex: Reembolso via PIX processado" maxlength="200">
        </div>
        <div class="col-auto">
          <button class="btn btn-sm btn-success fw-semibold">✔ Confirmar estorno</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- Painel: Conta / Prestador -->
    <?php if ($detalhe['tecnico_id'] && $usuarioAtivo !== null): ?>
    <div class="action-panel mb-3">
      <div class="fw-semibold mb-2" style="font-size:.85rem;color:#0d1b3d;">🔐 Gerenciamento de conta</div>
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <span style="font-size:.85rem;">Status:
          <span class="badge" style="background:<?= $usuarioAtivo ? '#dcfce7' : '#fee2e2' ?>;color:<?= $usuarioAtivo ? '#16a34a' : '#dc2626' ?>;font-size:.75rem;">
            <?= $usuarioAtivo ? 'Ativa' : 'Bloqueada' ?>
          </span>
        </span>
        <?php if ($usuarioAtivo): ?>
          <form method="post" onsubmit="return confirm('Bloquear este prestador?');">
            <input type="hidden" name="acao" value="bloquear_usuario">
            <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
            <input type="hidden" name="tecnico_id" value="<?= (int)$detalhe['tecnico_id'] ?>">
            <button class="btn btn-sm btn-danger">🔒 Bloquear conta</button>
          </form>
        <?php else: ?>
          <form method="post" onsubmit="return confirm('Restaurar acesso deste prestador?');">
            <input type="hidden" name="acao" value="desbloquear_usuario">
            <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
            <input type="hidden" name="tecnico_id" value="<?= (int)$detalhe['tecnico_id'] ?>">
            <button class="btn btn-sm btn-success">🔓 Restaurar acesso</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Enviar mensagem -->
    <?php if ($detalhe['status'] !== 'Fechado'): ?>
    <form method="post" id="form-resposta">
      <input type="hidden" name="acao" value="mensagem">
      <input type="hidden" name="suporte_id" value="<?= (int)$detalhe['id'] ?>">
      <div class="row g-2 align-items-end">
        <div class="col">
          <label class="form-label" id="label-resposta" style="font-size:.85rem;font-weight:600;">Resposta</label>
          <textarea name="mensagem" id="textarea-resposta" class="form-control" rows="3"
            placeholder="Digite a resposta para o usuário..." required></textarea>
        </div>
        <div class="col-auto">
          <label class="form-label" style="font-size:.85rem;font-weight:600;">Mudar status</label>
          <select name="status" id="select-status-resposta" class="form-select form-select-sm mb-2">
            <option value="">Manter atual</option>
            <?php foreach (['Em Andamento','Fechado'] as $s): ?>
              <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" id="btn-enviar-resposta" class="btn btn-warning fw-bold w-100">Enviar</button>
        </div>
      </div>
      <div id="hint-fechamento" class="alert alert-warning small mt-2 mb-0 d-none">
        ⚠️ Ao fechar, descreva claramente como o problema foi resolvido. Isso ficará visível para o usuário.
      </div>
    </form>
    <?php else: ?>
      <p class="text-muted small mb-0">🔒 Este ticket está fechado.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="admin-card mb-3">
    <div class="secao-titulo"><span>🔍</span> Filtros</div>
    <form method="get" class="d-flex gap-2 flex-wrap align-items-end">
      <?php if (isset($_GET['ver'])): ?><input type="hidden" name="ver" value="<?= (int)$_GET['ver'] ?>"><?php endif; ?>
      <div>
        <label class="form-label form-label-sm mb-1" style="font-size:.8rem;font-weight:600;">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="todos" <?= $filtroStatus==='todos'?'selected':'' ?>>Todos</option>
          <?php foreach (['Aberto','Em Andamento','Fechado'] as $s): ?>
            <option value="<?= $s ?>" <?= $filtroStatus===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label form-label-sm mb-1" style="font-size:.8rem;font-weight:600;">Prioridade</label>
        <select name="prioridade" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php foreach ($prioridades as $p): ?>
            <option value="<?= $p ?>" <?= $filtroPrio===$p?'selected':'' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label form-label-sm mb-1" style="font-size:.8rem;font-weight:600;">Categoria</label>
        <select name="categoria" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c ?>" <?= $filtroCateg===$c?'selected':'' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-sm btn-warning fw-semibold align-self-end">Filtrar</button>
      <?php if ($filtroStatus !== 'todos' || $filtroPrio || $filtroCateg): ?>
        <a href="suporteAdmin.php" class="btn btn-sm btn-outline-secondary align-self-end">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Tabela de tickets -->
  <div class="admin-card">
    <div class="secao-titulo">
      <span>🎫</span> Tickets
      <span class="badge ms-auto" style="background:#e8ecf3;color:#0d1b3d;font-size:.75rem;"><?= count($tickets) ?></span>
    </div>
    <?php if (!$tickets): ?>
      <div class="text-center py-5 text-muted">
        <div style="font-size:2.5rem;margin-bottom:.5rem;">✅</div>
        Nenhum ticket encontrado.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
          <thead class="table-primary">
            <tr><th>#</th><th>Usuário</th><th>Tipo</th><th>Categoria</th><th>Prioridade</th><th>Assunto</th><th>Status</th><th>Data</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach ($tickets as $tk):
            $badgeSt = $tk['status']==='Aberto'?'danger':($tk['status']==='Em Andamento'?'warning text-dark':'success');
            $badgePr = prioridadeBadge($tk['prioridade'] ?? 'Normal');
          ?>
            <tr>
              <td class="text-muted" style="font-size:.82rem;"><?= (int)$tk['id'] ?></td>
              <td class="fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($tk['usuario_nome'] ?? '—') ?></td>
              <td><span class="badge bg-secondary" style="font-size:.7rem;"><?= ucfirst($tk['tipo_usuario'] ?? '—') ?></span></td>
              <td><span class="badge" style="background:#f3f4f6;color:#374151;font-size:.7rem;"><?= htmlspecialchars($tk['categoria'] ?? 'Outro') ?></span></td>
              <td><span class="badge bg-<?= $badgePr ?>" style="font-size:.7rem;"><?= htmlspecialchars($tk['prioridade'] ?? 'Normal') ?></span></td>
              <td style="font-size:.83rem;"><?= htmlspecialchars(mb_strimwidth($tk['assunto'], 0, 45, '…')) ?></td>
              <td><span class="badge bg-<?= $badgeSt ?>" style="font-size:.7rem;"><?= $tk['status'] ?></span></td>
              <td class="text-muted" style="font-size:.78rem;"><?= date('d/m/Y', strtotime($tk['criado_em'])) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <a href="suporteAdmin.php?ver=<?= (int)$tk['id'] ?>&status=<?= urlencode($filtroStatus) ?><?= $filtroPrio ? '&prioridade='.urlencode($filtroPrio) : '' ?><?= $filtroCateg ? '&categoria='.urlencode($filtroCateg) : '' ?>"
                     class="btn btn-sm btn-outline-primary" style="font-size:.78rem;">Abrir</a>
                  <?php if ($isMaster): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir este ticket?');">
                      <input type="hidden" name="acao" value="excluir">
                      <input type="hidden" name="suporte_id" value="<?= (int)$tk['id'] ?>">
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
const box = document.getElementById('thread-box');
if (box) box.scrollTop = box.scrollHeight;

const selStatus = document.getElementById('select-status-resposta');
const taResposta = document.getElementById('textarea-resposta');
const hintFech = document.getElementById('hint-fechamento');
const labelResposta = document.getElementById('label-resposta');
const btnEnviar = document.getElementById('btn-enviar-resposta');

if (selStatus) {
  selStatus.addEventListener('change', function () {
    const fechando = this.value === 'Fechado';
    if (fechando) {
      taResposta.placeholder = 'Descreva como o problema foi resolvido... (obrigatório para fechar)';
      labelResposta.textContent = 'Resolução (obrigatória)';
      btnEnviar.textContent = 'Fechar ticket';
      btnEnviar.classList.replace('btn-warning', 'btn-success');
      hintFech.classList.remove('d-none');
    } else {
      taResposta.placeholder = 'Digite a resposta para o usuário...';
      labelResposta.textContent = 'Resposta';
      btnEnviar.textContent = 'Enviar';
      btnEnviar.classList.replace('btn-success', 'btn-warning');
      hintFech.classList.add('d-none');
    }
  });
}
</script>
</body>
</html>
