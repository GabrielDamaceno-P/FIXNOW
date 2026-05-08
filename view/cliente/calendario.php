<?php
session_start();
require_once __DIR__ . '/../../controller/CalendarioClienteControl.php';

$ctrl = new CalendarioClienteControl();
$ctrl->processar();

$mes            = $ctrl->mes;
$ano            = $ctrl->ano;
$chamadosPorDia = $ctrl->chamadosPorDia;
$semAgendamento = $ctrl->semAgendamento;
$navMeses       = $ctrl->navMeses;
$mensagem       = $ctrl->mensagem;
$erro           = $ctrl->erro;
$naoLidas       = $ctrl->naoLidas;
$clienteNome    = $_SESSION['cliente_nome'] ?? 'Cliente';
$clienteFoto    = $_SESSION['cliente_foto'] ?? '';

$nomesMeses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho',
               'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$primeiroDia     = mktime(0, 0, 0, $mes, 1, $ano);
$totalDias       = (int)date('t', $primeiroDia);
$diaSemanaInicio = (int)date('w', $primeiroDia);
$hoje            = (int)date('j');
$mesAtual        = (int)date('n');
$anoAtual        = (int)date('Y');

$corStatus = [
    'Pendente'     => 'warning',
    'Em Andamento' => 'primary',
    'Concluído'    => 'success',
    'Negado'       => 'danger',
];
$podeReagendar = fn($status) => in_array($status, ['Pendente', 'Em Andamento']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calendário - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .cal-table { table-layout: fixed; width: 100%; }
    .cal-table th { text-align: center; font-size: .8rem; padding: .5rem 0; background: var(--fix-blue, #0d1b3d); color: #fff; }
    .cal-table td { vertical-align: top; min-height: 90px; height: 90px; padding: 4px 5px; border: 1px solid #dee2e6; font-size: .85rem; }
    .cal-table td.hoje { background: #fff8e1; }
    .cal-table td.outro-mes { background: #f8f9fa; color: #adb5bd; }
    .cal-dia-num { font-weight: 600; margin-bottom: 3px; }
    .cal-badge { display: block; font-size: .72rem; border-radius: 4px; padding: 1px 4px; margin-bottom: 2px; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="../dashboardCliente.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../catalogo.php">Catálogo</a></li>
        <li class="nav-item"><a class="nav-link" href="solicitar.php">Solicitar</a></li>
        <li class="nav-item"><a class="nav-link active" href="calendario.php">Calendário</a></li>
        <li class="nav-item">
          <a class="nav-link" href="../notificacoes.php">Notificações
            <?php if ($naoLidas > 0): ?><span class="badge bg-danger ms-1"><?php echo $naoLidas; ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="../perfil.php">Perfil</a></li>
        <li class="nav-item"><a class="nav-link" href="../rastreamento.php">Rastreamento</a></li>
        <li class="nav-item"><a class="nav-link" href="../suporte.php">Suporte</a></li>
        <li class="nav-item"><a class="nav-link" href="../../logout.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <h2 class="mb-0">Calendário de Serviços</h2>
    <a href="solicitar.php" class="btn btn-warning fw-semibold">Novo chamado</a>
  </div>

  <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="calendario.php?mes=<?php echo $navMeses['anterior']['mes']; ?>&ano=<?php echo $navMeses['anterior']['ano']; ?>"
           class="btn btn-outline-secondary btn-sm">&larr; <?php echo $nomesMeses[$navMeses['anterior']['mes']]; ?></a>
        <h4 class="mb-0 fw-bold"><?php echo $nomesMeses[$mes] . ' ' . $ano; ?></h4>
        <a href="calendario.php?mes=<?php echo $navMeses['proximo']['mes']; ?>&ano=<?php echo $navMeses['proximo']['ano']; ?>"
           class="btn btn-outline-secondary btn-sm"><?php echo $nomesMeses[$navMeses['proximo']['mes']]; ?> &rarr;</a>
      </div>

      <div class="table-responsive">
        <table class="cal-table">
          <thead>
            <tr><th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th></tr>
          </thead>
          <tbody>
            <?php
            $totalLinhas = (int)ceil(($diaSemanaInicio + $totalDias) / 7);
            for ($linha = 0; $linha < $totalLinhas; $linha++):
            ?>
            <tr>
              <?php for ($col = 0; $col < 7; $col++):
                $diaExibir = $linha * 7 + $col - $diaSemanaInicio + 1;
                $fora      = $diaExibir < 1 || $diaExibir > $totalDias;
                $ehHoje    = !$fora && $diaExibir === $hoje && $mes === $mesAtual && $ano === $anoAtual;
              ?>
                <td class="<?php echo $fora ? 'outro-mes' : ($ehHoje ? 'hoje' : ''); ?>">
                  <?php if (!$fora): ?>
                    <div class="cal-dia-num"><?php echo $diaExibir; ?></div>
                    <?php foreach (($chamadosPorDia[$diaExibir] ?? []) as $ch):
                      $cor    = $corStatus[$ch['status']] ?? 'secondary';
                      $titulo = mb_strimwidth($ch['descricao'], 0, 30, '…');
                    ?>
                      <span class="cal-badge bg-<?php echo $cor; ?> <?php echo $cor === 'warning' ? 'text-dark' : 'text-white'; ?>"
                            data-bs-toggle="modal" data-bs-target="#modalReagendar"
                            data-id="<?php echo (int)$ch['id']; ?>"
                            data-desc="<?php echo htmlspecialchars($ch['descricao']); ?>"
                            data-status="<?php echo htmlspecialchars($ch['status']); ?>"
                            data-data="<?php echo htmlspecialchars($ch['data_agendamento'] ?? ''); ?>"
                            data-pode="<?php echo $podeReagendar($ch['status']) ? '1' : '0'; ?>">
                        <?php echo htmlspecialchars($titulo); ?>
                      </span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </td>
              <?php endfor; ?>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex flex-wrap gap-3 mt-3">
        <span><span class="badge bg-warning text-dark">Pendente</span></span>
        <span><span class="badge bg-primary">Em Andamento</span></span>
        <span><span class="badge bg-success">Concluído</span></span>
        <span><span class="badge bg-danger">Negado</span></span>
      </div>
    </div>
  </div>

  <?php if ($semAgendamento): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <h5 class="mb-3">Chamados sem data agendada</h5>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr><th>Descrição</th><th>Categoria</th><th>Status</th><th>Aberto em</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($semAgendamento as $ch):
              $cor = $corStatus[$ch['status']] ?? 'secondary';
            ?>
            <tr>
              <td><?php echo htmlspecialchars(mb_strimwidth($ch['descricao'], 0, 60, '…')); ?></td>
              <td><?php echo htmlspecialchars($ch['categoria']); ?></td>
              <td><span class="badge bg-<?php echo $cor; ?> <?php echo $cor === 'warning' ? 'text-dark' : ''; ?>"><?php echo htmlspecialchars($ch['status']); ?></span></td>
              <td><?php echo date('d/m/Y', strtotime($ch['criado_em'])); ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal" data-bs-target="#modalReagendar"
                        data-id="<?php echo (int)$ch['id']; ?>"
                        data-desc="<?php echo htmlspecialchars($ch['descricao']); ?>"
                        data-status="<?php echo htmlspecialchars($ch['status']); ?>"
                        data-data="" data-pode="1">Agendar data</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>

<!-- Modal Reagendar -->
<div class="modal fade" id="modalReagendar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reagendar serviço</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <p class="text-muted small mb-3" id="modal-desc"></p>
          <div class="mb-2">
            <label class="form-label">Status atual</label>
            <div id="modal-status-badge"></div>
          </div>
          <div id="modal-form-fields">
            <div class="mb-3">
              <label class="form-label">Nova data e hora <span class="text-danger">*</span></label>
              <input type="datetime-local" name="nova_data" id="input-nova-data" class="form-control" required
                     min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>">
            </div>
          </div>
          <div id="modal-aviso-finalizado" class="alert alert-warning d-none">
            Este chamado já foi finalizado e não pode ser reagendado.
          </div>
          <input type="hidden" name="reagendar_chamado_id" id="input-chamado-id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="submit" class="btn btn-warning fw-semibold" id="btn-confirmar-reagenda">Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?php echo date('Y'); ?> Fix Now.</small></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
document.getElementById('modalReagendar').addEventListener('show.bs.modal', function(e) {
  var t = e.relatedTarget;
  var corMap = { 'Pendente': 'warning text-dark', 'Em Andamento': 'primary', 'Concluído': 'success', 'Negado': 'danger' };
  var status = t.dataset.status;
  var pode   = t.dataset.pode === '1';
  document.getElementById('modal-desc').textContent = t.dataset.desc;
  document.getElementById('modal-status-badge').innerHTML = '<span class="badge bg-' + (corMap[status] || 'secondary') + '">' + status + '</span>';
  document.getElementById('input-chamado-id').value = t.dataset.id;
  var campos = document.getElementById('modal-form-fields');
  var aviso  = document.getElementById('modal-aviso-finalizado');
  var btn    = document.getElementById('btn-confirmar-reagenda');
  if (pode) {
    campos.classList.remove('d-none'); aviso.classList.add('d-none'); btn.disabled = false;
    document.getElementById('input-nova-data').value = t.dataset.data ? t.dataset.data.slice(0,16) : '';
  } else {
    campos.classList.add('d-none'); aviso.classList.remove('d-none'); btn.disabled = true;
  }
});
</script>
</body>
</html>
