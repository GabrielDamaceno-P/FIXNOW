<?php
session_start();
require_once __DIR__ . '/../../controller/OrcamentoPrestadorControl.php';

$ctrl = new OrcamentoPrestadorControl();
$ctrl->processar();
$chamadosDisp       = $ctrl->chamadosDisponiveis;
$meusOrcamentos     = $ctrl->meusOrcamentos;
$chamadoSelecionado = $ctrl->chamadoSelecionado;
$mensagem           = $ctrl->mensagem;
$erro               = $ctrl->erro;
$naoLidas           = $ctrl->naoLidas;
$tecnicoNome        = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto        = $_SESSION['tecnico_foto'] ?? '';

$niveis = [['Simples','80'],['Básico','150'],['Intermediário','280'],['Avançado','450'],['Premium','700'],['Urgente','900']];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orçamentos - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
  <style>
    .page-header{background:linear-gradient(135deg,#0d1b3d 0%,#1a2b63 60%,#c95e00 100%);border-radius:16px;padding:1.8rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
    .page-header::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-header h1{color:#fff;font-size:clamp(1.2rem,3vw,1.7rem);font-weight:800;margin:0 0 .25rem}
    .page-header p{color:rgba(255,255,255,.72);font-size:.9rem;margin:0}
    .form-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;padding:1.5rem}
    .form-card-titulo{font-weight:700;font-size:.95rem;color:#0d1b3d;margin-bottom:1.1rem;padding-bottom:.7rem;border-bottom:2px solid #f0f3fa;display:flex;align-items:center;gap:.5rem}
    .nivel-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(80px,1fr));gap:.4rem;margin-bottom:.5rem}
    .nivel-btn{border:1.5px solid #e8ecf3;border-radius:10px;padding:.5rem .4rem;text-align:center;cursor:pointer;transition:all .18s;background:#fff;width:100%}
    .nivel-btn:hover{border-color:#ffc107;background:#fffbf0}
    .nivel-btn.sel{border-color:#ffc107;background:#fff3cd}
    .nivel-btn .nt{font-size:.72rem;font-weight:700;color:#0d1b3d;display:block}
    .nivel-btn .np{font-size:.84rem;font-weight:800;color:#f59e0b;display:block}
    .nivel-custom-btn{border:1.5px dashed #cbd5e1;border-radius:10px;padding:.4rem .75rem;cursor:pointer;transition:all .18s;background:transparent;font-size:.78rem;font-weight:700;color:#6b7280;display:flex;align-items:center;gap:.35rem;margin-bottom:.75rem}
    .nivel-custom-btn:hover{border-color:#ffc107;color:#f59e0b}
    .nivel-custom-btn.sel{border-color:#ffc107;color:#f59e0b;background:#fffbf0}
    .detalhe-card{background:#fff;border:1.5px solid #e8ecf3;border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
    .detalhe-header{background:linear-gradient(135deg,#0d1b3d,#1a2b63);padding:.85rem 1.2rem;display:flex;align-items:center;justify-content:space-between}
    .detalhe-header h6{color:#fff;margin:0;font-weight:700;font-size:.92rem}
    .detalhe-body{padding:1.1rem}
    .info-row{display:flex;gap:1.2rem;flex-wrap:wrap;margin-bottom:.8rem}
    .info-row .lbl{font-size:.72rem;color:#8090b0;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.1rem}
    .info-row .val{font-size:.86rem;font-weight:600;color:#1a2b63}
    .desc-box{background:#f8f9fc;border:1px solid #e8ecf3;border-radius:8px;padding:.7rem .9rem;font-size:.84rem;white-space:pre-wrap;color:#374151}
    .orc-item{display:flex;align-items:flex-start;gap:.75rem;padding:.9rem 0;border-bottom:1px solid #f0f3fa}
    .orc-item:last-child{border-bottom:none}
    .orc-strip{width:3px;border-radius:3px;flex-shrink:0;align-self:stretch;min-height:44px}
    .s-ok{background:#16a34a}.s-no{background:#dc2626}.s-pend{background:#f59e0b}
    .orc-valor{font-size:1.05rem;font-weight:800;color:#0d1b3d}
    .orc-sub{font-size:.76rem;color:#8090b0}
    /* DARK */
    [data-theme="dark"] .form-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .form-card-titulo{color:#e4e8f4;border-bottom-color:#2e3650}
    [data-theme="dark"] .nivel-btn{background:#252d42;border-color:#2e3650}
    [data-theme="dark"] .nivel-btn .nt{color:#c8d0e0}
    [data-theme="dark"] .nivel-btn:hover,.nivel-btn.sel{background:#2a2010;border-color:#ffc107}
    [data-theme="dark"] .nivel-custom-btn{border-color:#3a4560;color:#8090b0}
    [data-theme="dark"] .nivel-custom-btn:hover,[data-theme="dark"] .nivel-custom-btn.sel{border-color:#ffc107;color:#f59e0b;background:#2a2010}
    [data-theme="dark"] .detalhe-card{background:#1e2538;border-color:#2e3650}
    [data-theme="dark"] .detalhe-body{background:#1e2538}
    [data-theme="dark"] .info-row .val{color:#c8d0e0}
    [data-theme="dark"] .desc-box{background:#252d42;border-color:#2e3650;color:#c8d0e0}
    [data-theme="dark"] .orc-item{border-bottom-color:#2e3650}
    [data-theme="dark"] .orc-valor{color:#e4e8f4}
    [data-theme="dark"] .orc-sub{color:#5a6a8a}
  </style>
</head>
<body>
<?php $paginaAtiva = 'orcamentos'; require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-4 mt-5">

  <div class="page-header">
    <div style="position:relative;z-index:1">
      <h1>💬 Orçamentos</h1>
      <p>Envie propostas para chamados disponíveis — abertos ou solicitações diretas para você.</p>
    </div>
  </div>

  <?php if (!empty($_GET['aceito'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      ✅ <strong>Chamado aceito!</strong> Preencha o formulário e envie o orçamento para o cliente.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($mensagem): ?><div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($mensagem); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($erro); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

  <?php
    $chamadosJson = [];
    foreach ($chamadosDisp as $ch) {
        $chamadosJson[(int)$ch['id']] = [
            'id'       => (int)$ch['id'], 'cliente'   => $ch['cliente_nome'],
            'categoria'=> $ch['categoria'], 'descricao' => $ch['descricao'],
            'endereco' => $ch['endereco_servico'] ?? '',
            'data'     => !empty($ch['data_agendamento']) ? date('d/m/Y H:i', strtotime($ch['data_agendamento'])) : 'Não informada',
            'status'   => $ch['status'], 'fotos' => $ch['fotos'] ?? [],
        ];
    }
  ?>

  <div class="row g-4">
    <!-- Formulário -->
    <div class="col-lg-5">
      <div class="form-card" style="position:sticky;top:80px;">
        <div class="form-card-titulo"><span>📋</span> Enviar orçamento</div>
        <form method="post" class="js-guard-submit">
          <input type="hidden" name="acao" value="enviar">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Chamado <span class="text-danger">*</span></label>
            <select name="chamado_id" id="sel-chamado" class="form-select" required>
              <option value="">— Selecione um chamado —</option>
              <?php foreach ($chamadosDisp as $ch): ?>
                <option value="<?php echo (int)$ch['id']; ?>" <?php echo $chamadoSelecionado === (int)$ch['id'] ? 'selected' : ''; ?>>
                  <?php echo ($ch['status'] ?? '') === 'Aguardando Orçamento' ? '⏳ ' : '📋 '; ?>
                  #<?php echo (int)$ch['id']; ?> — <?php echo htmlspecialchars(mb_strimwidth($ch['descricao'], 0, 42, '…')); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!$chamadosDisp): ?>
              <small class="text-warning d-block mt-1">Nenhum chamado disponível no momento.</small>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Nível do serviço</label>
            <div class="nivel-grid" id="nivelGrid">
              <?php foreach ($niveis as [$rot,$val]): ?>
                <button type="button" class="nivel-btn" data-valor="<?php echo $val; ?>">
                  <span class="nt"><?php echo $rot; ?></span>
                  <span class="np">R$ <?php echo $val; ?></span>
                </button>
              <?php endforeach; ?>
            </div>
            <button type="button" class="nivel-custom-btn w-100 justify-content-center" id="btnPersonalizado">
              ✏️ Definir valor personalizado
            </button>
            <div id="customValorWrap" style="display:none;" class="mt-1">
              <input type="number" id="input-custom-valor" class="form-control" min="1" step="0.01"
                     placeholder="Digite o valor exato (R$)" style="font-size:.9rem;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Valor proposto (R$) <span class="text-danger">*</span></label>
            <input type="number" name="valor" id="input-valor-orcamento" class="form-control" min="1" step="0.01" required placeholder="0,00">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Descrição / observações</label>
            <textarea name="descricao" class="form-control" rows="3" maxlength="500" placeholder="Detalhe o que está incluso no orçamento..."></textarea>
          </div>

          <button type="submit" class="btn btn-warning fw-bold w-100" <?php echo !$chamadosDisp ? 'disabled' : ''; ?>>
            Enviar orçamento
          </button>
        </form>
      </div>
    </div>

    <!-- Detalhe + Lista -->
    <div class="col-lg-7">
      <!-- Detalhe do chamado -->
      <div id="chamado-detalhe" class="detalhe-card" style="display:none;">
        <div class="detalhe-header">
          <h6>Solicitação — <span id="det-id"></span></h6>
          <span id="det-badge" class="badge"></span>
        </div>
        <div class="detalhe-body">
          <div class="info-row">
            <div><div class="lbl">Cliente</div><div class="val" id="det-cliente"></div></div>
            <div><div class="lbl">Categoria</div><div class="val" id="det-categoria"></div></div>
            <div><div class="lbl">Data</div><div class="val" id="det-data"></div></div>
            <div><div class="lbl">Endereço</div><div class="val" id="det-endereco"></div></div>
          </div>
          <div class="mb-3">
            <div class="lbl mb-1">Problema descrito</div>
            <div class="desc-box" id="det-descricao"></div>
          </div>
          <div id="det-fotos-wrap" style="display:none;">
            <div class="lbl mb-2">Fotos do problema</div>
            <div id="det-fotos" class="d-flex flex-wrap gap-2"></div>
          </div>
        </div>
      </div>

      <!-- Lista de orçamentos -->
      <div class="form-card">
        <div class="form-card-titulo">
          <span>📄</span> Meus orçamentos
          <span class="ms-auto badge bg-secondary" style="font-size:.72rem;"><?php echo count($meusOrcamentos); ?></span>
        </div>
        <?php if (!$meusOrcamentos): ?>
          <div class="text-center py-4 text-muted" style="font-size:.9rem;">
            <div style="font-size:2rem;margin-bottom:.4rem;">📭</div>
            Nenhum orçamento enviado ainda.
          </div>
        <?php else: ?>
          <?php foreach ($meusOrcamentos as $o):
            $sc = $o->status === 'Aceito' ? 's-ok' : ($o->status === 'Recusado' ? 's-no' : 's-pend');
            $bc = $o->status === 'Aceito' ? 'success' : ($o->status === 'Recusado' ? 'danger' : 'warning text-dark');
          ?>
          <div class="orc-item">
            <div class="orc-strip <?php echo $sc; ?>"></div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <div>
                  <div class="orc-sub">Chamado #<?php echo $o->chamadoId; ?></div>
                  <div class="fw-semibold" style="font-size:.88rem;"><?php echo htmlspecialchars($o->clienteNome ?? ''); ?></div>
                </div>
                <div class="text-end flex-shrink-0">
                  <div class="orc-valor">R$ <?php echo number_format($o->valor, 2, ',', '.'); ?></div>
                  <span class="badge bg-<?php echo $bc; ?>" style="font-size:.7rem;"><?php echo $o->status; ?></span>
                </div>
              </div>
              <?php if ($o->status === 'Recusado' && !empty($o->motivoRecusa)): ?>
                <div class="small text-muted mt-1" style="font-size:.78rem;">💬 <?php echo htmlspecialchars(mb_strimwidth($o->motivoRecusa, 0, 80, '…')); ?></div>
              <?php endif; ?>
              <div class="d-flex gap-1 mt-2 flex-wrap">
                <a href="../chat.php?chamado=<?php echo $o->chamadoId; ?>" class="btn btn-sm btn-outline-primary">💬 Chat</a>
                <?php if ($o->status === 'Pendente'): ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#modalAlterarOrcamento"
                    data-orcamento-id="<?php echo $o->id; ?>"
                    data-valor="<?php echo number_format($o->valor, 2, '.', ''); ?>"
                    data-descricao="<?php echo htmlspecialchars($o->descricao ?? '', ENT_QUOTES); ?>"
                    data-chamado="<?php echo $o->chamadoId; ?>">Alterar</button>
                  <form method="post" class="d-inline" onsubmit="return confirm('Cancelar este orçamento?')">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="orcamento_id" value="<?php echo $o->id; ?>">
                    <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<!-- Modal Alterar Orçamento -->
<div class="modal fade" id="modalAlterarOrcamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Alterar orçamento — chamado <span id="modal-orc-chamado"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" class="js-guard-submit">
        <input type="hidden" name="acao" value="alterar">
        <input type="hidden" name="orcamento_id" id="modal-orc-id">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Nível do serviço</label>
            <div class="nivel-grid" id="modalNivelGrid">
              <?php foreach ($niveis as [$rot,$val]): ?>
                <button type="button" class="nivel-btn" data-modal-valor="<?php echo $val; ?>">
                  <span class="nt"><?php echo $rot; ?></span>
                  <span class="np">R$ <?php echo $val; ?></span>
                </button>
              <?php endforeach; ?>
            </div>
            <button type="button" class="nivel-custom-btn w-100 justify-content-center" id="modalBtnPersonalizado">
              ✏️ Definir valor personalizado
            </button>
            <div id="modalCustomValorWrap" style="display:none;" class="mt-1">
              <input type="number" id="modal-input-custom-valor" class="form-control" min="1" step="0.01"
                     placeholder="Digite o valor exato (R$)" style="font-size:.9rem;">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Novo valor (R$) <span class="text-danger">*</span></label>
            <input type="number" name="valor" id="modal-orc-valor" class="form-control" min="1" step="0.01" required>
          </div>
          <div class="mb-0">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Descrição / observações</label>
            <textarea name="descricao" id="modal-orc-descricao" class="form-control" rows="3" maxlength="500"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning fw-bold">Salvar alteração</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
var _chamados = <?php echo json_encode($chamadosJson, JSON_UNESCAPED_UNICODE); ?>;
var selChamado = document.getElementById('sel-chamado');
var detalheCard = document.getElementById('chamado-detalhe');

function mostrarDetalhe(id) {
  var ch = _chamados[id]; if (!ch || !detalheCard) return;
  document.getElementById('det-id').textContent        = '#' + ch.id;
  document.getElementById('det-cliente').textContent   = ch.cliente;
  document.getElementById('det-categoria').textContent = ch.categoria;
  document.getElementById('det-data').textContent      = ch.data;
  document.getElementById('det-endereco').textContent  = ch.endereco || 'Não informado';
  document.getElementById('det-descricao').textContent = ch.descricao;
  var badge = document.getElementById('det-badge');
  if (ch.status === 'Aguardando Orçamento') { badge.textContent = '⏳ Aceito por você'; badge.className = 'badge bg-warning text-dark'; }
  else { badge.textContent = '📋 Pendente'; badge.className = 'badge bg-secondary'; }
  var fotosWrap = document.getElementById('det-fotos-wrap');
  var fotosDiv  = document.getElementById('det-fotos');
  fotosDiv.innerHTML = '';
  if (ch.fotos && ch.fotos.length > 0) {
    ch.fotos.forEach(function(p) {
      var a = document.createElement('a'); a.href = '../../' + p; a.target = '_blank';
      var img = document.createElement('img');
      img.src = '../../' + p;
      img.style.cssText = 'height:78px;width:96px;object-fit:cover;border-radius:8px;border:1.5px solid #e8ecf3;cursor:pointer;';
      img.onerror = function() { a.style.display = 'none'; };
      a.appendChild(img); fotosDiv.appendChild(a);
    });
    fotosWrap.style.display = '';
  } else { fotosWrap.style.display = 'none'; }
  detalheCard.style.display = '';
}

if (selChamado) {
  selChamado.addEventListener('change', function() {
    var id = parseInt(this.value);
    if (id) mostrarDetalhe(id); else if (detalheCard) detalheCard.style.display = 'none';
  });
  var pre = parseInt(selChamado.value); if (pre) mostrarDetalhe(pre);
}

// --- Formulário principal ---
var inputValor       = document.getElementById('input-valor-orcamento');
var btnPersonalizado = document.getElementById('btnPersonalizado');
var customWrap       = document.getElementById('customValorWrap');
var inputCustom      = document.getElementById('input-custom-valor');

function resetNivelGrid() {
  document.querySelectorAll('#nivelGrid .nivel-btn').forEach(b => b.classList.remove('sel'));
  btnPersonalizado.classList.remove('sel');
}

document.querySelectorAll('#nivelGrid .nivel-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    resetNivelGrid();
    this.classList.add('sel');
    customWrap.style.display = 'none';
    inputValor.value = this.dataset.valor;
    inputValor.readOnly = false;
  });
});

if (btnPersonalizado) {
  btnPersonalizado.addEventListener('click', function() {
    resetNivelGrid();
    this.classList.add('sel');
    customWrap.style.display = '';
    inputValor.value = '';
    inputCustom.focus();
  });
}

if (inputCustom) {
  inputCustom.addEventListener('input', function() {
    inputValor.value = this.value;
  });
}

// --- Modal Alterar ---
var modalInputValor       = document.getElementById('modal-orc-valor');
var modalBtnPersonalizado = document.getElementById('modalBtnPersonalizado');
var modalCustomWrap       = document.getElementById('modalCustomValorWrap');
var modalInputCustom      = document.getElementById('modal-input-custom-valor');

function resetModalNivelGrid() {
  document.querySelectorAll('#modalNivelGrid .nivel-btn').forEach(b => b.classList.remove('sel'));
  if (modalBtnPersonalizado) modalBtnPersonalizado.classList.remove('sel');
}

document.querySelectorAll('#modalNivelGrid .nivel-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    resetModalNivelGrid();
    this.classList.add('sel');
    if (modalCustomWrap) modalCustomWrap.style.display = 'none';
    modalInputValor.value = this.dataset.modalValor;
  });
});

if (modalBtnPersonalizado) {
  modalBtnPersonalizado.addEventListener('click', function() {
    resetModalNivelGrid();
    this.classList.add('sel');
    modalCustomWrap.style.display = '';
    modalInputValor.value = '';
    modalInputCustom.focus();
  });
}

if (modalInputCustom) {
  modalInputCustom.addEventListener('input', function() {
    modalInputValor.value = this.value;
  });
}

var modalAlterar = document.getElementById('modalAlterarOrcamento');
if (modalAlterar) {
  modalAlterar.addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('modal-orc-id').value            = btn.dataset.orcamentoId;
    document.getElementById('modal-orc-valor').value         = btn.dataset.valor;
    document.getElementById('modal-orc-descricao').value     = btn.dataset.descricao;
    document.getElementById('modal-orc-chamado').textContent = '#' + btn.dataset.chamado;
    resetModalNivelGrid();
    if (modalCustomWrap)  modalCustomWrap.style.display = 'none';
    if (modalInputCustom) modalInputCustom.value = '';
  });
}
</script>
</body>
</html>
