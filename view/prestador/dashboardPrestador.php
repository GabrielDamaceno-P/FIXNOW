<?php
session_start();
require_once __DIR__ . '/../../controller/DashboardPrestadorControl.php';

$ctrl = new DashboardPrestadorControl();
$ctrl->processar();

$tecnicoNome       = $_SESSION['tecnico_nome'] ?? 'Prestador';
$tecnicoFoto       = $_SESSION['tecnico_foto'] ?? '';
$stats             = $ctrl->stats;
$pendentesCount    = $ctrl->pendentesCount;
$chamadosDisp      = $ctrl->chamadosDisponiveis;
$diretos           = $ctrl->solicitacoesDiretas;
$meusChamados      = $ctrl->emAndamento;
$historico         = $ctrl->historico;
$notificacoes      = $ctrl->notificacoes;
$mensagem          = $ctrl->mensagem;
$erro              = $ctrl->erro;
$isDestaque        = $ctrl->isDestaque;
$categoriasServico = $ctrl->categoriasServico;
$naoLidas          = count(array_filter($notificacoes, fn($n) => !(int)$n['lida']));
$paginaAtiva       = 'chamados';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meus chamados - Prestador - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/prestador_nav.php'; ?>

<main class="container py-5 mt-5">

  <section class="fn-hero p-4 p-lg-5 mb-4">
    <div class="row g-3 align-items-center">
      <div class="col-lg-8">
        <h1 class="h3 mb-2">Painel do Prestador Fix Now</h1>
        <p class="mb-0 text-white-50">Acompanhe suas oportunidades, acelere aceites e mantenha seu histórico de excelência.</p>
      </div>
      <div class="col-lg-4 d-flex flex-wrap justify-content-lg-end align-items-start gap-2 mt-2 mt-lg-0">
        <?php if ($categoriasServico): ?>
          <?php foreach ($categoriasServico as $cat): ?>
            <span class="badge bg-warning text-dark px-3 py-2"><?php echo htmlspecialchars($cat); ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="badge bg-secondary px-3 py-2">Sem serviços cadastrados</span>
        <?php endif; ?>
        <?php if ($isDestaque): ?>
          <span class="badge px-3 py-2" style="background:linear-gradient(135deg,#ffc107,#ff9800);color:#fff;box-shadow:0 2px 8px rgba(255,152,0,.4);">★ Prestador em Destaque</span>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card fn-stat-card h-100">
        <div class="card-body"><p class="text-muted mb-1">Chamados disponíveis</p><h3 class="mb-0"><?php echo $pendentesCount; ?></h3></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card fn-stat-card h-100">
        <div class="card-body"><p class="text-muted mb-1">Em andamento</p><h3 class="mb-0"><?php echo (int)($stats['em_andamento'] ?? 0); ?></h3></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card fn-stat-card h-100">
        <div class="card-body"><p class="text-muted mb-1">Concluídos</p><h3 class="mb-0"><?php echo (int)($stats['concluidos'] ?? 0); ?></h3></div>
      </div>
    </div>
  </section>

  <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <?php if (!$categoriasServico): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2">
    Você ainda não possui serviços cadastrados.
    <a href="servicos.php" class="alert-link ms-1">Crie seus serviços</a> para aparecer no catálogo e receber chamados.
  </div>
  <?php endif; ?>

  <!-- Chamados pendentes (fila da especialidade) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <h4 class="h5">Chamados pendentes (sua especialidade)</h4>
      <?php if (!$chamadosDisp): ?>
        <p class="text-muted mb-0">Nenhum chamado aberto aguardando técnico no momento.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-primary">
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Descrição</th>
                <th>Endereço</th>
                <th>Preço</th>
                <th>Data solicitada</th>
                <th>Preferência</th>
                <th>Foto</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($chamadosDisp as $p): ?>
              <tr>
                <td><?php echo (int)$p['id']; ?></td>
                <td><?php echo htmlspecialchars($p['cliente_nome']); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($p['descricao'], 0, 60, '...')); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($p['endereco_servico'], 0, 40, '...')); ?></td>
                <td>R$ <?php echo number_format((float)$p['preco_sugerido'], 2, ',', '.'); ?></td>
                <td>
                  <?php if (!empty($p['data_agendamento'])): ?>
                    <span class="badge bg-info text-dark"><?php echo date('d/m/Y H:i', strtotime($p['data_agendamento'])); ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td><?php echo !empty($p['prest_feminino']) ? '<span class="badge bg-info text-dark">Só prestadoras</span>' : '<span class="text-muted">—</span>'; ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-secondary js-open-cliente-perfil"
                    data-cliente-nome="<?php echo htmlspecialchars($p['cliente_nome'], ENT_QUOTES); ?>"
                    data-cliente-foto="<?php echo htmlspecialchars($p['cliente_foto'] ?? '', ENT_QUOTES); ?>"
                    data-cliente-telefone="<?php echo htmlspecialchars($p['cliente_telefone'] ?? '', ENT_QUOTES); ?>"
                    data-cliente-endereco="<?php echo htmlspecialchars($p['endereco_servico'] ?? '', ENT_QUOTES); ?>"
                    data-problema-foto="<?php echo htmlspecialchars($p['foto_path'] ?? '', ENT_QUOTES); ?>"
                    data-resumo="<?php echo htmlspecialchars(mb_strimwidth($p['descricao'], 0, 170, '...'), ENT_QUOTES); ?>">Ver perfil</button>
                </td>
                <td>
                  <form method="post" class="d-inline js-confirm-aceitar js-guard-submit">
                    <input type="hidden" name="aceitar_id" value="<?php echo (int)$p['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold">Aceitar</button>
                  </form>
                  <form method="post" class="d-inline js-confirm-negar js-guard-submit ms-1">
                    <input type="hidden" name="negar_id" value="<?php echo (int)$p['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Negar serviço</button>
                  </form>
                  <a href="../chat.php?chamado=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-primary ms-1">💬 Chat</a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Solicitações diretas -->
  <?php if ($diretos): ?>
  <div class="card shadow-sm mb-4" style="border: 2px solid #0d6efd !important;">
    <div class="card-body">
      <h4 class="h5 text-primary">Solicitações diretas <span class="badge bg-primary ms-1"><?php echo count($diretos); ?></span></h4>
      <p class="text-muted small mb-3">Clientes que escolheram você especificamente pelo catálogo. Aceite ou recuse cada solicitação.</p>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-primary">
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Descrição</th>
              <th>Endereço</th>
              <th>Preço</th>
              <th>Horário solicitado</th>
              <th>Foto</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($diretos as $d): ?>
            <tr>
              <td><?php echo (int)$d['id']; ?></td>
              <td><?php echo htmlspecialchars($d['cliente_nome']); ?></td>
              <td><?php echo htmlspecialchars(mb_strimwidth($d['descricao'], 0, 60, '...')); ?></td>
              <td><?php echo htmlspecialchars(mb_strimwidth($d['endereco_servico'], 0, 40, '...')); ?></td>
              <td>R$ <?php echo number_format((float)$d['preco_sugerido'], 2, ',', '.'); ?></td>
              <td>
                <?php if (!empty($d['data_agendamento'])): ?>
                  <span class="badge bg-success"><?php echo date('d/m/Y H:i', strtotime($d['data_agendamento'])); ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-outline-secondary js-open-cliente-perfil"
                  data-cliente-nome="<?php echo htmlspecialchars($d['cliente_nome'], ENT_QUOTES); ?>"
                  data-cliente-foto="<?php echo htmlspecialchars($d['cliente_foto'] ?? '', ENT_QUOTES); ?>"
                  data-cliente-telefone="<?php echo htmlspecialchars($d['cliente_telefone'] ?? '', ENT_QUOTES); ?>"
                  data-cliente-endereco="<?php echo htmlspecialchars($d['endereco_servico'] ?? '', ENT_QUOTES); ?>"
                  data-problema-foto="<?php echo htmlspecialchars($d['foto_path'] ?? '', ENT_QUOTES); ?>"
                  data-resumo="<?php echo htmlspecialchars(mb_strimwidth($d['descricao'], 0, 170, '...'), ENT_QUOTES); ?>">Ver perfil</button>
              </td>
              <td>
                <form method="post" class="d-inline js-confirm-aceitar js-guard-submit">
                  <input type="hidden" name="aceitar_direto_id" value="<?php echo (int)$d['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-primary fw-semibold">Aceitar</button>
                </form>
                <form method="post" class="d-inline js-confirm-negar js-guard-submit ms-1">
                  <input type="hidden" name="recusar_direto_id" value="<?php echo (int)$d['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Recusar</button>
                </form>
                <a href="../chat.php?chamado=<?php echo (int)$d['id']; ?>" class="btn btn-sm btn-outline-primary ms-1">💬 Chat</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Chamados em andamento -->
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <h4 class="h5">Chamados em andamento</h4>
      <?php if (!$meusChamados): ?>
        <p class="text-muted mb-0">Nenhum chamado ativo no momento.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-primary">
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Contato</th>
                <th>Descrição</th>
                <th>Endereço</th>
                <th>Preço</th>
                <th>Status</th>
                <th>Agendado</th>
                <th>Foto cliente</th>
                <th>Alterar</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($meusChamados as $c): ?>
              <tr>
                <td><?php echo (int)$c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['cliente_nome'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($c['cliente_telefone'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($c['descricao'] ?? '', 0, 55, '...')); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($c['endereco_servico'] ?? '', 0, 45, '...')); ?></td>
                <td>R$ <?php echo number_format((float)($c['preco_sugerido'] ?? 0), 2, ',', '.'); ?></td>
                <td><?php echo htmlspecialchars($c['status'] ?? ''); ?></td>
                <td>
                  <?php if (!empty($c['reagendamento_pendente']) && !empty($c['data_agendamento_proposta'])): ?>
                    <div class="d-flex flex-column gap-1">
                      <span class="badge bg-warning text-dark">Nova proposta</span>
                      <small><?php echo date('d/m/Y H:i', strtotime($c['data_agendamento_proposta'])); ?></small>
                      <div class="d-flex gap-1 mt-1">
                        <form method="post" class="d-inline js-guard-submit">
                          <input type="hidden" name="aceitar_reagendamento_id" value="<?php echo (int)$c['id']; ?>">
                          <button class="btn btn-sm btn-success">Aceitar</button>
                        </form>
                        <form method="post" class="d-inline js-guard-submit">
                          <input type="hidden" name="recusar_reagendamento_id" value="<?php echo (int)$c['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger">Recusar</button>
                        </form>
                      </div>
                    </div>
                  <?php elseif (!empty($c['data_agendamento'])): ?>
                    <?php echo date('d/m/Y H:i', strtotime($c['data_agendamento'])); ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary js-open-cliente-perfil"
                    data-cliente-nome="<?php echo htmlspecialchars($c['cliente_nome'] ?? '', ENT_QUOTES); ?>"
                    data-cliente-foto="<?php echo htmlspecialchars($c['cliente_foto'] ?? '', ENT_QUOTES); ?>"
                    data-cliente-telefone="<?php echo htmlspecialchars($c['cliente_telefone'] ?? '', ENT_QUOTES); ?>"
                    data-cliente-endereco="<?php echo htmlspecialchars($c['endereco_servico'] ?? '', ENT_QUOTES); ?>"
                    data-problema-foto="<?php echo htmlspecialchars($c['foto_path'] ?? '', ENT_QUOTES); ?>"
                    data-resumo="<?php echo htmlspecialchars(mb_strimwidth($c['descricao'] ?? '', 0, 170, '...'), ENT_QUOTES); ?>">Ver perfil</button>
                </td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <form method="post" class="d-flex gap-1 flex-wrap js-guard-submit">
                      <input type="hidden" name="chamado_id" value="<?php echo (int)$c['id']; ?>">
                      <select name="novo_status" class="form-select form-select-sm" style="min-width: 9rem;">
                        <?php foreach (['Pendente', 'Em Andamento', 'Concluído', 'Negado'] as $opt): ?>
                          <option <?php echo ($c['status'] ?? '') === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                    </form>
                    <a href="../chat.php?chamado=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-outline-primary">💬 Chat</a>
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

  <!-- Histórico -->
  <?php if ($historico): ?>
  <div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 mb-0">Histórico de serviços</h4>
        <a href="financeiro.php" class="btn btn-sm btn-outline-warning">Ver relatório financeiro</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-primary">
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Categoria</th>
              <th>Descrição</th>
              <th>Valor</th>
              <th>Pagamento</th>
              <th>Status</th>
              <th>Concluído em</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($historico as $h): ?>
            <?php
              $badgeH   = $h['status'] === 'Concluído' ? 'success' : 'dark';
              $badgePag = '';
              if ($h['pag_status'] === 'Pago')      $badgePag = 'success';
              elseif ($h['pag_status'] === 'Pendente') $badgePag = 'warning text-dark';
              elseif ($h['pag_status'] === 'Estornado') $badgePag = 'danger';
            ?>
            <tr>
              <td><?php echo (int)$h['id']; ?></td>
              <td><?php echo htmlspecialchars($h['cliente_nome'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($h['categoria'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars(mb_strimwidth($h['descricao'] ?? '', 0, 45, '...')); ?></td>
              <td>R$ <?php echo number_format((float)($h['pag_valor'] ?? $h['preco_sugerido'] ?? 0), 2, ',', '.'); ?></td>
              <td>
                <?php if ($h['pag_status']): ?>
                  <span class="badge bg-<?php echo $badgePag; ?>"><?php echo htmlspecialchars($h['pag_status']); ?></span>
                  <?php if ($h['pag_status'] === 'Pago' && !empty($h['pago_em'])): ?>
                    <div class="small text-muted"><?php echo date('d/m/Y', strtotime($h['pago_em'])); ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-<?php echo $badgeH; ?>"><?php echo htmlspecialchars($h['status'] ?? ''); ?></span></td>
              <td><?php echo date('d/m/Y', strtotime($h['atualizado_em'] ?? $h['criado_em'])); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- Modal foto ampliada -->
<div class="modal fade" id="modalFotoFixnow" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-fn-foto-titulo>Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" alt="" class="img-fluid rounded shadow-sm" data-fn-foto-img style="max-height: 70vh;">
      </div>
    </div>
  </div>
</div>

<!-- Modal perfil do cliente -->
<div class="modal fade fn-profile-modal" id="modalClientePerfil" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title">Perfil do cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body pt-0">
        <div class="row g-3 align-items-stretch">
          <div class="col-md-5">
            <div class="fn-profile-photo-frame">
              <img src="" class="fn-profile-main-photo" data-cliente-modal-foto alt="Foto do cliente">
            </div>
          </div>
          <div class="col-md-7">
            <h4 class="h5 mb-2" data-cliente-modal-nome></h4>
            <div class="fn-profile-info-card mb-2">
              <p class="mb-1"><strong>Telefone:</strong> <span data-cliente-modal-tel></span></p>
              <p class="mb-0"><strong>Endereço:</strong> <span data-cliente-modal-end></span></p>
            </div>
            <div class="fn-profile-resumo"><strong>Resumo:</strong> <span data-cliente-modal-resumo></span></div>
          </div>
          <div class="col-12">
            <h6 class="mb-2">Foto do problema</h6>
            <img src="" class="fn-profile-problem-photo" data-cliente-modal-problema alt="Foto do problema">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/forms-helpers.js"></script>
<script src="../../assets/js/foto-lightbox.js"></script>
<script src="../../assets/js/dashboard-prestador.js"></script>
</body>
</html>
