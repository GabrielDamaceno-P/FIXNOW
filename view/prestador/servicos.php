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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meus Serviços - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <span class="fn-user-badge me-2">
      <?php if ($tecnicoFoto): ?>
        <img src="../../<?php echo htmlspecialchars($tecnicoFoto); ?>" alt="Foto" width="44" height="44">
      <?php else: ?>
        <span class="fallback"><?php echo htmlspecialchars(mb_substr($tecnicoNome, 0, 1)); ?></span>
      <?php endif; ?>
      <span><?php echo htmlspecialchars($tecnicoNome); ?></span>
    </span>
    <a class="navbar-brand fw-bold" href="../../index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboardPrestador.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="calendario.php">Calendário</a></li>
        <li class="nav-item"><a class="nav-link" href="orcamento.php">Orçamentos</a></li>
        <li class="nav-item"><a class="nav-link active" href="servicos.php">Meus Serviços</a></li>
        <li class="nav-item"><a class="nav-link" href="portfolio.php">Portfólio</a></li>
        <li class="nav-item"><a class="nav-link" href="financeiro.php">Financeiro</a></li>
        <li class="nav-item"><a class="nav-link" href="../suporte.php">Suporte</a></li>
        <li class="nav-item">
          <a class="nav-link" href="../notificacoes.php">
            Notificações<?php if ($naoLidas > 0): ?><span class="badge bg-danger ms-1"><?php echo $naoLidas; ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="../perfil.php">Perfil</a></li>
        <li class="nav-item"><a class="nav-link" href="../../logout.php?entidade=prestador">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5 mt-5">
  <h2 class="mb-4">Meus Serviços</h2>

  <?php if ($mensagem): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 id="formTitulo">Novo serviço</h5>
          <form method="post" class="js-guard-submit" id="formServico">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="servico_id" id="servico_id" value="0">
            <div class="mb-3">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" id="f_nome" class="form-control" required maxlength="150">
            </div>
            <div class="mb-3">
              <label class="form-label">Categoria</label>
              <select name="categoria_id" id="f_cat" class="form-select">
                <option value="">— Sem categoria —</option>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" id="f_desc" class="form-control" rows="2" maxlength="500"></textarea>
            </div>

            <div class="mb-3 form-check">
              <input type="checkbox" name="ativo" id="f_ativo" class="form-check-input" value="1" checked>
              <label class="form-check-label" for="f_ativo">Serviço ativo</label>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-warning fw-semibold">Salvar</button>
              <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <?php if (!$servicos): ?>
        <div class="alert alert-info">Nenhum serviço cadastrado ainda.</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-primary"><tr><th>Nome</th><th>Categoria</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($servicos as $s): ?>
            <tr>
              <td><?php echo htmlspecialchars($s->nome); ?></td>
              <td><?php echo htmlspecialchars($s->categoriaNome ?? '—'); ?></td>
              <td><span class="badge bg-<?php echo $s->ativo ? 'success' : 'secondary'; ?>"><?php echo $s->ativo ? 'Ativo' : 'Inativo'; ?></span></td>
              <td class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-warning"
                  onclick="editarServico(<?php echo $s->id; ?>,'<?php echo addslashes($s->nome); ?>',<?php echo (int)$s->categoriaId; ?>,'<?php echo addslashes($s->descricao); ?>',<?php echo $s->ativo; ?>)">
                  Editar
                </button>
                <form method="post" class="d-inline" onsubmit="return confirm('Remover serviço?')">
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="servico_id" value="<?php echo $s->id; ?>">
                  <button class="btn btn-sm btn-outline-danger">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<footer class="bg-dark text-light py-3 mt-5">
  <div class="container text-center"><small>&copy; <?php echo date('Y'); ?> Fix Now.</small></div>
</footer>
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
