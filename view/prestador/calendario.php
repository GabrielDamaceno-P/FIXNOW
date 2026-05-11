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
    .slot-check { display: none; }
    .slot-label {
      display: block; width: 100%; padding: .3rem .2rem; border-radius: 6px;
      font-size: .78rem; text-align: center; cursor: pointer;
      transition: background .15s, color .15s;
      background: #d1e7dd; color: #0a3622; border: 1px solid #a3cfbb; user-select: none;
    }
    .slot-check:checked + .slot-label { background: #f8d7da; color: #58151c; border-color: #f1aeb5; }
    .slot-check:disabled + .slot-label { opacity: .45; cursor: not-allowed; }
    .slot-agendado {
      display: block; width: 100%; padding: .3rem .2rem; border-radius: 6px;
      font-size: .72rem; text-align: center; line-height: 1.3;
      background: #cfe2ff; color: #084298; border: 1px solid #9ec5fe; cursor: default;
    }
    th.dia-col { min-width: 90px; font-size: .82rem; }
    td.hora-col { font-size: .8rem; white-space: nowrap; color: #495057; width: 52px; }
  </style>
</head>
<body>
<?php $paginaAtiva = 'calendario'; require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Calendário de Disponibilidade</h2>
  <p class="text-muted mb-4">
    Todos os horários são <span class="text-success fw-semibold">livres</span> por padrão.
    Clique nos horários em que estará <span class="text-danger fw-semibold">indisponível</span> para bloqueá-los.
  </p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="inicio" value="<?php echo htmlspecialchars($inicioStr); ?>">

    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
      <?php if ($podePrev): ?>
        <a href="calendario.php?inicio=<?php echo $prevSegundaStr; ?>" class="btn btn-outline-secondary btn-sm">&larr; Semana anterior</a>
      <?php else: ?>
        <button class="btn btn-outline-secondary btn-sm" disabled>&larr; Semana anterior</button>
      <?php endif; ?>
      <strong><?php echo htmlspecialchars($rangeDisplay); ?></strong>
      <a href="calendario.php?inicio=<?php echo $nextSegundaStr; ?>" class="btn btn-outline-secondary btn-sm">Próxima semana &rarr;</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body p-2 p-md-3">
        <div class="table-responsive">
          <table class="table table-bordered mb-0 align-middle" style="min-width:520px;">
            <thead class="table-primary">
              <tr>
                <th class="hora-col">Hora</th>
                <?php foreach ($diasSemana as $i => $dia): ?>
                  <th class="dia-col text-center">
                    <?php echo $nomeDias[$i]; ?><br>
                    <small><?php echo $dia->format('d/m'); ?></small>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($horas as $hora): ?>
              <tr>
                <td class="hora-col"><?php echo $hora; ?></td>
                <?php foreach ($diasSemana as $dia):
                  $dataStr  = $dia->format('Y-m-d');
                  $slotId   = 'slot_' . $dia->format('Ymd') . '_' . str_replace(':', '', $hora);
                  $bloqueado = isset($bloqueados[$dataStr][$hora]);
                  $agendado  = $agendados[$dataStr][$hora] ?? null;
                  $passado  = $dia < $hoje || ($dia == $hoje && $hora <= date('H:i'));
                ?>
                  <td class="text-center p-1">
                    <?php if ($agendado): ?>
                      <span class="slot-agendado" title="Chamado #<?php echo (int)$agendado['id']; ?> - <?php echo htmlspecialchars($agendado['cliente']); ?>">
                        📌 <?php echo htmlspecialchars(mb_strimwidth($agendado['categoria'], 0, 12, '…')); ?><br>
                        <small><?php echo htmlspecialchars(mb_strimwidth($agendado['cliente'], 0, 10, '…')); ?></small>
                      </span>
                    <?php else: ?>
                      <input type="checkbox" class="slot-check" name="slot[]"
                             value="<?php echo $dataStr; ?>|<?php echo $hora; ?>"
                             id="<?php echo $slotId; ?>"
                             <?php echo $bloqueado ? 'checked' : ''; ?>
                             <?php echo $passado   ? 'disabled' : ''; ?>>
                      <label class="slot-label" for="<?php echo $slotId; ?>">
                        <?php
                          if ($passado)       echo 'Passado';
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
      </div>
    </div>

    <div class="d-flex align-items-center gap-3 flex-wrap">
      <button type="submit" class="btn btn-warning fw-semibold px-4">Salvar agenda</button>
      <div class="d-flex gap-2 small text-muted align-items-center flex-wrap">
        <span class="badge px-2" style="background:#d1e7dd;color:#0a3622;border:1px solid #a3cfbb;">Livre</span> disponível
        <span class="badge px-2" style="background:#f8d7da;color:#58151c;border:1px solid #f1aeb5;">Bloqueado</span> indisponível
        <span class="badge px-2" style="background:#cfe2ff;color:#084298;border:1px solid #9ec5fe;">📌 Agendado</span> chamado marcado
      </div>
    </div>
  </form>
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
