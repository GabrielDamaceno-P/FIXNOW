<?php
session_start();
require_once __DIR__ . '/../../controller/ServicosControl.php';

$ctrl = new ServicosControl();
$ctrl->processar();
$servicos   = $ctrl->servicos;
$categorias = $ctrl->categorias;
$mensagem   = $ctrl->mensagem;
$erro       = $ctrl->erro;
$naoLidas   = $ctrl->naoLidas;
$tecnicoNome = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto = $_SESSION['tecnico_foto'] ?? '';

$catIcons = ['Suporte TI'=>'💻','Elétrica'=>'⚡','Hidráulica'=>'🔧','Pintura'=>'🎨','Marcenaria'=>'🪚','Jardinagem'=>'🌿','Limpeza'=>'🧹','Refrigeração'=>'❄️'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meus Serviços - Fix Now</title>
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
    .servico-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:12px;padding:1rem 1.1rem;margin-bottom:.75rem;display:flex;align-items:flex-start;gap:.9rem;transition:box-shadow .18s}
    .servico-card:hover{box-shadow:0 4px 14px rgba(13,27,61,.1)}
    .servico-cat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;background:#f0f3fa}
    .servico-nome{font-weight:700;font-size:.92rem;color:#0d1b3d;margin-bottom:.15rem}
    .servico-cat-label{font-size:.77rem;color:#667085}
    .servico-desc{font-size:.8rem;color:#667085;margin-top:.2rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    /* DARK */
    [data-theme="dark"] .form-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .form-card-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .servico-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .servico-card:hover{box-shadow:0 4px 14px rgba(0,0,0,.3)}
    [data-theme="dark"] .servico-cat-icon{background:#252d42}
    [data-theme="dark"] .servico-nome{color:#e4e8f4}
    [data-theme="dark"] .servico-cat-label,[data-theme="dark"] .servico-desc{color:#8090b0}
  </style>
</head>
<body>
<?php $paginaAtiva = 'servicos'; require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-4 mt-5">

  <div class="page-header">
    <div style="position:relative;z-index:1">
      <h1>🔧 Meus Serviços</h1>
      <p>Gerencie as especialidades que você oferece. Elas definem quais chamados aparecem para você.</p>
    </div>
  </div>

  <?php if ($mensagem): ?><div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($mensagem); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($erro); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

  <div class="row g-4">
    <!-- Formulário -->
    <div class="col-lg-4">
      <div class="form-card">
        <div class="form-card-titulo"><span>➕</span> <span id="formTitulo">Novo serviço</span></div>
        <form method="post" class="js-guard-submit" id="formServico">
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="servico_id" id="servico_id" value="0">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Nome <span class="text-danger">*</span></label>
            <input type="text" name="nome" id="f_nome" class="form-control" required maxlength="150" placeholder="Ex: Instalação elétrica">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Categoria</label>
            <select name="categoria_id" id="f_cat" class="form-select">
              <option value="">— Sem categoria —</option>
              <?php foreach ($categorias as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Descrição</label>
            <textarea name="descricao" id="f_desc" class="form-control" rows="3" maxlength="500" placeholder="Descreva brevemente este serviço..."></textarea>
          </div>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input type="checkbox" name="ativo" id="f_ativo" class="form-check-input" value="1" checked role="switch">
              <label class="form-check-label fw-semibold" for="f_ativo" style="font-size:.88rem;">Serviço ativo</label>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-bold flex-grow-1">Salvar</button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lista de serviços -->
    <div class="col-lg-8">
      <?php if (!$servicos): ?>
        <div class="text-center py-5 text-muted">
          <div style="font-size:3rem;margin-bottom:.6rem;">🛠</div>
          <h5 class="fw-bold" style="color:#0d1b3d;">Nenhum serviço cadastrado</h5>
          <p style="font-size:.9rem;">Adicione seus serviços para aparecer no catálogo e receber chamados.</p>
        </div>
      <?php else: ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted" style="font-size:.85rem;"><strong><?php echo count($servicos); ?></strong> serviço<?php echo count($servicos) !== 1 ? 's' : ''; ?> cadastrado<?php echo count($servicos) !== 1 ? 's' : ''; ?></span>
        </div>
        <?php foreach ($servicos as $s):
          $ico = $catIcons[$s->categoriaNome ?? ''] ?? '🔩';
        ?>
        <div class="servico-card">
          <div class="servico-cat-icon"><?php echo $ico; ?></div>
          <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div>
                <div class="servico-nome"><?php echo htmlspecialchars($s->nome); ?></div>
                <div class="servico-cat-label"><?php echo htmlspecialchars($s->categoriaNome ?? '—'); ?></div>
              </div>
              <span class="badge bg-<?php echo $s->ativo ? 'success' : 'secondary'; ?> flex-shrink-0">
                <?php echo $s->ativo ? 'Ativo' : 'Inativo'; ?>
              </span>
            </div>
            <?php if (!empty($s->descricao)): ?>
              <div class="servico-desc mt-1"><?php echo htmlspecialchars($s->descricao); ?></div>
            <?php endif; ?>
            <div class="d-flex gap-1 mt-2">
              <button class="btn btn-sm btn-outline-warning"
                onclick="editarServico(<?php echo $s->id; ?>,'<?php echo addslashes($s->nome); ?>',<?php echo (int)$s->categoriaId; ?>,'<?php echo addslashes($s->descricao); ?>',<?php echo $s->ativo; ?>)">
                Editar
              </button>
              <form method="post" class="d-inline" onsubmit="return confirm('Remover serviço?')">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="servico_id" value="<?php echo $s->id; ?>">
                <button class="btn btn-sm btn-outline-danger">Remover</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
function editarServico(id, nome, catId, desc, ativo) {
  document.getElementById('servico_id').value = id;
  document.getElementById('f_nome').value = nome;
  document.getElementById('f_cat').value = catId || '';
  document.getElementById('f_desc').value = desc;
  document.getElementById('f_ativo').checked = ativo == 1;
  document.getElementById('formTitulo').textContent = 'Editar serviço #' + id;
  document.getElementById('formServico').scrollIntoView({behavior:'smooth'});
}
function resetForm() {
  document.getElementById('servico_id').value = 0;
  document.getElementById('formServico').reset();
  document.getElementById('formTitulo').textContent = 'Novo serviço';
}
</script>
</body>
</html>
