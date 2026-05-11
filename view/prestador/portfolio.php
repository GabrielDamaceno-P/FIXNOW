<?php
session_start();
require_once __DIR__ . '/../../controller/PortfolioControl.php';

$ctrl = new PortfolioControl();
$ctrl->processar();
$fotos      = $ctrl->fotos;
$categorias = $ctrl->categorias;
$mensagem   = $ctrl->mensagem;
$erro       = $ctrl->erro;
$naoLidas   = $ctrl->naoLidas;
$tecnicoNome = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto = $_SESSION['tecnico_foto'] ?? '';

// Agrupa fotos por categoria
$fotosPorCat = ['__sem__' => []];
foreach ($categorias as $c) $fotosPorCat[$c['id']] = [];
foreach ($fotos as $f) {
    $cid = $f['categoria_id'] ?? null;
    if ($cid && isset($fotosPorCat[$cid])) $fotosPorCat[$cid][] = $f;
    else $fotosPorCat['__sem__'][] = $f;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portfólio - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php $paginaAtiva = 'portfolio'; require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-5 mt-5">
  <h2 class="mb-4">Meu Portfólio</h2>

  <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- Formulário -->
    <div class="col-lg-4">
      <div class="card shadow-sm border-0 sticky-top" style="top:80px;">
        <div class="card-body">
          <h5 id="formTitulo">Adicionar fotos</h5>
          <form method="post" enctype="multipart/form-data" class="js-guard-submit" id="formPortfolio">
            <input type="hidden" name="acao" id="f_acao" value="adicionar">
            <input type="hidden" name="foto_id" id="f_id" value="0">

            <div class="mb-3">
              <label class="form-label">Fotos <span class="text-danger" id="fotoObrig">*</span></label>
              <input type="file" name="fotos[]" id="f_foto" class="form-control" multiple
                     accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
              <small class="text-muted" id="fotoHint">Selecione uma ou mais fotos (máx. 10).</small>
            </div>

            <?php if ($categorias): ?>
            <div class="mb-3">
              <label class="form-label">Categoria do serviço</label>
              <select name="categoria_id" id="f_cat" class="form-select">
                <option value="">— Sem categoria —</option>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">Título <span class="text-muted small">(opcional)</span></label>
              <input type="text" name="titulo" id="f_titulo" class="form-control" maxlength="100">
            </div>
            <div class="mb-3">
              <label class="form-label">Descrição <span class="text-muted small">(opcional)</span></label>
              <textarea name="descricao" id="f_desc" class="form-control" rows="2" maxlength="255"></textarea>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-warning fw-semibold">Salvar</button>
              <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Galeria por categoria -->
    <div class="col-lg-8">
      <?php if (!$fotos): ?>
        <div class="alert alert-info">Nenhuma foto no portfólio ainda.</div>
      <?php else: ?>

        <?php if ($categorias): ?>
        <!-- Tabs de categoria -->
        <ul class="nav nav-pills mb-3 flex-wrap gap-1" id="portfolio-tabs">
          <li class="nav-item">
            <button class="nav-link active" data-tab="todos">Todos (<?php echo count($fotos); ?>)</button>
          </li>
          <?php foreach ($categorias as $c): ?>
            <?php $cnt = count($fotosPorCat[$c['id']] ?? []); ?>
            <?php if ($cnt > 0): ?>
            <li class="nav-item">
              <button class="nav-link" data-tab="cat-<?php echo (int)$c['id']; ?>">
                <?php echo htmlspecialchars($c['nome']); ?> (<?php echo $cnt; ?>)
              </button>
            </li>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if (!empty($fotosPorCat['__sem__'])): ?>
          <li class="nav-item">
            <button class="nav-link" data-tab="sem-cat">Sem categoria (<?php echo count($fotosPorCat['__sem__']); ?>)</button>
          </li>
          <?php endif; ?>
        </ul>
        <?php endif; ?>

        <div class="row g-3" id="galeria">
        <?php foreach ($fotos as $f): ?>
          <div class="col-sm-6 foto-item"
               data-cat="<?php echo $f['categoria_id'] ? 'cat-' . (int)$f['categoria_id'] : 'sem-cat'; ?>">
            <div class="card shadow-sm border-0 h-100">
              <img src="../../<?php echo htmlspecialchars($f['foto_path']); ?>" class="card-img-top" alt="Portfólio"
                   style="height:180px;object-fit:cover;cursor:pointer;"
                   onclick="window.open('../../<?php echo htmlspecialchars($f['foto_path']); ?>','_blank')"
                   loading="lazy">
              <div class="card-body py-2">
                <?php if (!empty($f['categoria_nome'])): ?>
                  <span class="badge bg-primary mb-1"><?php echo htmlspecialchars($f['categoria_nome']); ?></span>
                <?php endif; ?>
                <?php if ($f['titulo']): ?><h6 class="card-title mb-1 mt-1"><?php echo htmlspecialchars($f['titulo']); ?></h6><?php endif; ?>
                <?php if ($f['descricao']): ?><p class="card-text small text-muted mb-1"><?php echo htmlspecialchars($f['descricao']); ?></p><?php endif; ?>
                <small class="text-muted"><?php echo date('d/m/Y', strtotime($f['criado_em'])); ?></small>
              </div>
              <div class="card-footer bg-transparent d-flex gap-1">
                <button class="btn btn-sm btn-outline-warning flex-grow-1"
                  onclick="editarFoto(<?php echo (int)$f['id']; ?>,'<?php echo addslashes($f['titulo'] ?? ''); ?>','<?php echo addslashes($f['descricao'] ?? ''); ?>',<?php echo (int)($f['categoria_id'] ?? 0); ?>)">
                  Editar info
                </button>
                <form method="post" class="d-inline flex-grow-1" onsubmit="return confirm('Remover foto?')">
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="foto_id" value="<?php echo (int)$f['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger w-100">Remover</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
function editarFoto(id, titulo, desc, catId) {
  document.getElementById('f_id').value = id;
  document.getElementById('f_acao').value = 'editar';
  document.getElementById('f_titulo').value = titulo;
  document.getElementById('f_desc').value = desc;
  var catSel = document.getElementById('f_cat');
  if (catSel) catSel.value = catId || '';
  var fotoInput = document.getElementById('f_foto');
  fotoInput.required = false;
  fotoInput.disabled = true;
  document.getElementById('fotoObrig').style.display = 'none';
  document.getElementById('fotoHint').textContent = 'Upload desativado ao editar informações.';
  document.getElementById('formTitulo').textContent = 'Editar foto #' + id;
  document.getElementById('formPortfolio').scrollIntoView({behavior:'smooth'});
}
function resetForm() {
  document.getElementById('f_id').value = 0;
  document.getElementById('f_acao').value = 'adicionar';
  document.getElementById('formPortfolio').reset();
  var fotoInput = document.getElementById('f_foto');
  fotoInput.required = true;
  fotoInput.disabled = false;
  document.getElementById('fotoObrig').style.display = '';
  document.getElementById('fotoHint').textContent = 'Selecione uma ou mais fotos (máx. 10).';
  document.getElementById('formTitulo').textContent = 'Adicionar fotos';
}

// Filtro por categoria (tabs)
document.querySelectorAll('#portfolio-tabs .nav-link').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('#portfolio-tabs .nav-link').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    var tab = this.dataset.tab;
    document.querySelectorAll('.foto-item').forEach(function(item) {
      if (tab === 'todos' || item.dataset.cat === tab) {
        item.style.display = '';
      } else {
        item.style.display = 'none';
      }
    });
  });
});
</script>
</body>
</html>
