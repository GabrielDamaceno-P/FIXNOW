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

$fotosPorCat = ['__sem__' => []];
foreach ($categorias as $c) $fotosPorCat[$c['id']] = [];
foreach ($fotos as $f) {
    $cid = $f['categoria_id'] ?? null;
    if ($cid && isset($fotosPorCat[$cid])) $fotosPorCat[$cid][] = $f;
    else $fotosPorCat['__sem__'][] = $f;
}

$catIcons = ['Suporte TI'=>'💻','Elétrica'=>'⚡','Hidráulica'=>'🔧','Pintura'=>'🎨','Marcenaria'=>'🪚','Jardinagem'=>'🌿','Limpeza'=>'🧹','Refrigeração'=>'❄️'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portfólio - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-header{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-header::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-header h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-header p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .form-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.5rem;position:sticky;top:80px}
    @media(max-width:991.98px){.form-card{position:relative;top:0}}
    .form-card-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:1.1rem;padding-bottom:.7rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}
    .upload-zone{border:2px dashed #ced4da;border-radius:10px;padding:1.3rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s}
    .upload-zone:hover{border-color:#ffc107;background:#fffbf0}
    .upload-zone.tem-fotos{border-color:#198754;background:#f0fff4;border-style:solid}
    .upload-zone input[type="file"]{display:none}
    .upload-zone .ui{font-size:1.8rem;margin-bottom:.3rem}
    .upload-zone p{font-size:.85rem;font-weight:600;margin:.1rem 0 0}
    .upload-zone small{font-size:.75rem;color:#8090b0}
    .preview-grid{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.5rem}
    .preview-thumb{position:relative;width:70px;height:70px;border-radius:8px;overflow:hidden;border:2px solid #198754;flex-shrink:0}
    .preview-thumb img{width:100%;height:100%;object-fit:cover;display:block}
    .preview-thumb span{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.55);color:#fff;font-size:.55rem;text-align:center;padding:1px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    [data-theme="dark"] .upload-zone.tem-fotos{background:#0d2318;border-color:#198754}
    .tab-filtro{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1.2rem}
    .tab-btn{border:1.5px solid #e8ecf3;border-radius:50px;padding:.3rem .85rem;font-size:.8rem;font-weight:600;color:#495057;background:#fff;cursor:pointer;transition:all .18s;white-space:nowrap}
    .tab-btn:hover,.tab-btn.ativo{background:#0d1b3d;border-color:#0d1b3d;color:#fff}
    .foto-card{border-radius:12px;overflow:hidden;border:1.5px solid #e8ecf3;background:#fff;box-shadow:0 3px 10px rgba(13,27,61,.06);transition:transform .18s,box-shadow .18s}
    .foto-card:hover{transform:translateY(-3px);box-shadow:0 10px 22px rgba(13,27,61,.12)}
    .foto-card img{width:100%;height:180px;object-fit:cover;cursor:pointer;display:block}
    .foto-card-body{padding:.7rem .9rem}
    .foto-card-footer{padding:.5rem .9rem .8rem;display:flex;gap:.4rem}
    /* DARK */
    [data-theme="dark"] .form-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .form-card-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .upload-zone{border-color:#2e3650}
    [data-theme="dark"] .upload-zone:hover{background:#252d42;border-color:#ffc107}
    [data-theme="dark"] .upload-zone p{color:#c8d0e0}
    [data-theme="dark"] .tab-btn{background:#252d42;border-color:#2e3650;color:#c8d0e0}
    [data-theme="dark"] .tab-btn:hover,[data-theme="dark"] .tab-btn.ativo{background:#ffc107;border-color:#ffc107;color:#0d1b3d}
    [data-theme="dark"] .foto-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .foto-card-body .card-title{color:#e4e8f4}
    [data-theme="dark"] .foto-card-body .card-text,[data-theme="dark"] .foto-card-body small{color:#8090b0 !important}
  </style>
</head>
<body>
<?php $paginaAtiva = 'portfolio'; require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-4 mt-5">

  <div class="page-header">
    <div style="position:relative;z-index:1">
      <h1>🖼 Meu Portfólio</h1>
      <p>Mostre seu trabalho — as fotos ficam visíveis para clientes no catálogo.</p>
    </div>
  </div>

  <?php if ($mensagem): ?><div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($mensagem); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($erro); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

  <div class="row g-4">
    <!-- Formulário -->
    <div class="col-lg-4">
      <div class="form-card">
        <div class="form-card-titulo"><span>📤</span> <span id="formTitulo">Adicionar fotos</span></div>
        <form method="post" enctype="multipart/form-data" class="js-guard-submit" id="formPortfolio">
          <input type="hidden" name="acao" id="f_acao" value="adicionar">
          <input type="hidden" name="foto_id" id="f_id" value="0">

          <div class="mb-3">
            <label class="upload-zone w-100" for="f_foto" id="uploadZone">
              <div class="ui" id="uploadIcon">📷</div>
              <p id="fotoLabel">Clique para selecionar fotos</p>
              <small id="fotoHint">JPG, PNG ou WEBP &bull; Até 10 arquivos</small>
              <input type="file" name="fotos[]" id="f_foto" multiple
                     accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                     onchange="previewFotos(this)">
            </label>
            <div id="previewGrid" style="display:none;margin-top:.6rem;display:none"></div>
          </div>

          <?php if ($categorias): ?>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Categoria</label>
            <select name="categoria_id" id="f_cat" class="form-select">
              <option value="">— Sem categoria —</option>
              <?php foreach ($categorias as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Título <span class="text-muted fw-normal">(opcional)</span></label>
            <input type="text" name="titulo" id="f_titulo" class="form-control" maxlength="100" placeholder="Ex: Instalação concluída">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Descrição <span class="text-muted fw-normal">(opcional)</span></label>
            <textarea name="descricao" id="f_desc" class="form-control" rows="2" maxlength="255" placeholder="Contexto do trabalho realizado..."></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-bold flex-grow-1">Salvar</button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Galeria -->
    <div class="col-lg-8">
      <?php if (!$fotos): ?>
        <div class="text-center py-5 text-muted">
          <div style="font-size:3rem;margin-bottom:.6rem;">🖼</div>
          <h5 class="fw-bold" style="color:#0d1b3d;">Portfólio vazio</h5>
          <p style="font-size:.9rem;">Adicione fotos dos seus trabalhos para atrair mais clientes.</p>
        </div>
      <?php else: ?>

        <!-- Filtros de categoria -->
        <div class="tab-filtro" id="portfolio-tabs">
          <button class="tab-btn ativo" data-tab="todos">🗂 Todos (<?php echo count($fotos); ?>)</button>
          <?php foreach ($categorias as $c):
            $cnt = count($fotosPorCat[$c['id']] ?? []);
            if ($cnt > 0):
              $ico = $catIcons[$c['nome']] ?? '🔩';
          ?>
            <button class="tab-btn" data-tab="cat-<?php echo (int)$c['id']; ?>">
              <?php echo $ico . ' ' . htmlspecialchars($c['nome']); ?> (<?php echo $cnt; ?>)
            </button>
          <?php endif; endforeach; ?>
          <?php if (!empty($fotosPorCat['__sem__'])): ?>
            <button class="tab-btn" data-tab="sem-cat">Sem categoria (<?php echo count($fotosPorCat['__sem__']); ?>)</button>
          <?php endif; ?>
        </div>

        <div class="row g-3" id="galeria">
          <?php foreach ($fotos as $f): ?>
          <div class="col-sm-6 foto-item" data-cat="<?php echo $f['categoria_id'] ? 'cat-'.(int)$f['categoria_id'] : 'sem-cat'; ?>">
            <div class="foto-card">
              <img src="../../<?php echo htmlspecialchars($f['foto_path']); ?>" alt="Portfólio"
                   onclick="window.open('../../<?php echo htmlspecialchars($f['foto_path']); ?>','_blank')"
                   loading="lazy">
              <div class="foto-card-body">
                <?php if (!empty($f['categoria_nome'])): ?>
                  <span class="badge mb-1" style="background:#0d1b3d;color:#ffc107;font-size:.7rem;">
                    <?php echo ($catIcons[$f['categoria_nome']] ?? '🔩') . ' ' . htmlspecialchars($f['categoria_nome']); ?>
                  </span>
                <?php endif; ?>
                <?php if ($f['titulo']): ?>
                  <h6 class="card-title mb-0 mt-1" style="font-size:.88rem;"><?php echo htmlspecialchars($f['titulo']); ?></h6>
                <?php endif; ?>
                <?php if ($f['descricao']): ?>
                  <p class="card-text small text-muted mt-1 mb-0" style="font-size:.78rem;"><?php echo htmlspecialchars($f['descricao']); ?></p>
                <?php endif; ?>
                <small class="text-muted d-block mt-1" style="font-size:.74rem;">📅 <?php echo date('d/m/Y', strtotime($f['criado_em'])); ?></small>
              </div>
              <div class="foto-card-footer">
                <button class="btn btn-sm btn-outline-warning flex-grow-1"
                  onclick="editarFoto(<?php echo (int)$f['id']; ?>,'<?php echo addslashes($f['titulo'] ?? ''); ?>','<?php echo addslashes($f['descricao'] ?? ''); ?>',<?php echo (int)($f['categoria_id'] ?? 0); ?>)">
                  Editar
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
function previewFotos(input) {
  var zone  = document.getElementById('uploadZone');
  var icon  = document.getElementById('uploadIcon');
  var label = document.getElementById('fotoLabel');
  var hint  = document.getElementById('fotoHint');
  var grid  = document.getElementById('previewGrid');

  grid.innerHTML = '';
  grid.className = 'preview-grid';

  if (!input.files || input.files.length === 0) {
    zone.classList.remove('tem-fotos');
    icon.textContent = '📷';
    label.textContent = 'Clique para selecionar fotos';
    hint.textContent  = 'JPG, PNG ou WEBP • Até 10 arquivos';
    grid.style.display = 'none';
    return;
  }

  var total = Math.min(input.files.length, 10);
  zone.classList.add('tem-fotos');
  icon.textContent = '✅';
  label.textContent = total === 1 ? input.files[0].name : total + ' fotos selecionadas';
  hint.textContent  = 'Clique para trocar • ' + total + (total === 1 ? ' arquivo' : ' arquivos');
  grid.style.display = 'flex';

  for (var i = 0; i < total; i++) {
    (function(file) {
      var thumb = document.createElement('div');
      thumb.className = 'preview-thumb';
      var img = document.createElement('img');
      img.alt = file.name;
      var reader = new FileReader();
      reader.onload = function(e) { img.src = e.target.result; };
      reader.readAsDataURL(file);
      var name = document.createElement('span');
      name.textContent = file.name;
      thumb.appendChild(img);
      thumb.appendChild(name);
      grid.appendChild(thumb);
    })(input.files[i]);
  }
}

function editarFoto(id, titulo, desc, catId) {
  document.getElementById('f_id').value      = id;
  document.getElementById('f_acao').value    = 'editar';
  document.getElementById('f_titulo').value  = titulo;
  document.getElementById('f_desc').value    = desc;
  var catSel = document.getElementById('f_cat');
  if (catSel) catSel.value = catId || '';
  var fotoInput = document.getElementById('f_foto');
  fotoInput.required = false;
  fotoInput.disabled = false;
  document.getElementById('uploadIcon').textContent = '✏️';
  document.getElementById('fotoLabel').textContent  = 'Selecionar nova foto (opcional)';
  document.getElementById('fotoHint').textContent   = 'Deixe em branco para manter a foto atual.';
  document.getElementById('uploadZone').classList.remove('tem-fotos');
  var grid = document.getElementById('previewGrid');
  grid.innerHTML = '';
  grid.style.display = 'none';
  document.getElementById('formTitulo').textContent = 'Editar foto #' + id;
  document.getElementById('formPortfolio').scrollIntoView({behavior:'smooth'});
}
function resetForm() {
  document.getElementById('f_id').value   = 0;
  document.getElementById('f_acao').value = 'adicionar';
  document.getElementById('formPortfolio').reset();
  var fotoInput = document.getElementById('f_foto');
  fotoInput.required = true;
  fotoInput.disabled = false;
  document.getElementById('uploadIcon').textContent = '📷';
  document.getElementById('fotoLabel').textContent  = 'Clique para selecionar fotos';
  document.getElementById('fotoHint').textContent   = 'JPG, PNG ou WEBP • Até 10 arquivos';
  document.getElementById('uploadZone').classList.remove('tem-fotos');
  var grid = document.getElementById('previewGrid');
  grid.innerHTML = '';
  grid.style.display = 'none';
  document.getElementById('formTitulo').textContent = 'Adicionar fotos';
}

document.querySelectorAll('#portfolio-tabs .tab-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('#portfolio-tabs .tab-btn').forEach(b => b.classList.remove('ativo'));
    this.classList.add('ativo');
    var tab = this.dataset.tab;
    document.querySelectorAll('.foto-item').forEach(function(item) {
      item.style.display = (tab === 'todos' || item.dataset.cat === tab) ? '' : 'none';
    });
  });
});
</script>
</body>
</html>
