<?php
session_start();
require_once __DIR__ . '/../../controller/SolicitarChamadoControl.php';

$ctrl = new SolicitarChamadoControl();
$ctrl->processar();

$categorias      = $ctrl->categorias;
$prestadores     = $ctrl->prestadores;
$prestadorPre    = $ctrl->prestadorPre;
$categoriaPre    = $ctrl->categoriaPre;
$clienteGenero   = $ctrl->clienteGenero;
$clienteNome     = $ctrl->clienteNome;
$clienteFoto     = $ctrl->clienteFoto;
$clienteEndereco = $ctrl->clienteEndereco;
$clienteCep      = $ctrl->clienteCep;
$tecnicoInfo          = $ctrl->tecnicoInfo;
$slotsDisp            = $ctrl->slotsDisponiveis;
$categoriasPrestador  = $ctrl->categoriasPrestador;
$naoLidas        = $ctrl->naoLidas;
$mensagem        = $ctrl->mensagem;
$erro            = $ctrl->erro;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitar Serviço - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body data-cliente-genero="<?php echo htmlspecialchars($clienteGenero); ?>">
<?php $paginaAtiva = 'solicitar'; $_navDepth = 2; require_once __DIR__ . '/../../includes/cliente_nav.php'; ?>

<main class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h2 class="mb-3">Solicitar serviço técnico</h2>

          <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
          <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

          <?php if ($tecnicoInfo): ?>
          <div class="alert alert-primary d-flex align-items-center gap-3 mb-3">
            <?php if (!empty($tecnicoInfo['foto_perfil'])): ?>
              <img src="../../<?php echo htmlspecialchars($tecnicoInfo['foto_perfil']); ?>" width="48" height="48"
                   class="rounded-circle object-fit-cover flex-shrink-0" alt="Foto">
            <?php endif; ?>
            <div>
              <div class="fw-semibold">Solicitação direta para: <?php echo htmlspecialchars($tecnicoInfo['nome']); ?></div>
              <?php if ($categoriaPre !== ''): ?>
                <div class="small text-muted">Categoria: <?php echo htmlspecialchars($categoriaPre); ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" class="row g-3 js-guard-submit" id="form-solicitar">

            <div class="col-12">
              <label class="form-label">Descrição do problema <span class="text-danger">*</span></label>
              <textarea name="descricao" class="form-control" rows="4" required maxlength="2000"
                id="descricao-textarea"><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
              <small class="text-muted" id="descricao-contador">0 / 2000 caracteres</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Categoria <span class="text-danger">*</span></label>
              <?php if ($tecnicoInfo && $categoriaPre !== ''): ?>
                <!-- Categoria única ou já definida: somente leitura -->
                <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoriaPre); ?>">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($categoriaPre); ?>" disabled>
              <?php elseif ($tecnicoInfo && $categoriasPrestador): ?>
                <!-- Prestador selecionado: mostrar apenas as categorias dos serviços dele -->
                <select name="categoria" class="form-select" required>
                  <option value="">Selecione a categoria...</option>
                  <?php foreach ($categoriasPrestador as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"
                      <?php echo (($_POST['categoria'] ?? '') === $cat) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($cat); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <!-- Sem prestador fixo: todas as categorias -->
                <select name="categoria" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($categorias as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['nome']); ?>"
                      <?php echo ($categoriaPre === $c['nome'] || ($_POST['categoria'] ?? '') === $c['nome']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($c['nome']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="form-label">Fotos <span class="text-muted small">(opcional, até 6)</span></label>
              <input type="file" name="fotos[]" class="form-control" id="foto-input" multiple
                     accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
              <small class="text-muted">Selecione uma ou mais fotos do problema.</small>
            </div>

            <div class="col-12 d-none" id="foto-preview-box">
              <p class="small text-muted mb-1">Pré-visualização</p>
              <div id="foto-preview-imgs" class="d-flex flex-wrap gap-2"></div>
            </div>

            <?php if ($clienteGenero === 'Feminino'): ?>
            <div class="col-12" id="wrap-prestadora-mulher">
              <div class="form-check border rounded p-3 bg-light">
                <input class="form-check-input" type="checkbox" name="prest_feminino" value="1"
                  id="chk-prestadora-mulher" <?php echo !empty($_POST['prest_feminino']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chk-prestadora-mulher">
                  Quero <strong>somente prestadoras mulheres</strong> para este serviço.
                </label>
              </div>
            </div>
            <?php endif; ?>

            <div class="col-md-6">
              <label class="form-label">Endereço do serviço <span class="text-danger">*</span></label>
              <input type="text" name="endereco" class="form-control" required maxlength="200"
                     value="<?php echo htmlspecialchars($_POST['endereco'] ?? (trim($clienteEndereco . ($clienteCep ? ' - CEP: ' . $clienteCep : '')))); ?>">
            </div>

            <?php if ($tecnicoInfo): ?>
            <div class="col-md-6">
              <input type="hidden" name="tecnico_id_selecionado" value="<?php echo (int)$tecnicoInfo['id']; ?>">
              <label class="form-label">Horário disponível</label>
              <?php if ($slotsDisp): ?>
                <select name="slot_selecionado" class="form-select" required>
                  <option value="">Selecione um horário...</option>
                  <?php
                    $nomeDias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
                    foreach ($slotsDisp as $dataSlot => $horasSlot):
                        $dtSlot = new DateTime($dataSlot);
                        $diaFmt = $nomeDias[(int)$dtSlot->format('w')] . ', ' . $dtSlot->format('d/m');
                  ?>
                  <optgroup label="<?php echo htmlspecialchars($diaFmt); ?>">
                    <?php foreach ($horasSlot as $hSlot): ?>
                      <option value="<?php echo $dataSlot; ?>|<?php echo $hSlot; ?>"
                        <?php echo (($_POST['slot_selecionado'] ?? '') === "$dataSlot|$hSlot") ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($diaFmt . ' às ' . $hSlot); ?>
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <div class="alert alert-warning py-2 mb-0">
                  Este prestador não possui horários disponíveis no momento.
                  <a href="../catalogo.php" class="alert-link">Escolha outro prestador</a>.
                </div>
              <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="col-md-6">
              <label class="form-label">Data e hora desejada <span class="text-muted small">(opcional)</span></label>
              <input type="datetime-local" name="data_agendamento" class="form-control"
                     min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                     value="<?php echo htmlspecialchars($_POST['data_agendamento'] ?? ''); ?>">
            </div>
            <?php endif; ?>

            <div class="col-12">
              <button type="submit" class="btn btn-warning fw-semibold" id="btn-abrir-chamado"
                <?php echo ($tecnicoInfo && !$slotsDisp) ? 'disabled' : ''; ?>>Abrir chamado</button>
              <a href="../dashboardCliente.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/forms-helpers.js"></script>
<script src="../../assets/js/solicitar.js"></script>
</body>
</html>
