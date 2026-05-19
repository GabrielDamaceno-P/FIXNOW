<?php
session_start();
require_once __DIR__ . '/../../controller/OrcamentoPrestadorControl.php';

$ctrl = new OrcamentoPrestadorControl();
$ctrl->processar();
$chamadosDisp     = $ctrl->chamadosDisponiveis;
$meusOrcamentos   = $ctrl->meusOrcamentos;
$chamadoSelecionado = $ctrl->chamadoSelecionado;
$mensagem         = $ctrl->mensagem;
$erro             = $ctrl->erro;
$naoLidas         = $ctrl->naoLidas;
$tecnicoNome      = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto      = $_SESSION['tecnico_foto'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orçamentos - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php $paginaAtiva = 'orcamentos'; require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-5 mt-5">
  <h2 class="mb-1">Orçamentos</h2>
  <p class="text-muted mb-4">Envie propostas para chamados pendentes — tanto abertos quanto solicitações diretas para você.</p>

  <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5>Enviar orçamento</h5>
          <form method="post" class="js-guard-submit">
            <input type="hidden" name="acao" value="enviar">
            <div class="mb-3">
              <label class="form-label">Chamado <span class="text-danger">*</span></label>
              <select name="chamado_id" class="form-select" required>
                <option value="">— Selecione —</option>
                <?php foreach ($chamadosDisp as $ch): ?>
                  <option value="<?php echo (int)$ch['id']; ?>" <?php echo $chamadoSelecionado === (int)$ch['id'] ? 'selected' : ''; ?>>
                    <?php echo ($ch['status'] ?? '') === 'Aguardando Orçamento' ? '⏳ ' : ''; ?>
                    #<?php echo (int)$ch['id']; ?> - <?php echo htmlspecialchars(mb_strimwidth($ch['descricao'], 0, 50, '...')); ?> (<?php echo htmlspecialchars($ch['cliente_nome']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (!$chamadosDisp): ?><div class="form-text text-warning">Nenhum chamado disponível.</div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label class="form-label">Nível do serviço</label>
              <select id="nivel-servico" class="form-select">
                <option value="">Selecione para sugerir um valor...</option>
                <option value="50">Simples — R$ 50</option>
                <option value="89">Básico — R$ 89</option>
                <option value="130">Intermediário — R$ 130</option>
                <option value="200">Avançado — R$ 200</option>
                <option value="280">Premium — R$ 280</option>
                <option value="350">Urgente — R$ 350</option>
                <option value="">Personalizado (preencha abaixo)</option>
              </select>
              <small class="text-muted">Escolha um nível para pré-preencher o valor, ou insira manualmente.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Valor proposto (R$) <span class="text-danger">*</span></label>
              <input type="number" name="valor" id="input-valor-orcamento" class="form-control" min="1" step="0.01" required placeholder="0.00">
            </div>
            <div class="mb-3">
              <label class="form-label">Descrição / observações</label>
              <textarea name="descricao" class="form-control" rows="3" maxlength="500" placeholder="Detalhe o que está incluso no orçamento..."></textarea>
            </div>
            <button type="submit" class="btn btn-warning fw-semibold" <?php echo !$chamadosDisp ? 'disabled' : ''; ?>>Enviar orçamento</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <?php if ($chamadosDisp): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="mb-3">Chamados disponíveis</h5>
          <div class="table-responsive">
            <table class="table table-hover align-middle table-sm">
              <thead class="table-primary"><tr><th>#</th><th>Cliente</th><th>Categoria</th><th>Descrição</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($chamadosDisp as $ch): ?>
                <tr>
                  <td><?php echo (int)$ch['id']; ?></td>
                  <td>
                    <?php echo htmlspecialchars($ch['cliente_nome']); ?>
                    <?php if (($ch['status'] ?? '') === 'Aguardando Orçamento'): ?>
                      <span class="badge bg-warning text-dark ms-1">Aceito</span>
                    <?php elseif (!empty($ch['solicitacao_direta'])): ?>
                      <span class="badge bg-primary ms-1">Direto</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($ch['categoria']); ?></td>
                  <td><?php echo htmlspecialchars(mb_strimwidth($ch['descricao'], 0, 40, '...')); ?></td>
                  <td class="d-flex gap-1">
                    <a href="orcamento.php?chamado=<?php echo (int)$ch['id']; ?>" class="btn btn-sm btn-outline-warning">Orçar</a>
                    <a href="../chat.php?chamado=<?php echo (int)$ch['id']; ?>" class="btn btn-sm btn-outline-primary">💬</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3">Meus orçamentos</h5>
          <?php if (!$meusOrcamentos): ?>
            <p class="text-muted">Nenhum orçamento enviado.</p>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
              <thead class="table-primary"><tr><th>#</th><th>Chamado</th><th>Cliente</th><th>Valor</th><th>Status</th><th>Motivo</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($meusOrcamentos as $o): ?>
                <?php $badge = $o->status === 'Aceito' ? 'success' : ($o->status === 'Recusado' ? 'danger' : 'warning text-dark'); ?>
                <tr>
                  <td><?php echo $o->id; ?></td>
                  <td>#<?php echo $o->chamadoId; ?></td>
                  <td><?php echo htmlspecialchars($o->clienteNome ?? ''); ?></td>
                  <td>R$ <?php echo number_format($o->valor, 2, ',', '.'); ?></td>
                  <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $o->status; ?></span></td>
                  <td>
                    <?php if ($o->status === 'Recusado' && !empty($o->motivoRecusa)): ?>
                      <span class="small text-muted" title="<?php echo htmlspecialchars($o->motivoRecusa); ?>">
                        <?php echo htmlspecialchars(mb_strimwidth($o->motivoRecusa, 0, 35, '...')); ?>
                      </span>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                    <a href="../chat.php?chamado=<?php echo $o->chamadoId; ?>" class="btn btn-sm btn-outline-primary">💬</a>
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
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
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
            <label class="form-label">Nível do serviço</label>
            <select id="modal-nivel-servico" class="form-select">
              <option value="">Selecione para sugerir um valor...</option>
              <option value="50">Simples — R$ 50</option>
              <option value="89">Básico — R$ 89</option>
              <option value="130">Intermediário — R$ 130</option>
              <option value="200">Avançado — R$ 200</option>
              <option value="280">Premium — R$ 280</option>
              <option value="350">Urgente — R$ 350</option>
              <option value="">Personalizado (preencha abaixo)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Novo valor (R$) <span class="text-danger">*</span></label>
            <input type="number" name="valor" id="modal-orc-valor" class="form-control" min="1" step="0.01" required>
          </div>
          <div class="mb-0">
            <label class="form-label">Descrição / observações</label>
            <textarea name="descricao" id="modal-orc-descricao" class="form-control" rows="3" maxlength="500"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning fw-semibold">Salvar alteração</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
document.getElementById('nivel-servico')?.addEventListener('change', function() {
  if (this.value) document.getElementById('input-valor-orcamento').value = this.value;
});
document.getElementById('modal-nivel-servico')?.addEventListener('change', function() {
  if (this.value) document.getElementById('modal-orc-valor').value = this.value;
});
var modalAlterar = document.getElementById('modalAlterarOrcamento');
if (modalAlterar) {
  modalAlterar.addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('modal-orc-id').value        = btn.dataset.orcamentoId;
    document.getElementById('modal-orc-valor').value     = btn.dataset.valor;
    document.getElementById('modal-orc-descricao').value = btn.dataset.descricao;
    document.getElementById('modal-orc-chamado').textContent = '#' + btn.dataset.chamado;
    document.getElementById('modal-nivel-servico').value  = '';
  });
}
</script>
</body>
</html>
