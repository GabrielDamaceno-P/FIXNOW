<?php
session_start();
require_once __DIR__ . '/../controller/RastreamentoChamadoControl.php';

$ctrl = new RastreamentoChamadoControl();
$ctrl->processar();

$chamado     = $ctrl->chamado;
$nomeTec     = $ctrl->tecnicoNome;
$nomeCli     = $ctrl->clienteNome;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rastreamento - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    #map {
      height: 420px;
      border-radius: 14px;
      box-shadow: 0 10px 28px rgba(0,0,0,.12);
      background: #dfe6ee;
    }
    .marker-tecnico-inner {
      display: block; width: 28px; height: 28px; border-radius: 50%;
      background: radial-gradient(circle at 30% 30%, #ffb347, #ff7a00);
      border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,.35);
    }
  </style>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
<?php $paginaAtiva = 'rastreamento'; $_navDepth = 1; require_once __DIR__ . '/../includes/cliente_nav.php'; ?>

<main class="container py-5 mt-5">
  <h2 class="mb-3">Rastreamento do chamado</h2>

  <?php if (!$chamado): ?>
    <div class="alert alert-warning">Nenhum chamado encontrado para rastreamento.</div>
  <?php else: ?>
    <div class="alert alert-info">
      <?php echo htmlspecialchars($nomeTec); ?> —
      ETA: <strong><span id="eta-dinamico"><?php echo !empty($chamado['em_deslocamento']) ? 'calculando...' : '—'; ?></span></strong>.
      Status do chamado: <strong><?php echo htmlspecialchars($chamado['status']); ?></strong>.
    </div>

    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Cliente</span>
          <?php if (!empty($chamado['cliente_foto'])): ?>
            <button type="button" class="btn btn-sm btn-outline-primary js-open-foto"
              data-titulo="<?php echo htmlspecialchars($nomeCli, ENT_QUOTES); ?>"
              data-foto="../<?php echo htmlspecialchars($chamado['cliente_foto'], ENT_QUOTES); ?>">Ver foto</button>
          <?php else: ?>
            <span class="small text-muted">sem foto</span>
          <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Prestador</span>
          <?php if (!empty($chamado['tecnico_foto'])): ?>
            <button type="button" class="btn btn-sm btn-outline-primary js-open-foto"
              data-titulo="<?php echo htmlspecialchars($nomeTec, ENT_QUOTES); ?>"
              data-foto="../<?php echo htmlspecialchars($chamado['tecnico_foto'], ENT_QUOTES); ?>">Ver foto</button>
          <?php else: ?>
            <span class="small text-muted">sem foto</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($chamado['descricao'])): ?>
          <div class="w-100 small"><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($chamado['descricao'])); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div id="map"
      data-chamado-id="<?php echo (int)$chamado['id']; ?>"
      data-lat-servico="<?php echo $chamado['lat_servico'] !== null ? htmlspecialchars($chamado['lat_servico'], ENT_QUOTES) : ''; ?>"
      data-lng-servico="<?php echo $chamado['lng_servico'] !== null ? htmlspecialchars($chamado['lng_servico'], ENT_QUOTES) : ''; ?>"
      data-endereco-servico="<?php echo htmlspecialchars($chamado['endereco_servico'] ?? '', ENT_QUOTES); ?>"
      data-tecnico-nome="<?php echo htmlspecialchars($nomeTec, ENT_QUOTES); ?>">
    </div>
    <?php if (empty($chamado['em_deslocamento'])): ?>
    <p class="text-muted small text-center mt-2" id="rastreamento-status">Aguardando prestador iniciar deslocamento...</p>
    <?php else: ?>
    <p class="text-success small text-center mt-2" id="rastreamento-status">Prestador a caminho!</p>
    <?php endif; ?>
  <?php endif; ?>
</main>

<div class="modal fade" id="modalFotoFixnow" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-fn-foto-titulo>Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" alt="" class="img-fluid rounded shadow-sm" data-fn-foto-img style="max-height:70vh;">
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($chamado): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/rastreamento-mapa.js"></script>
<script src="../assets/js/foto-lightbox.js"></script>
<?php endif; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>
