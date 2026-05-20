<?php
session_start();
require_once __DIR__ . '/../../controller/CalendarioPrestadorControl.php';

$ctrl = new CalendarioPrestadorControl();
$ctrl->processar();

$diasSemana     = $ctrl->diasSemana;
$bloqueados     = $ctrl->bloqueados;
$agendados      = $ctrl->agendados;
$horas          = CalendarioPrestadorControl::$horas;
$podePrev       = $ctrl->podePrev;
$prevSegundaStr = $ctrl->prevSegundaStr;
$nextSegundaStr = $ctrl->nextSegundaStr;
$inicioStr      = $ctrl->inicioStr;
$rangeDisplay   = $ctrl->rangeDisplay;
$mensagem       = $ctrl->mensagem;
$naoLidas       = $ctrl->naoLidas;
$tecnicoNome    = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto    = $_SESSION['tecnico_foto'] ?? '';
$nomeDias       = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
$hoje           = new DateTime('today');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calendário de Disponibilidade - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-hero{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-hero h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-hero p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}

    .cal-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.1rem;box-shadow:0 3px 10px rgba(13,27,61,.05)}
    .nav-semana{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1.1rem}
    .nav-semana .range-label{font-weight:700;font-size:.95rem;color:#0d1b3d;flex-grow:1;text-align:center}

    /* Slots */
    .slot-check{display:none}
    .slot-label{
      display:block;width:100%;padding:.32rem .15rem;border-radius:7px;
      font-size:.72rem;font-weight:600;text-align:center;cursor:pointer;
      transition:background .13s,color .13s,border-color .13s;user-select:none;
      background:#dcfce7;color:#15803d;border:1.5px solid #86efac;
    }
    .slot-check:checked + .slot-label{background:#fee2e2;color:#b91c1c;border-color:#fca5a5}
    .slot-check:disabled + .slot-label{background:#f3f4f6;color:#9ca3af;border-color:#e5e7eb;cursor:not-allowed;font-weight:500}
    .slot-agendado{
      display:block;width:100%;padding:.32rem .15rem;border-radius:7px;
      font-size:.68rem;font-weight:600;text-align:center;line-height:1.35;cursor:default;
      background:#dbeafe;color:#1d4ed8;border:1.5px solid #93c5fd;
    }

    /* Tabela */
    .cal-table th{font-size:.78rem;font-weight:700;text-align:center;vertical-align:middle;padding:.55rem .3rem}
    .cal-table th.hora-th{width:52px;font-size:.75rem;color:#6b7280;text-align:left;padding-left:.5rem}
    .cal-table td{padding:.28rem .22rem;vertical-align:middle}
    .cal-table td.hora-td{font-size:.75rem;color:#6b7280;white-space:nowrap;padding-left:.5rem;font-weight:600}
    .cal-table thead tr{background:#f0f4fb}
    .cal-table .dia-hoje th{background:linear-gradient(135deg,#0d1b3d,#1a2b63);color:#fff}
    .cal-table .dia-hoje th small{color:rgba(255,255,255,.75)}

    /* Legenda */
    .legenda-item{display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:#374151}
    .legenda-dot{width:12px;height:12px;border-radius:4px;flex-shrink:0;border:1.5px solid}

    [data-theme="dark"] .cal-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .nav-semana .range-label{color:#e4e8f4}
    [data-theme="dark"] .cal-table thead tr{background:#252e45}
    [data-theme="dark"] .cal-table th{color:#c8d0e4}
    [data-theme="dark"] .cal-table td.hora-td{color:#8090b0}
    [data-theme="dark"] .legenda-item{color:#c8d0e4}
    [data-theme="dark"] .slot-check:disabled + .slot-label{background:#2a3249;color:#5a6880;border-color:#2e3650}
  </style>
</head>
<body>
<?php $paginaAtiva = 'calendario'; require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-4 mt-5">

  <!-- Hero -->
  <div class="page-hero mb-4">
    <div style="position:relative;z-index:1">
      <h1>📅 Calendário de Disponibilidade</h1>
      <p>Todos os horários estão <strong style="color:#86efac">livres</strong> por padrão. Clique nos horários em que estará <strong style="color:#fca5a5">indisponível</strong> para bloqueá-los.</p>
    </div>
  </div>

  <?php if ($mensagem): ?>
    <div class="alert alert-success alert-dismissible fade show">
      ✅ <?= htmlspecialchars($mensagem) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="cal-card">
    <form method="post">
      <input type="hidden" name="inicio" value="<?= htmlspecialchars($inicioStr) ?>">

      <!-- Navegação de semana -->
      <div class="nav-semana">
        <?php if ($podePrev): ?>
          <a href="calendario.php?inicio=<?= $prevSegundaStr ?>" class="btn btn-sm btn-outline-secondary">← Semana anterior</a>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-secondary" disabled>← Semana anterior</button>
        <?php endif; ?>

        <span class="range-label">📅 <?= htmlspecialchars($rangeDisplay) ?></span>

        <a href="calendario.php?inicio=<?= $nextSegundaStr ?>" class="btn btn-sm btn-outline-secondary">Próxima semana →</a>
      </div>

      <!-- Grade de horários -->
      <div class="table-responsive">
        <table class="table table-bordered cal-table mb-0 align-middle" style="min-width:520px">
          <thead>
            <tr>
              <th class="hora-th">Hora</th>
              <?php foreach ($diasSemana as $i => $dia):
                $ehHoje = ($dia->format('Y-m-d') === $hoje->format('Y-m-d'));
              ?>
                <th class="<?= $ehHoje ? 'dia-hoje' : '' ?>" style="min-width:88px">
                  <div style="font-size:.83rem"><?= $nomeDias[$i] ?></div>
                  <small style="font-size:.73rem;<?= $ehHoje ? 'color:rgba(255,255,255,.75)' : 'color:#6b7280' ?>"><?= $dia->format('d/m') ?></small>
                  <?php if ($ehHoje): ?><div style="font-size:.65rem;color:rgba(255,255,255,.6);margin-top:1px">hoje</div><?php endif; ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($horas as $hora): ?>
            <tr>
              <td class="hora-td"><?= $hora ?></td>
              <?php foreach ($diasSemana as $dia):
                $dataStr  = $dia->format('Y-m-d');
                $slotId   = 'slot_' . $dia->format('Ymd') . '_' . str_replace(':', '', $hora);
                $bloqueado = isset($bloqueados[$dataStr][$hora]);
                $agendado  = $agendados[$dataStr][$hora] ?? null;
                $passado   = $dia < $hoje || ($dia == $hoje && $hora <= date('H:i'));
              ?>
                <td class="text-center" style="padding:.22rem .18rem">
                  <?php if ($agendado): ?>
                    <span class="slot-agendado" title="Chamado #<?= (int)$agendado['id'] ?> — <?= htmlspecialchars($agendado['cliente']) ?>">
                      📌 <?= htmlspecialchars(mb_strimwidth($agendado['categoria'], 0, 12, '…')) ?><br>
                      <span style="font-weight:500"><?= htmlspecialchars(mb_strimwidth($agendado['cliente'], 0, 10, '…')) ?></span>
                    </span>
                  <?php else: ?>
                    <input type="checkbox" class="slot-check" name="slot[]"
                           value="<?= $dataStr ?>|<?= $hora ?>"
                           id="<?= $slotId ?>"
                           <?= $bloqueado ? 'checked' : '' ?>
                           <?= $passado   ? 'disabled' : '' ?>>
                    <label class="slot-label" for="<?= $slotId ?>">
                      <?php
                        if ($passado)       echo '—';
                        elseif ($bloqueado) echo 'Bloqueado';
                        else                echo 'Livre';
                      ?>
                    </label>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Rodapé: salvar + legenda -->
      <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-3 pt-2" style="border-top:1.5px solid #f0f3fa">
        <button type="submit" class="btn btn-warning fw-bold px-4">Salvar agenda</button>
        <div class="d-flex gap-3 flex-wrap">
          <div class="legenda-item">
            <div class="legenda-dot" style="background:#dcfce7;border-color:#86efac"></div>
            Livre
          </div>
          <div class="legenda-item">
            <div class="legenda-dot" style="background:#fee2e2;border-color:#fca5a5"></div>
            Bloqueado
          </div>
          <div class="legenda-item">
            <div class="legenda-dot" style="background:#dbeafe;border-color:#93c5fd"></div>
            📌 Agendado
          </div>
          <div class="legenda-item">
            <div class="legenda-dot" style="background:#f3f4f6;border-color:#e5e7eb"></div>
            Passado
          </div>
        </div>
      </div>

    </form>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
document.querySelectorAll('.slot-check').forEach(function(chk) {
  chk.addEventListener('change', function() {
    this.nextElementSibling.textContent = this.checked ? 'Bloqueado' : 'Livre';
  });
});
</script>
</body>
</html>
