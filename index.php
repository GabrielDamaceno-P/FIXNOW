<?php
session_start();
require_once __DIR__ . '/config/db.php';

$stmtDest = $pdo->query("
    SELECT t.id, t.nome, t.especialidade, t.foto_perfil, t.avaliacao_media,
           COUNT(DISTINCT a.id) AS total_avaliacoes
    FROM tecnico t
    LEFT JOIN avaliacao a ON a.tecnico_id = t.id
    WHERE t.destaque = 1 AND t.ativo = 1 AND t.status_cadastro = 'Aprovado'
    GROUP BY t.id
    ORDER BY t.avaliacao_media DESC, t.nome ASC
    LIMIT 6
");
$destaques = $stmtDest->fetchAll();

$stmtStats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM tecnico WHERE ativo = 1 AND status_cadastro = 'Aprovado') AS total_prestadores,
        (SELECT COUNT(*) FROM chamado) AS total_chamados,
        (SELECT ROUND(AVG(nota), 1) FROM avaliacao) AS media_geral
");
$stats = $stmtStats->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fix Now - Serviços Técnicos Rápidos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    /* ── secao-principal ── */
    .secao-principal {
      min-height: 88vh;
      background: linear-gradient(135deg, #0d1b3d 0%, #1a2b63 55%, #c95e00 100%);
      color: #fff;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
    }
    .secao-principal::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .secao-principal h1 { line-height: 1.12; max-width: 560px; }
    .secao-principal .lead { max-width: 500px; color: rgba(255,255,255,.85); }
    .emblema-principal {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.22);
      border-radius: 50px;
      padding: .35rem .9rem;
      font-size: .82rem;
      color: #ffe8bb;
      margin-bottom: 1rem;
      backdrop-filter: blur(4px);
    }
    /* Stat cards no secao-principal */
    .stats-principal {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      position: relative;
      z-index: 1;
    }
    .card-estatistica {
      background: rgba(255,255,255,.10);
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 16px;
      padding: 1.1rem 1.4rem;
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: transform .2s, box-shadow .2s;
    }
    .card-estatistica:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px rgba(0,0,0,.25);
    }
    .icone-estatistica {
      font-size: 2rem;
      line-height: 1;
      flex-shrink: 0;
    }
    .valor-estatistica {
      font-size: 1.65rem;
      font-weight: 800;
      color: #ffc107;
      line-height: 1;
    }
    .rotulo-estatistica {
      font-size: .78rem;
      color: rgba(255,255,255,.75);
      margin-top: .15rem;
    }

    /* ── Trust bar ── */
    .barra-confianca {
      background: #0d1b3d;
      color: #fff;
      padding: .85rem 0;
    }
    .item-confianca {
      display: flex;
      align-items: center;
      gap: .55rem;
      font-size: .88rem;
    }
    .ponto-confianca {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #ffc107;
      flex-shrink: 0;
    }

    /* ── Categorias ── */
    .card-categoria {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      background: #fff;
      border: 1.5px solid #e8ecf3;
      border-radius: 16px;
      padding: 1.4rem 1rem;
      color: #0d1b3d;
      transition: all .22s ease;
      text-align: center;
    }
    .card-categoria:hover {
      border-color: #ffc107;
      background: #fffbf0;
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(255,193,7,.15);
      color: #0d1b3d;
    }
    .icone-categoria {
      font-size: 2.4rem;
      margin-bottom: .5rem;
      line-height: 1;
    }
    .rotulo-categoria {
      font-size: .88rem;
      font-weight: 600;
    }

    /* ── Como funciona ── */
    .card-etapa {
      background: #fff;
      border: 1px solid #e8ecf3;
      border-radius: 16px;
      padding: 1.8rem 1.5rem;
      text-align: center;
      box-shadow: 0 6px 18px rgba(13,27,61,.05);
      height: 100%;
      position: relative;
      transition: transform .2s, box-shadow .2s;
    }
    .card-etapa:hover {
      transform: translateY(-4px);
      box-shadow: 0 14px 30px rgba(13,27,61,.10);
    }
    .numero-etapa {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ffc107, #ff7a00);
      color: #0d1b3d;
      font-size: 1.2rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto .9rem;
      box-shadow: 0 4px 12px rgba(255,193,7,.4);
    }
    .icone-etapa { font-size: 2rem; margin-bottom: .6rem; line-height: 1; }
    .card-etapa h5 { font-weight: 700; font-size: 1.05rem; margin-bottom: .5rem; }
    .card-etapa p { font-size: .9rem; color: #667085; margin: 0; }
    .conector-etapa {
      display: none;
    }
    @media (min-width:768px) {
      .conector-etapa {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d0d7e6;
        font-size: 1.8rem;
        padding-top: 2.2rem;
      }
    }
    .link-rapido {
      display: flex;
      align-items: center;
      gap: 1rem;
      text-decoration: none;
      background: #fff;
      border: 1.5px solid #e8ecf3;
      border-radius: 14px;
      color: #0d1b3d;
      padding: 1.25rem 1.3rem;
      transition: all .2s ease;
    }
    .link-rapido:hover {
      border-color: #ffc107;
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(13,27,61,.09);
      color: #0d1b3d;
    }
    .icone-rapido {
      font-size: 2rem;
      line-height: 1;
      flex-shrink: 0;
    }
    .link-rapido small { color: #667085; font-size: .82rem; }
    .seta-rapida {
      margin-left: auto;
      color: #ccd4e0;
      font-size: 1.1rem;
      transition: transform .2s;
    }
    .link-rapido:hover .seta-rapida { transform: translateX(4px); color: #ffc107; }

    .card-destaque {
      border-radius: 16px;
      border: 2px solid #ffc107;
      background: #fff;
      box-shadow: 0 8px 24px rgba(255,122,0,.13);
      transition: transform .2s, box-shadow .2s;
      overflow: hidden;
    }
    .card-destaque:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 32px rgba(255,122,0,.22);
    }
    .foto-destaque {
      width: 72px; height: 72px;
      border-radius: 50%; object-fit: cover;
      border: 3px solid #ffc107;
    }
    .foto-destaque-fallback {
      width: 72px; height: 72px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0d1b3d, #1f3c82);
      color: #fff; font-size: 1.6rem; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      border: 3px solid #ffc107; flex-shrink: 0;
    }
    .estrelas-mini { color: #ffc107; font-size: .85rem; }

    .titulo-secao { font-size: 1.95rem; margin-bottom: .5rem; }
    .subtitulo-secao { color: #667085; max-width: 640px; margin: 0 auto 2rem; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">Fix Now</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link active" href="index.php">Início</a></li>
        <li class="nav-item"><a class="nav-link" href="catalogo.php">Catálogo</a></li>
        <li class="nav-item"><a class="nav-link" href="#como-funciona">Como funciona</a></li>
        <li class="nav-item"><a class="nav-link" href="#acessos-rapidos">Acessos</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
        <li class="nav-item"><a class="btn btn-sm btn-warning ms-lg-1 px-3 fw-semibold" href="view/cliente/solicitar.php">Pedir serviço</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="secao-principal pt-5">
  <div class="container py-4 py-lg-5" style="position:relative;z-index:1;">
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <div class="emblema-principal">⚡ Atendimento em até 1 hora</div>
        <h1 class="display-5 fw-bold">Seu problema resolvido com rapidez e confiança</h1>
        <p class="lead mt-3">
          Conectamos você a técnicos verificados em Suporte TI, Elétrica, Hidráulica, Pintura e Marcenaria.
        </p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="view/cadastrarCliente.php" class="btn btn-warning btn-lg fw-semibold px-4">Criar conta grátis</a>
          <a href="view/cliente/solicitar.php" class="btn btn-outline-light btn-lg px-4">Pedir serviço agora</a>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-4" style="font-size:.83rem;color:rgba(255,255,255,.7);">
          <span>✅ Sem taxa de cadastro</span>
          <span>✅ Orçamento gratuito</span>
          <span>✅ Prestadores verificados</span>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="stats-principal">
          <div class="card-estatistica">
            <div class="icone-estatistica">👨‍🔧</div>
            <div>
              <div class="valor-estatistica"><?php echo $stats['total_prestadores'] > 0 ? $stats['total_prestadores'] . '+' : '0'; ?></div>
              <div class="rotulo-estatistica">Prestadores ativos e verificados</div>
            </div>
          </div>
          <div class="card-estatistica">
            <div class="icone-estatistica">🔧</div>
            <div>
              <div class="valor-estatistica"><?php echo $stats['total_chamados'] > 0 ? $stats['total_chamados'] . '+' : '0'; ?></div>
              <div class="rotulo-estatistica">Chamados abertos na plataforma</div>
            </div>
          </div>
          <div class="card-estatistica">
            <div class="icone-estatistica">⭐</div>
            <div>
              <div class="valor-estatistica"><?php echo $stats['media_geral'] ? number_format((float)$stats['media_geral'], 1, ',', '.') : '—'; ?></div>
              <div class="rotulo-estatistica">Avaliação média dos prestadores</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<div class="barra-confianca">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-center gap-4">
      <div class="item-confianca"><div class="ponto-confianca"></div> Prestadores aprovados manualmente</div>
      <div class="item-confianca"><div class="ponto-confianca"></div> Orçamento transparente antes de aceitar</div>
      <div class="item-confianca"><div class="ponto-confianca"></div> Suporte a múltiplas categorias</div>
      <div class="item-confianca"><div class="ponto-confianca"></div> Acompanhe seu chamado em tempo real</div>
    </div>
  </div>
</div>
<section class="py-5" id="categorias">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold titulo-secao">O que você precisa?</h2>
      <p class="subtitulo-secao">Selecione uma categoria e encontre o prestador ideal para o seu problema.</p>
    </div>
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="catalogo.php?categoria=Suporte+TI" class="card-categoria h-100">
          <div class="icone-categoria">💻</div>
          <div class="rotulo-categoria">Suporte TI</div>
        </a>
      </div>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="catalogo.php?categoria=Elétrica" class="card-categoria h-100">
          <div class="icone-categoria">⚡</div>
          <div class="rotulo-categoria">Elétrica</div>
        </a>
      </div>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="catalogo.php?categoria=Hidráulica" class="card-categoria h-100">
          <div class="icone-categoria">🔧</div>
          <div class="rotulo-categoria">Hidráulica</div>
        </a>
      </div>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="catalogo.php?categoria=Pintura" class="card-categoria h-100">
          <div class="icone-categoria">🎨</div>
          <div class="rotulo-categoria">Pintura</div>
        </a>
      </div>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="catalogo.php?categoria=Marcenaria" class="card-categoria h-100">
          <div class="icone-categoria">🪚</div>
          <div class="rotulo-categoria">Marcenaria</div>
        </a>
      </div>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="catalogo.php" class="card-categoria h-100" style="border-style:dashed;">
          <div class="icone-categoria">🔍</div>
          <div class="rotulo-categoria">Ver tudo</div>
        </a>
      </div>
    </div>
  </div>
</section>
<section class="py-5 bg-light" id="como-funciona">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold titulo-secao">Como funciona</h2>
      <p class="subtitulo-secao">Do pedido ao atendimento em 4 passos simples.</p>
    </div>
    <div class="row g-3 align-items-start">
      <div class="col-md">
        <div class="card-etapa">
          <div class="numero-etapa">1</div>
          <div class="icone-etapa">📋</div>
          <h5>Descreva o problema</h5>
          <p>Informe o que precisa, adicione fotos e escolha a categoria do serviço.</p>
        </div>
      </div>
      <div class="conector-etapa col-md-auto px-0">›</div>
      <div class="col-md">
        <div class="card-etapa">
          <div class="numero-etapa">2</div>
          <div class="icone-etapa">💬</div>
          <h5>Receba orçamentos</h5>
          <p>Prestadores disponíveis analisam seu chamado e enviam propostas de preço.</p>
        </div>
      </div>
      <div class="conector-etapa col-md-auto px-0">›</div>
      <div class="col-md">
        <div class="card-etapa">
          <div class="numero-etapa">3</div>
          <div class="icone-etapa">✅</div>
          <h5>Aceite e confirme</h5>
          <p>Escolha a melhor proposta, confirme o pagamento e o técnico é acionado.</p>
        </div>
      </div>
      <div class="conector-etapa col-md-auto px-0">›</div>
      <div class="col-md">
        <div class="card-etapa">
          <div class="numero-etapa">4</div>
          <div class="icone-etapa">🛠️</div>
          <h5>Problema resolvido</h5>
          <p>Acompanhe o status do chamado e avalie o prestador ao final do serviço.</p>
        </div>
      </div>
    </div>
    <div class="text-center mt-4">
      <a href="view/cliente/solicitar.php" class="btn btn-warning btn-lg fw-semibold px-5">Começar agora</a>
    </div>
  </div>
</section>

<?php if ($destaques): ?>
<section class="py-5" style="background: linear-gradient(135deg,#fffbf0 0%,#fff8e1 100%);">
  <div class="container">
    <div class="text-center mb-4">
      <span class="badge bg-warning text-dark px-3 py-2 mb-2" style="font-size:.9rem;">Selecionados pela Fix Now</span>
      <h2 class="fw-bold titulo-secao">Prestadores em Destaque</h2>
      <p class="subtitulo-secao">Profissionais avaliados e indicados pela nossa equipe.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <?php foreach ($destaques as $d):
        $estrelas = round((float)$d['avaliacao_media']);
      ?>
        <div class="col-sm-6 col-lg-4">
          <div class="card-destaque p-4 h-100 d-flex flex-column">
            <div class="d-flex align-items-center gap-3 mb-3">
              <?php if ($d['foto_perfil'] && file_exists(__DIR__ . '/' . $d['foto_perfil'])): ?>
                <img src="<?php echo htmlspecialchars($d['foto_perfil']); ?>" alt="<?php echo htmlspecialchars($d['nome']); ?>" class="foto-destaque">
              <?php else: ?>
                <div class="foto-destaque-fallback"><?php echo htmlspecialchars(mb_substr($d['nome'], 0, 1)); ?></div>
              <?php endif; ?>
              <div>
                <div class="fw-bold fs-6"><?php echo htmlspecialchars($d['nome']); ?></div>
                <div class="small text-muted"><?php echo htmlspecialchars($d['especialidade'] ?? 'Prestador de serviços'); ?></div>
                <div class="estrelas-mini mt-1">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php echo $i <= $estrelas ? '★' : '☆'; ?>
                  <?php endfor; ?>
                  <span class="text-muted" style="font-size:.8rem;">
                    <?php echo number_format((float)$d['avaliacao_media'], 1, ',', '.'); ?>
                    <?php if ($d['total_avaliacoes'] > 0): ?>(<?php echo (int)$d['total_avaliacoes']; ?>)<?php endif; ?>
                  </span>
                </div>
              </div>
            </div>
            <div class="mt-auto">
              <a href="prestador/portfolio-publico.php?id=<?php echo (int)$d['id']; ?>" class="btn btn-warning btn-sm fw-semibold w-100">Ver portfólio →</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="catalogo.php" class="btn btn-outline-warning fw-semibold px-4">Ver catálogo completo</a>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="py-5" id="acessos-rapidos">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold titulo-secao">Acessos rápidos</h2>
      <p class="subtitulo-secao">Navegue diretamente para o que você precisa.</p>
    </div>
    <div class="row g-3 g-lg-4">
      <div class="col-md-6 col-lg-3">
        <a class="link-rapido h-100" href="view/cadastrarCliente.php">
          <div class="icone-rapido">👤</div>
          <div>
            <h6 class="mb-1 fw-bold">Sou Cliente</h6>
            <small>Crie sua conta e solicite atendimento agora.</small>
          </div>
          <div class="seta-rapida">›</div>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a class="link-rapido h-100" href="login.php">
          <div class="icone-rapido">🔑</div>
          <div>
            <h6 class="mb-1 fw-bold">Sou Prestador</h6>
            <small>Acesse seu painel e veja os chamados disponíveis.</small>
          </div>
          <div class="seta-rapida">›</div>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a class="link-rapido h-100" href="catalogo.php">
          <div class="icone-rapido">📂</div>
          <div>
            <h6 class="mb-1 fw-bold">Catálogo</h6>
            <small>Explore serviços e portfólios dos prestadores.</small>
          </div>
          <div class="seta-rapida">›</div>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a class="link-rapido h-100" href="view/cliente/solicitar.php">
          <div class="icone-rapido">🚀</div>
          <div>
            <h6 class="mb-1 fw-bold">Pedir serviço</h6>
            <small>Abra um chamado direto e receba orçamentos.</small>
          </div>
          <div class="seta-rapida">›</div>
        </a>
      </div>
    </div>
  </div>
</section>

<footer class="bg-dark text-light py-4">
  <div class="container">
    <div class="row align-items-center g-2">
      <div class="col-md-6 text-center text-md-start">
        <span class="fw-bold fs-5">Fix Now</span>
        <span class="text-secondary ms-2" style="font-size:.85rem;">Plataforma de serviços técnicos rápidos</span>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <small class="text-secondary">&copy; <?php echo date('Y'); ?> Fix Now. Todos os direitos reservados.</small>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/index-home.js"></script>
</body>
</html>
